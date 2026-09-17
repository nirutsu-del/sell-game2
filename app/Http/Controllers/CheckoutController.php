<?php

namespace App\Http\Controllers;

use App\Models\GameAccount;
use App\Services\StoreService;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function buy(Request $r, GameAccount $account, StoreService $store)
    {
        $purchase = $store->buy($r->user(), $account->id);
        return redirect()->route('purchases.show', $purchase)->with('success', 'ชำระเงินสำเร็จ ข้อมูลไอดีอยู่ด้านล่าง')->with('reveal_purchase_id', $purchase->id);
    }

    public function spin(Request $r, int $box, StoreService $store)
    {
        $data = $r->validate(['request_id' => ['nullable', 'uuid']]);
        $result = $store->spin($r->user(), $box, $data['request_id'] ?? null);

        if ($r->expectsJson() || $r->ajax()) {
            $eligible = \App\Models\GachaBox::findOrFail($box)->eligibleItems();
            $weight = $eligible->sum('drop_rate');
            $nextRewards = $eligible->map(fn ($item) => [
                'id'=>$item->id, 'type'=>$item->reward_type,
                'title'=>$item->reward_type === 'credit' ? 'เครดิต ฿'.number_format($item->credit_amount,2) : $item->account->title,
                'image'=>!empty($item->account?->images[0]) ? asset('storage/'.$item->account->images[0]) : null,
                'chance'=>$weight > 0 ? round($item->drop_rate / $weight * 100,4) : 0,
            ])->values();
            return response()->json([
                'success' => true,
                ...$result,
                'next_rewards'=>$nextRewards,
            ]);
        }

        return redirect()->route('gacha.show', $box)->with('success', $result['result']);
    }

    public function purchase(Request $r, int $id)
    {
        $p = $r->user()->purchases()->with('account')->findOrFail($id);
        return view('purchases.show', [
            'purchase' => $p,
            'credentials' => json_decode(decrypt($p->account_data_delivered), true),
        ]);
    }
}
