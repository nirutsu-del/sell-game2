@extends('layouts.app')

@section('content')
<div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
    <div>
        <p class="text-sm font-medium text-violet-300">บัญชีของฉัน</p>
        <h1 class="text-3xl font-bold">สวัสดี, {{ auth()->user()->name }}</h1>
    </div>
    <a href="{{ route('wallet.index') }}" class="rounded-lg bg-violet-600 px-4 py-3 font-semibold hover:bg-violet-500">Wallet ฿{{ number_format(auth()->user()->balance, 2) }}</a>
</div>

<div class="mt-8 grid gap-6 lg:grid-cols-2">
    <a href="{{ route('password.edit') }}" class="market-shortcut lg:col-span-2">เปลี่ยนรหัสผ่าน →</a>
    <a href="{{ route('orders.index') }}" class="market-shortcut lg:col-span-2">▤ ประวัติทั้งหมด · ซื้อไอดี / สุ่มรางวัล →</a>
    <section class="rounded-xl border border-slate-800 bg-slate-900 p-5">
        <div class="flex items-center justify-between"><h2 class="text-lg font-bold">ไอดีที่ซื้อ</h2><a href="{{ route('shop.index') }}" class="text-sm text-violet-300">เลือกซื้อเพิ่ม</a></div>
        <div class="mt-4 space-y-3">
            @forelse($purchases as $purchase)
                <a href="{{ route('purchases.show', $purchase) }}" class="flex items-center justify-between rounded-lg bg-slate-800/70 p-3 hover:bg-slate-800"><div><p class="font-medium">{{ $purchase->account->title }}</p><p class="text-xs text-slate-400">{{ $purchase->created_at->copy()->timezone('Asia/Bangkok')->format('d/m/Y H:i') }}</p></div><span class="text-sm text-violet-300">ดูข้อมูล →</span></a>
            @empty <p class="py-5 text-center text-slate-500">คุณยังไม่มีไอดีที่ซื้อ</p>@endforelse
        </div>
    </section>
    <section class="rounded-xl border border-slate-800 bg-slate-900 p-5">
        <div class="flex items-center justify-between"><h2 class="text-lg font-bold">ประวัติเติมเงิน</h2><a href="{{ route('wallet.index') }}" class="text-sm text-violet-300">เติมเงิน</a></div>
        <div class="mt-4 space-y-3">
            @forelse($topups as $topup)
                <div class="flex items-center justify-between rounded-lg bg-slate-800/70 p-3"><div><p class="font-medium">฿{{ number_format($topup->amount, 2) }}</p><p class="text-xs text-slate-400">{{ $topup->created_at->copy()->timezone('Asia/Bangkok')->format('d/m/Y H:i') }}</p></div><span class="rounded-full px-2 py-1 text-xs {{ $topup->status === 'success' ? 'bg-emerald-500/15 text-emerald-300' : ($topup->status === 'failed' ? 'bg-red-500/15 text-red-300' : 'bg-amber-500/15 text-amber-300') }}">{{ $topup->status }}</span></div>
            @empty <p class="py-5 text-center text-slate-500">ยังไม่มีประวัติเติมเงิน</p>@endforelse
        </div>
    </section>
</div>
@endsection
