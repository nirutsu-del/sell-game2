<?php

namespace App\Services;

use App\Models\{GameAccount, GachaBox, GachaItem, GachaSpin, PurchaseHistory, User, WalletTransaction};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StoreService
{
    public function buy(User $user, int $accountId): PurchaseHistory
    {
        return DB::transaction(function () use ($user, $accountId) {
            $user = User::lockForUpdate()->findOrFail($user->id);
            $account = GameAccount::lockForUpdate()->findOrFail($accountId);
            if ($account->status !== 'available') {
                throw ValidationException::withMessages(['account' => 'ไอดีนี้ถูกขายไปแล้ว']);
            }
            if ($user->balance < $account->price) {
                throw ValidationException::withMessages(['balance' => 'ยอดเงินใน Wallet ไม่เพียงพอ']);
            }
            $credentials = $account->credentials();
            $purchase = PurchaseHistory::create([
                'user_id' => $user->id,
                'game_account_id' => $account->id,
                'price_paid' => $account->price,
                'account_data_delivered' => encrypt(json_encode($credentials, JSON_THROW_ON_ERROR)),
                'source' => 'shop',
            ]);
            $user->decrement('balance', $account->price);
            $account->update(['status' => 'sold']);
            WalletTransaction::create([
                'user_id' => $user->id,
                'amount' => $account->price,
                'type' => 'debit',
                'description' => 'ซื้อ ' . $account->title,
                'reference_type' => PurchaseHistory::class,
                'reference_id' => $purchase->id,
            ]);
            StoreNotifier::admins('มีคำสั่งซื้อไอดีใหม่ A-'.$purchase->id, $account->title.' · ฿'.number_format($account->price,2), 'admin_purchase', $purchase->id);
            $user->notify(new \App\Notifications\StoreNotification('ส่งมอบไอดีแล้ว · A-'.$purchase->id, $account->title, 'purchase', $purchase->id));
            return $purchase;
        }, 3);
    }

    public function spin(User $user, int $boxId, ?string $requestId = null): array
    {
        return DB::transaction(function () use ($user, $boxId, $requestId) {
            $user = User::lockForUpdate()->findOrFail($user->id);
            if ($requestId) {
                $previous = GachaSpin::where('user_id', $user->id)->where('request_id', $requestId)->first();
                if ($previous) {
                    if ($previous->gacha_box_id !== $boxId) {
                        throw ValidationException::withMessages(['request_id' => 'คำขอนี้ใช้กับกล่องอื่นแล้ว']);
                    }
                    return array_merge($previous->result_data, ['new_balance' => (float) $user->balance]);
                }
            }
            $box = GachaBox::lockForUpdate()->findOrFail($boxId);

            if (!$box->is_active) {
                throw ValidationException::withMessages(['box' => 'กล่องนี้ปิดใช้งานอยู่']);
            }
            if ($user->balance < $box->price_per_spin) {
                throw ValidationException::withMessages(['balance' => 'ยอดเงินใน Wallet ไม่เพียงพอ กรุณาเติมเงินก่อนสุ่ม']);
            }

            // Filter available items: credit or unsold game account
            $items = GachaItem::where('gacha_box_id', $box->id)
                ->where('drop_rate', '>', 0)
                ->lockForUpdate()
                ->get()
                ->filter(function ($item) {
                    if ($item->reward_type === 'credit') {
                        return true;
                    }
                    if (!$item->game_account_id) {
                        return false;
                    }
                    return GameAccount::whereKey($item->game_account_id)->lockForUpdate()->value('status') === 'available';
                })
                ->values();

            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['box' => 'ของรางวัลในกล่องหมดแล้ว']);
            }

            $totalWeight = $items->sum(fn ($i) => (float)$i->drop_rate);
            if ($totalWeight <= 0) {
                throw ValidationException::withMessages(['box' => 'อัตราการออกของรางวัลยังไม่ถูกต้อง']);
            }

            $roll = (random_int(1, 10000000) / 10000000) * $totalWeight;
            $sum = 0.0;
            $selectedItem = $items->last();
            foreach ($items as $candidate) {
                $sum += (float)$candidate->drop_rate;
                if ($roll <= $sum) {
                    $selectedItem = $candidate;
                    break;
                }
            }

            // Deduct spin fee
            $user->decrement('balance', $box->price_per_spin);
            WalletTransaction::create([
                'user_id' => $user->id,
                'amount' => $box->price_per_spin,
                'type' => 'debit',
                'description' => 'สุ่ม ' . $box->name,
                'reference_type' => GachaBox::class,
                'reference_id' => $box->id,
            ]);

            $purchase = null;
            $credentials = null;
            $accountTitle = null;

            if ($selectedItem->reward_type === 'credit') {
                $creditAmt = (float)$selectedItem->credit_amount;
                $user->increment('balance', $creditAmt);
                WalletTransaction::create([
                    'user_id' => $user->id,
                    'amount' => $creditAmt,
                    'type' => 'credit',
                    'description' => 'รางวัลเครดิตจาก ' . $box->name,
                    'reference_type' => GachaBox::class,
                    'reference_id' => $box->id,
                ]);
                $result = 'ยินดีด้วย! คุณได้รับเครดิตคืน ' . number_format($creditAmt, 2) . ' บาท';
            } else {
                $account = GameAccount::lockForUpdate()->findOrFail($selectedItem->game_account_id);
                if ($account->status !== 'available') {
                    throw ValidationException::withMessages(['box' => 'รางวัลนี้เพิ่งมีผู้ได้รับไป กรุณาสุ่มใหม่อีกครั้ง']);
                }

                $credentials = $account->credentials();
                $purchase = PurchaseHistory::create([
                    'user_id' => $user->id,
                    'game_account_id' => $account->id,
                    'price_paid' => 0,
                    'account_data_delivered' => encrypt(json_encode($credentials, JSON_THROW_ON_ERROR)),
                    'source' => 'gacha',
                ]);
                $account->update(['status' => 'sold']);
                $accountTitle = $account->title;
                $result = '🎉 ยินดีด้วย! คุณได้รับ: ' . $account->title;
            }

            $spinRecord = GachaSpin::create([
                'user_id' => $user->id,
                'gacha_box_id' => $box->id,
                'gacha_item_id' => $selectedItem->id,
                'purchase_history_id' => $purchase?->id,
                'price_paid' => $box->price_per_spin,
                'request_id' => $requestId,
            ]);

            $finalBalance = (float)User::where('id', $user->id)->value('balance');

            $response = [
                'result' => $result,
                'spin_id' => $spinRecord->id,
                'item_id' => $selectedItem->id,
                'reward_image' => isset($account) && !empty($account->images[0]) ? asset('storage/'.$account->images[0]) : null,
                'reward_type' => $selectedItem->reward_type,
                'credit_amount' => (float)$selectedItem->credit_amount,
                'account_title' => $accountTitle,
                'purchase_id' => $purchase?->id,
                'purchase_url' => $purchase ? route('purchases.show', $purchase->id) : null,
                'new_balance' => $finalBalance,
                'box_name' => $box->name,
            ];
            $spinRecord->update(['result_data' => $response]);
            if ($purchase) {
                // Keep the original item for historical spins; a new item occupies its place.
                $replacement = GameAccount::where('category_id', $account->category_id)
                    ->where('status', 'available')
                    ->whereNotIn('id', GachaItem::where('gacha_box_id', $box->id)->whereNotNull('game_account_id')->select('game_account_id'))
                    ->orderBy('id')->lockForUpdate()->first();
                if ($replacement) {
                    GachaItem::create([
                        'gacha_box_id'=>$box->id, 'game_account_id'=>$replacement->id,
                        'reward_type'=>'game_account', 'drop_rate'=>$selectedItem->drop_rate,
                    ]);
                    $selectedItem->update(['drop_rate'=>0]);
                }
            }
            return $response;
        }, 3);
    }
}
