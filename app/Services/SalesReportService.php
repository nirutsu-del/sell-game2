<?php
namespace App\Services;

use App\Models\{WalletTransaction, PurchaseHistory, ServiceOrder, GachaBox, TopupTransaction};
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class SalesReportService
{
    public function period(Request $request): array
    {
        $data = $request->validate([
            'period'=>'nullable|in:today,month,custom,all',
            'from'=>'nullable|required_if:period,custom|date_format:Y-m-d',
            'to'=>'nullable|required_if:period,custom|date_format:Y-m-d|after_or_equal:from',
        ]);
        $period = $data['period'] ?? 'month';
        $today = CarbonImmutable::now('Asia/Bangkok');
        $from = match($period) {
            'today'=>$today->startOfDay(),
            'month'=>$today->startOfMonth(),
            'custom'=>CarbonImmutable::createFromFormat('!Y-m-d',$data['from'],'Asia/Bangkok'),
            default=>null,
        };
        $to = $period === 'custom' ? CarbonImmutable::createFromFormat('!Y-m-d',$data['to'],'Asia/Bangkok') : $today;
        return ['period'=>$period,'from'=>$from?->format('Y-m-d'),'to'=>$from ? $to->format('Y-m-d') : null,
            'start'=>$from?->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s'),
            'end'=>$from ? $to->addDay()->startOfDay()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s') : null];
    }
    public function definitions(): array
    {
        return [
            'accounts'=>['label'=>'ขายไอดี','type'=>'debit','ref'=>(new PurchaseHistory)->getMorphClass()],
            'services'=>['label'=>'งานบริการ','type'=>'debit','ref'=>(new ServiceOrder)->getMorphClass()],
            'gacha'=>['label'=>'ค่าสุ่ม','type'=>'debit','ref'=>(new GachaBox)->getMorphClass()],
            'refunds'=>['label'=>'คืนเงินงานบริการ','type'=>'credit','ref'=>(new ServiceOrder)->getMorphClass()],
            'rewards'=>['label'=>'เครดิตรางวัลสุ่ม','type'=>'credit','ref'=>(new GachaBox)->getMorphClass()],
            'topups'=>['label'=>'เติม Wallet สำเร็จ','type'=>'credit','ref'=>(new TopupTransaction)->getMorphClass()],
        ];
    }
    public function query(array $period)
    {
        return WalletTransaction::query()->when($period['start'],fn($q)=>$q
            ->where('wallet_transactions.created_at','>=',$period['start'])
            ->where('wallet_transactions.created_at','<',$period['end']));
    }
    public static function cents(string|int|float $amount): int
    {
        $parts = explode('.',(string)$amount,2);
        return ((int)$parts[0])*100 + (int)str_pad(substr($parts[1] ?? '',0,2),2,'0');
    }
    public function summary(array $period): array
    {
        $groups = $this->query($period)->selectRaw('reference_type, type, SUM(amount) as total, COUNT(*) as records')->groupBy('reference_type','type')->get();
        $totals = []; $known = 0;
        foreach ($this->definitions() as $key=>$def) {
            $group = $groups->first(fn($g)=>$g->reference_type === $def['ref'] && $g->type === $def['type']);
            $totals[$key] = ['label'=>$def['label'],'cents'=>self::cents($group?->total ?? 0),'count'=>(int)($group?->records ?? 0)];
            $known += $totals[$key]['count'];
        }
        $gross = $totals['accounts']['cents']+$totals['services']['cents']+$totals['gacha']['cents'];
        return ['totals'=>$totals,'gross'=>$gross,'net'=>$gross-$totals['refunds']['cents']-$totals['rewards']['cents'],
            'paidCount'=>$totals['accounts']['count']+$totals['services']['count']+$totals['gacha']['count'],
            'unclassified'=>(int)$groups->sum('records')-$known];
    }
    public function bestSellers(array $period)
    {
        $definitions = $this->definitions();
        $service = $this->query($period)->where('reference_type',$definitions['services']['ref'])->where('type','debit')
            ->join('service_orders as so','so.id','=','wallet_transactions.reference_id')
            ->selectRaw("so.product_variant_id as item_id, so.product_name as title, so.variant_name as subtitle, SUM(so.quantity) as units, COUNT(*) as orders, SUM(wallet_transactions.amount) as total")
            ->groupBy('so.product_variant_id','so.product_name','so.variant_name')->orderByDesc('total')->limit(10)->get()->map(fn($r)=>['type'=>'บริการ','title'=>$r->title.' / '.$r->subtitle,'units'=>(int)$r->units,'orders'=>(int)$r->orders,'cents'=>self::cents($r->total)]);
        $account = $this->query($period)->where('reference_type',$definitions['accounts']['ref'])->where('type','debit')
            ->join('purchase_histories as ph','ph.id','=','wallet_transactions.reference_id')->join('game_accounts as ga','ga.id','=','ph.game_account_id')
            ->selectRaw('ga.id as item_id, ga.title as title, COUNT(*) as orders, SUM(wallet_transactions.amount) as total')
            ->groupBy('ga.id','ga.title')->orderByDesc('total')->limit(10)->get()->map(fn($r)=>['type'=>'ไอดี','title'=>$r->title,'units'=>(int)$r->orders,'orders'=>(int)$r->orders,'cents'=>self::cents($r->total)]);
        $gacha = $this->query($period)->where('reference_type',$definitions['gacha']['ref'])->where('type','debit')
            ->leftJoin('gacha_boxes as gb','gb.id','=','wallet_transactions.reference_id')
            ->selectRaw("wallet_transactions.reference_id as item_id, COALESCE(gb.name, 'กล่องที่ถูกลบ') as title, COUNT(*) as orders, SUM(wallet_transactions.amount) as total")
            ->groupBy('wallet_transactions.reference_id','gb.name')->orderByDesc('total')->limit(10)->get()->map(fn($r)=>['type'=>'สุ่ม','title'=>$r->title,'units'=>(int)$r->orders,'orders'=>(int)$r->orders,'cents'=>self::cents($r->total)]);
        return $service->concat($account)->concat($gacha)->sortByDesc('cents')->take(10)->values();
    }
    public function classify($transaction): string
    {
        foreach ($this->definitions() as $def) if ($def['ref'] === $transaction->reference_type && $def['type'] === $transaction->type) return $def['label'];
        return 'รายการอื่น (ไม่นับในยอดขาย)';
    }
}
