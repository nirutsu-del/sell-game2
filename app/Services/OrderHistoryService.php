<?php
namespace App\Services;
use App\Models\{ServiceOrder, PurchaseHistory, GachaSpin, User};
use Illuminate\Support\Facades\DB;
class OrderHistoryService {
    public function paginate(User $user, array $filters) {
        $service = DB::table('service_orders')->where('user_id',$user->id)
            ->selectRaw("'service' as kind, id, created_at, status");
        // A gacha account is represented by its spin, not a second purchase row.
        $account = DB::table('purchase_histories')->where('purchase_histories.user_id',$user->id)->where('source','shop')
            ->join('game_accounts','game_accounts.id','=','purchase_histories.game_account_id')
            ->selectRaw("'account' as kind, purchase_histories.id as id, purchase_histories.created_at as created_at, 'completed' as status");
        $gacha = DB::table('gacha_spins')->where('gacha_spins.user_id',$user->id)
            ->join('gacha_boxes','gacha_boxes.id','=','gacha_spins.gacha_box_id')
            ->join('gacha_items','gacha_items.id','=','gacha_spins.gacha_item_id')
            ->leftJoin('game_accounts','game_accounts.id','=','gacha_items.game_account_id')
            ->selectRaw("'gacha' as kind, gacha_spins.id as id, gacha_spins.created_at as created_at, 'completed' as status");
        $search = trim($filters['q'] ?? '');
        if ($search !== '') {
            if (preg_match('/^(?:(S|A|G)-)?#?(\d+)$/i',$search,$match)) {
                $id = (int) $match[2];
                $service->where('service_orders.id',$id);
                $account->where('purchase_histories.id',$id);
                $gacha->where('gacha_spins.id',$id);
                $prefix = strtoupper($match[1] ?? '');
                if ($prefix && $prefix !== 'S') $service->whereRaw('1=0');
                if ($prefix && $prefix !== 'A') $account->whereRaw('1=0');
                if ($prefix && $prefix !== 'G') $gacha->whereRaw('1=0');
            } else {
                $service->where(fn($q)=>$q->where('product_name','like',"%$search%")->orWhere('variant_name','like',"%$search%"));
                $account->where('game_accounts.title','like',"%$search%");
                $gacha->where(fn($q)=>$q->where('gacha_boxes.name','like',"%$search%")->orWhere('game_accounts.title','like',"%$search%"));
            }
        }
        $union = $service->unionAll($account)->unionAll($gacha);
        $query = DB::query()->fromSub($union,'history');
        if (($filters['type'] ?? 'all') !== 'all') $query->where('kind',$filters['type']);
        if (!empty($filters['status'])) $query->where('status',$filters['status']);
        $page = $query->orderByDesc('created_at')->orderBy('kind')->orderByDesc('id')->paginate(15)->withQueryString();
        $rows = $page->getCollection();
        $services = ServiceOrder::where('user_id',$user->id)->whereIn('id',$rows->where('kind','service')->pluck('id'))->get()->keyBy('id');
        $accounts = PurchaseHistory::where('user_id',$user->id)->with('account')->whereIn('id',$rows->where('kind','account')->pluck('id'))->get()->keyBy('id');
        $spins = GachaSpin::where('user_id',$user->id)->with('box','item.account')->whereIn('id',$rows->where('kind','gacha')->pluck('id'))->get()->keyBy('id');
        $page->setCollection($rows->map(function ($row) use ($services,$accounts,$spins) {
            if ($row->kind === 'service') {
                $order = $services[$row->id];
                return ['reference'=>'S-'.$order->id,'type'=>'งานบริการ','title'=>$order->product_name,'detail'=>$order->variant_name.' × '.$order->quantity,'amount'=>$order->total,'status'=>$order->statusLabel(),'date'=>$order->created_at,'url'=>route('orders.show',$order),'action'=>'ดูสถานะและรายละเอียด'];
            }
            if ($row->kind === 'account') {
                $purchase = $accounts[$row->id];
                return ['reference'=>'A-'.$purchase->id,'type'=>'ซื้อไอดี','title'=>$purchase->account->title,'detail'=>'รับไอดีแล้ว','amount'=>$purchase->price_paid,'status'=>'สำเร็จ','date'=>$purchase->created_at,'url'=>route('purchases.show',$purchase->id),'action'=>'ดูข้อมูลไอดี'];
            }
            $spin = $spins[$row->id];
            $reward = $spin->result_data['result'] ?? ($spin->item->reward_type === 'credit' ? 'เครดิต ฿'.number_format($spin->item->credit_amount,2) : ($spin->item->account?->title ?? 'ไอดีเกม'));
            return ['reference'=>'G-'.$spin->id,'type'=>'สุ่มรางวัล','title'=>$spin->box->name,'detail'=>$reward,'amount'=>$spin->price_paid,'status'=>'สำเร็จ','date'=>$spin->created_at,'url'=>$spin->purchase_history_id ? route('purchases.show',$spin->purchase_history_id) : null,'action'=>'ดูไอดีที่ได้รับ'];
        }));
        return $page;
    }
}
