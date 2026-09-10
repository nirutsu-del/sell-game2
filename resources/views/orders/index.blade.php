@extends('layouts.app')
@section('content')
<div class="market-heading"><div><p>MY ACTIVITY</p><h1 class="text-3xl font-bold">ประวัติของฉัน</h1><p class="mt-3 !tracking-normal !text-slate-400 !text-sm">คำสั่งซื้อและรางวัลจากการสุ่ม อยู่ที่นี่</p></div><a href="{{ route('user.dashboard') }}">บัญชีของฉัน →</a></div>
<nav aria-label="ประเภทประวัติ" class="mb-5 flex flex-wrap gap-2">
@foreach(['all'=>'ทั้งหมด','account'=>'ซื้อไอดี','service'=>'งานบริการ','gacha'=>'สุ่มรางวัล'] as $type=>$label)
@continue($type === 'service' && !config('store.service_catalog_enabled') && !\App\Models\ServiceOrder::where('user_id',auth()->id())->exists())
<a href="{{ route('orders.index',array_merge(request()->except(['page','type']),['type'=>$type])) }}" @if(request('type','all') === $type) aria-current="page" @endif class="rounded-xl border px-5 py-3 text-sm {{ request('type','all') === $type ? 'border-orange-500 bg-orange-500/15 text-orange-300' : 'border-slate-700 text-slate-400' }}">{{ $label }}</a>
@endforeach
</nav>
<form class="mb-6 grid gap-3 rounded-2xl border border-slate-800 bg-slate-900 p-4 sm:grid-cols-4">
    <input type="hidden" name="type" value="{{ request('type','all') }}">
    <label class="sm:col-span-2"><span class="mb-2 block text-xs text-slate-400">เลขอ้างอิงหรือชื่อสินค้า / กล่องสุ่ม</span><input name="q" value="{{ request('q') }}" maxlength="100" placeholder="เช่น S-12, A-8, G-3 หรือชื่อสินค้า" class="w-full rounded-lg bg-slate-800 p-3"></label>
    <label><span class="mb-2 block text-xs text-slate-400">สถานะ</span><select name="status" class="w-full rounded-lg bg-slate-800 p-3">@foreach([''=>'ทุกสถานะ','pending'=>'รอดำเนินการ','processing'=>'กำลังดำเนินการ','completed'=>'สำเร็จ','refunded'=>'คืนเงินแล้ว'] as $value=>$label)<option value="{{ $value }}" @selected(request('status','') === $value)>{{ $label }}</option>@endforeach</select></label>
    <div class="flex items-end gap-3"><button class="market-button">ค้นหา</button><a class="py-3 text-sm text-slate-400" href="{{ route('orders.index') }}">ล้างตัวกรอง</a></div>
</form>
<p class="mb-4 text-xs text-slate-400">{{ number_format($orders->total()) }} รายการ · เรียงใหม่ล่าสุด · ซื้อไอดีและสุ่มรางวัลแสดงเป็นสถานะสำเร็จ</p>
<div class="space-y-3">
@forelse($orders as $entry)
<article class="rounded-2xl border border-slate-800 bg-slate-900 p-5">
    <div class="flex flex-wrap justify-between gap-4">
        <div class="min-w-0"><p class="text-xs text-orange-300">{{ $entry['reference'] }} · {{ $entry['type'] }}</p><h2 class="mt-2 break-words font-bold">{{ $entry['title'] }}</h2><p class="mt-2 break-words text-sm text-slate-300">{{ $entry['detail'] }}</p><p class="mt-2 text-xs text-slate-500">{{ $entry['date']->copy()->timezone('Asia/Bangkok')->format('d/m/Y H:i') }}</p></div>
        <div class="text-right"><p class="font-bold text-orange-300">฿{{ number_format($entry['amount'],2) }}</p><p class="mt-1 text-xs text-slate-400">ยอดชำระ / ค่าสุ่ม</p><p class="mt-3 text-sm">{{ $entry['status'] }}</p>
        @if($entry['url'])<a class="mt-3 inline-block text-sm text-orange-300 underline" href="{{ $entry['url'] }}">{{ $entry['action'] }} →</a>@endif</div>
    </div>
</article>
@empty
<div class="market-empty"><p>ไม่พบประวัติที่ตรงกับตัวกรอง</p><a href="{{ route('orders.index') }}" class="mt-3 inline-block text-orange-300">ดูประวัติทั้งหมด</a></div>
@endforelse
</div>
<div class="mt-6">{{ $orders->links() }}</div>
@endsection
