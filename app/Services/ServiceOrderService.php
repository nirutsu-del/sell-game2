<?php
namespace App\Services;
use App\Models\{User, Product, ProductVariant, ServiceOrder, WalletTransaction};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class ServiceOrderService {
    public function place(User $user, Product $product, array $data): ServiceOrder {
        return DB::transaction(function () use ($user,$product,$data) {
            $buyer = User::lockForUpdate()->findOrFail($user->id);
            $existing = ServiceOrder::where('user_id',$buyer->id)->where('request_id',$data['request_id'])->first();
            if ($existing) return $existing;
            $product = Product::lockForUpdate()->findOrFail($product->id);
            $variant = ProductVariant::where('product_id',$product->id)->lockForUpdate()->findOrFail($data['variant_id']);
            if (!$product->is_active || !$variant->is_active) throw ValidationException::withMessages(['product'=>'สินค้านี้ปิดรับคำสั่งซื้อแล้ว']);
            if ($variant->stock !== null && $variant->stock < $data['quantity']) throw ValidationException::withMessages(['quantity'=>'สต๊อกไม่เพียงพอ']);
            $totalCents = (int) round((float)$variant->price * 100) * $data['quantity'];
            if ((int) round((float)$buyer->balance*100) < $totalCents) throw ValidationException::withMessages(['balance'=>'เงินใน Wallet ไม่เพียงพอ กรุณาเติมเงิน']);
            $total = number_format($totalCents/100,2,'.','');
            $order = ServiceOrder::create([
                'user_id'=>$buyer->id,'product_variant_id'=>$variant->id,'request_id'=>$data['request_id'],
                'product_name'=>$product->name,'variant_name'=>$variant->name,'quantity'=>$data['quantity'],
                'recipient'=>$data['recipient'],'terms'=>$product->terms,'total'=>$total,'status'=>'pending',
            ]);
            $buyer->decrement('balance',$total);
            if ($variant->stock !== null) $variant->decrement('stock',$data['quantity']);
            WalletTransaction::create(['user_id'=>$buyer->id,'amount'=>$total,'type'=>'debit','description'=>'สั่งซื้อ '.$product->name,'reference_type'=>ServiceOrder::class,'reference_id'=>$order->id]);
            StoreNotifier::admins('มีคำสั่งซื้อใหม่ S-'.$order->id, $product->name.' · ฿'.number_format($total,2), 'admin_order', $order->id);
            return $order;
        },3);
    }
    public function updateStatus(ServiceOrder $order, string $status, ?string $note, User $actor, ?string $expectedStatus = null): void {
        abort_unless($actor->isAdmin(),403);
        DB::transaction(function () use ($order,$status,$note,$actor,$expectedStatus) {
            $user = User::lockForUpdate()->findOrFail($order->user_id);
            $order = ServiceOrder::lockForUpdate()->findOrFail($order->id);
            if ($expectedStatus !== null && $order->status !== $expectedStatus) {
                throw ValidationException::withMessages(['status'=>'รายการนี้มีผู้แก้ไขแล้ว กรุณารีเฟรชก่อนดำเนินการ']);
            }
            $previousStatus = $order->status;
            $allowed = ['pending'=>['processing','completed','refunded'],'processing'=>['completed','refunded'],'completed'=>[],'refunded'=>[]];
            if (!in_array($status,$allowed[$order->status] ?? [])) throw ValidationException::withMessages(['status'=>'ไม่สามารถเปลี่ยนสถานะรายการนี้ได้']);
            if ($status === 'refunded') {
                $user->increment('balance',$order->total);
                $variant = ProductVariant::lockForUpdate()->findOrFail($order->product_variant_id);
                if ($variant->stock !== null) $variant->increment('stock',$order->quantity);
                WalletTransaction::create(['user_id'=>$user->id,'amount'=>$order->total,'type'=>'credit','description'=>'คืนเงินคำสั่งซื้อ #'.$order->id,'reference_type'=>ServiceOrder::class,'reference_id'=>$order->id]);
            }
            $order->update(['status'=>$status,'delivery_note'=>$note]);
            $order->events()->create([
                'actor_id'=>$actor->id,'actor_name'=>$actor->name,
                'from_status'=>$previousStatus,'to_status'=>$status,'note'=>$note,'created_at'=>now(),
            ]);
            $title = ['processing'=>'ร้านเริ่มดำเนินการแล้ว','completed'=>'ส่งมอบงานสำเร็จ','refunded'=>'คืนเงินเข้า Wallet แล้ว'][$status];
            $user->notify(new \App\Notifications\StoreNotification(
                $title.' · S-'.$order->id,
                $order->product_name.($status === 'refunded' ? ' · ฿'.number_format($order->total,2) : ''),
                'order', $order->id,
            ));
        },3);
    }
}
