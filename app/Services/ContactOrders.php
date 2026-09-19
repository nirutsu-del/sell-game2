<?php

namespace App\Services;

use App\Models\{ServiceOrder, PurchaseHistory, GachaSpin, User};
use Illuminate\Validation\ValidationException;

class ContactOrders
{
    public static function options(?User $user): array
    {
        if (!$user) return [];
        $options = [];
        foreach (['S'=>ServiceOrder::class,'A'=>PurchaseHistory::class,'G'=>GachaSpin::class] as $prefix=>$model) {
            $query = $model::where('user_id',$user->id);
            if ($prefix === 'A') $query->where('source','shop');
            foreach ($query->latest('id')->limit(100)->get(['id','created_at']) as $order) {
                $options[] = ['reference'=>$prefix.'-'.$order->id,'date'=>$order->created_at];
            }
        }
        return collect($options)->sortByDesc('date')->take(100)->values()->all();
    }

    public static function validateReference(?User $user, ?string $reference): ?string
    {
        if (!$reference) return null;
        $reference = strtoupper(trim($reference));
        if ($user && preg_match('/^(S|A|G)-([1-9][0-9]*)$/D',$reference,$match)) {
            $model = ['S'=>ServiceOrder::class,'A'=>PurchaseHistory::class,'G'=>GachaSpin::class][$match[1]];
            $query = $model::where('user_id',$user->id)->whereKey($match[2]);
            if ($match[1] === 'A') $query->where('source','shop');
            if ($query->exists()) return $reference;
        }
        throw ValidationException::withMessages(['order_reference'=>'กรุณาเลือกคำสั่งซื้อของบัญชีคุณ หรือเว้นว่างหากไม่เกี่ยวกับคำสั่งซื้อ']);
    }
}
