@extends('layouts.app')
@section('content')
@php $labels = ['pending'=>'งานใหม่','processing'=>'กำลังทำ','completed'=>'เสร็จแล้ว','refunded'=>'คืนเงินแล้ว']; @endphp
<div class="market-heading"><div><p>ADMIN / WORK QUEUE</p><h1 class="text-3xl font-bold">คิวงานบริการ</h1></div><a href="{{ route('admin.dashboard') }}">กลับภาพรวมร้าน →</a></div>
<nav aria-label="คิวตามสถานะ" class="mb-5 grid grid-cols-2 gap-3 md:grid-cols-5">
@foreach(['all'=>'ทั้งหมด'] + $labels as $value=>$label)
<a href="{{ route('admin.service-orders.index',array_merge(request()->except(['page','status','waiting']),['status'=>$value])) }}" @if($selectedStatus === $value) aria-current="page" @endif class="rounded-2xl border p-4 {{ $selectedStatus === $value ? 'border-orange-500 bg-orange-500/10' : 'border-slate-700 bg-slate-900' }}"><p class="text-xs text-slate-400">{{ $label }}</p><p class="mt-2 text-2xl font-bold text-orange-300">{{ number_format($value === 'all' ? $counts->sum() : ($counts[$value] ?? 0)) }}</p></a>
@endforeach
</nav>
<a href="{{ route('admin.service-orders.index',['status'=>'all','waiting'=>1,'sort'=>'oldest']) }}" class="mb-5 block rounded-xl border border-amber-500/30 bg-amber-500/10 p-4 text-sm text-amber-200">◷ งานยังไม่เสร็จที่เปิดมาเกิน 24 ชั่วโมง: {{ $waitingCount }} รายการ →<span class="mt-1 block text-xs text-slate-400">เป็นการเตือนตามอายุคำสั่งซื้อ ไม่ใช่กำหนดส่งของทุกสินค้า</span></a>
<form class="market-form mb-6 grid gap-3 rounded-xl border border-slate-800 bg-slate-900 p-4 md:grid-cols-4">
    <input type="hidden" name="status" value="{{ $selectedStatus }}">
    <label>เลขอ้างอิง / ชื่อลูกค้า / สินค้า<input name="q" value="{{ request('q') }}" maxlength="100" placeholder="S-12 หรือชื่อลูกค้า"></label>
    <label>เรียงลำดับ<select name="sort"><option value="oldest" @selected(request('sort','oldest') === 'oldest')>เก่าสุดก่อน</option><option value="newest" @selected(request('sort') === 'newest')>ใหม่สุดก่อน</option></select></label>
    <label class="flex items-center gap-2 self-end pb-3"><input type="checkbox" name="waiting" value="1" @checked(request('waiting'))> งานเปิดมาเกิน 24 ชั่วโมง</label>
    <div class="flex items-end gap-3"><button class="market-button">ค้นหา</button><a href="{{ route('admin.service-orders.index') }}" class="py-3 text-sm text-slate-400">ล้าง</a></div>
</form>
<p class="mb-4 text-xs text-slate-400">พบ {{ $orders->total() }} รายการตามตัวกรอง</p>
<div class="space-y-5">
@forelse($orders as $order)
<article class="rounded-2xl border border-slate-700 bg-slate-900 p-5">
    <div class="flex flex-wrap justify-between gap-3"><h2 class="font-bold">S-{{ $order->id }} · {{ $order->product_name }} / {{ $order->variant_name }} × {{ $order->quantity }}</h2><span class="text-orange-300">{{ $order->statusLabel() }} · ฿{{ number_format($order->total,2) }}</span></div>
    <p class="mt-2 text-sm text-slate-400">{{ $order->user->name }} · {{ $order->created_at->copy()->timezone('Asia/Bangkok')->format('d/m/Y H:i') }}</p>
    @if(in_array($order->status,['pending','processing']))
    <p class="mt-2 text-xs {{ $order->created_at->lte(now()->subDay()) ? 'text-amber-300' : 'text-slate-400' }}">เปิดคำสั่งซื้อ {{ $order->created_at->copy()->locale('th')->diffForHumans() }}{{ $order->created_at->lte(now()->subDay()) ? ' · เกิน 24 ชั่วโมง' : '' }}</p>
    @endif
    <p class="mt-3 break-words text-sm">ข้อมูลผู้รับ: {{ $order->recipient }}</p>
    @if(in_array($order->status,['pending','processing']))
    <form method="POST" action="{{ route('admin.service-orders.update',$order) }}" class="market-form mt-4 space-y-3">
        @csrf @method('PUT')<input type="hidden" name="expected_status" value="{{ $order->status }}">
        <label>ข้อความถึงลูกค้า / ผลการส่งมอบ / เหตุผลคืนเงิน<textarea name="delivery_note" required maxlength="5000" rows="2"></textarea></label>
        <div class="flex flex-wrap gap-3"><select name="status" aria-label="สถานะใหม่ของ S-{{ $order->id }}">@if($order->status === 'pending')<option value="processing">เริ่มดำเนินการ</option>@endif<option value="completed">ส่งมอบสำเร็จ</option><option value="refunded">คืนเงินเข้า Wallet และคืนสต๊อก</option></select><button class="market-button">บันทึกสถานะ</button></div>
    </form>
    @else<p class="mt-3 whitespace-pre-line text-sm text-slate-400">{{ $order->delivery_note }}</p>@endif
    <details class="mt-5 border-t border-slate-800 pt-4">
        <summary class="cursor-pointer text-sm text-orange-300">ประวัติการดำเนินการ ({{ $order->events->count() }})</summary>
        <ol class="mt-4 space-y-4">
            <li class="text-xs text-slate-400">{{ $order->created_at->copy()->timezone('Asia/Bangkok')->format('d/m/Y H:i:s') }} · ลูกค้าสร้างคำสั่งซื้อ</li>
            @foreach($order->events as $event)
            <li class="border-l-2 border-orange-500/40 pl-4"><p class="text-sm">{{ $labels[$event->from_status] ?? $event->from_status }} → {{ $labels[$event->to_status] ?? $event->to_status }}</p><p class="mt-1 text-xs text-slate-400">โดย {{ $event->actor_name }} · {{ $event->created_at->copy()->timezone('Asia/Bangkok')->format('d/m/Y H:i:s') }}</p><p class="mt-2 whitespace-pre-line break-words text-sm text-slate-300">{{ $event->note }}</p></li>
            @endforeach
        </ol>
        @if($order->events->isEmpty())<p class="mt-3 text-xs text-slate-500">ยังไม่มีบันทึกผู้เปลี่ยนสถานะ ระบบเริ่มเก็บบันทึกตั้งแต่เปิดใช้ฟีเจอร์นี้</p>@endif
    </details>
</article>
@empty<p class="market-empty">ไม่มีงานตรงกับตัวกรองนี้</p>@endforelse
</div>
<div class="mt-5">{{ $orders->links() }}</div>
@endsection
