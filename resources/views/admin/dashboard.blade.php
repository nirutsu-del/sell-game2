@extends('layouts.app')

@section('content')
<div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
    <div>
        <p class="text-sm font-medium text-violet-300">ADMIN PANEL</p>
        <h1 class="text-3xl font-bold">ภาพรวมร้านค้า</h1>
    </div>
    <div class="flex flex-wrap gap-3">
        <a href="{{ route('admin.reports.sales') }}" class="market-button">รายงานยอดขาย / CSV</a>
        <a href="{{ route('admin.dashboard') }}#backup" class="market-small-button">สำรองข้อมูล: php artisan store:backup</a>
        <a href="{{ route('password.edit') }}" class="market-small-button">เปลี่ยนรหัสผ่านของฉัน</a>
        <a href="{{ route('admin.contacts.index') }}" class="market-button">กล่องข้อความติดต่อ</a>
        <a href="{{ route('admin.settings.edit') }}" class="market-button">ตั้งค่าร้าน / แบนเนอร์ / QR</a>
        @if(config('store.service_catalog_enabled'))
        <a href="{{ route('admin.products.index') }}" class="market-button">สินค้าและแพ็กเกจ</a>
        <a href="{{ route('admin.service-orders.index') }}" class="market-button">คิวงานบริการ</a>
        @endif
        <a href="{{ route('admin.accounts.create') }}" class="rounded-lg bg-violet-600 px-4 py-2 font-semibold hover:bg-violet-500">+ เพิ่มไอดีเกม</a>
        <a href="{{ route('admin.gacha.index') }}" class="rounded-lg border border-purple-500/50 bg-purple-500/10 px-4 py-2 font-semibold text-purple-300 hover:bg-purple-500/20">🎁 จัดการกล่องสุ่ม</a>
        <a href="{{ route('admin.categories.index') }}" class="rounded-lg border border-slate-700 px-4 py-2 font-semibold hover:bg-slate-800">จัดการเกม</a>
        <a href="{{ route('admin.accounts.index') }}" class="rounded-lg border border-slate-700 px-4 py-2 font-semibold hover:bg-slate-800">จัดการไอดี</a>
        <a href="{{ route('admin.topups.index') }}" class="rounded-lg border border-slate-700 px-4 py-2 font-semibold hover:bg-slate-800">ตรวจสอบเติมเงิน</a>
    </div>
</div>

<div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <p class="text-xs text-slate-400 sm:col-span-2 lg:col-span-4">ยอดขายรวมขายไอดี งานบริการ และค่าสุ่ม ก่อนหักคืนเงิน/เครดิตรางวัล ไม่รวมเติม Wallet · วันนี้ใช้เวลาไทย</p>
    <div class="rounded-xl border border-slate-800 bg-slate-900 p-5"><p class="text-sm text-slate-400">ยอดขายทั้งหมด</p><p class="mt-2 text-2xl font-bold text-emerald-400">฿{{ number_format($stats['sales'], 2) }}</p></div>
    <div class="rounded-xl border border-slate-800 bg-slate-900 p-5"><p class="text-sm text-slate-400">ยอดขายวันนี้</p><p class="mt-2 text-2xl font-bold">฿{{ number_format($stats['salesToday'], 2) }}</p></div>
    <div class="rounded-xl border border-slate-800 bg-slate-900 p-5"><p class="text-sm text-slate-400">ไอดีพร้อมขาย / ขายแล้ว</p><p class="mt-2 text-2xl font-bold">{{ $stats['availableAccounts'] }} <span class="text-sm font-normal text-slate-500">/ {{ $stats['soldAccounts'] }}</span></p></div>
    <div class="rounded-xl border border-slate-800 bg-slate-900 p-5"><p class="text-sm text-slate-400">Wallet ผู้ใช้ทั้งหมด</p><p class="mt-2 text-2xl font-bold text-violet-300">฿{{ number_format($stats['walletBalance'], 2) }}</p></div>
</div>

<div class="mt-8 grid gap-6 lg:grid-cols-2">
    <section class="rounded-xl border border-slate-800 bg-slate-900 p-5">
        <div class="flex items-center justify-between"><h2 class="text-lg font-bold">คำสั่งซื้อล่าสุด</h2><span class="text-sm text-slate-400">{{ $recentPurchases->count() }} รายการ</span></div>
        <div class="mt-4 space-y-3">
            @forelse($recentPurchases as $purchase)
                <div class="flex items-center justify-between rounded-lg bg-slate-800/70 p-3"><div><p class="font-medium">{{ $purchase->account->title }}</p><p class="text-xs text-slate-400">{{ $purchase->user->name }} · {{ $purchase->created_at->copy()->timezone('Asia/Bangkok')->format('d/m/Y H:i') }}</p></div><span class="font-semibold text-emerald-400">฿{{ number_format($purchase->price_paid, 2) }}</span></div>
            @empty <p class="py-5 text-center text-slate-500">ยังไม่มีคำสั่งซื้อ</p>@endforelse
        </div>
    </section>
    <section class="rounded-xl border border-slate-800 bg-slate-900 p-5">
        <div class="flex items-center justify-between"><h2 class="text-lg font-bold">รอตรวจสอบการเติมเงิน</h2><a href="{{ route('admin.topups.index') }}" class="rounded-full bg-amber-500/15 px-2 py-1 text-xs text-amber-300">{{ $stats['pendingTopups'] }} รายการ</a></div>
        <div class="mt-4 space-y-3">
            @forelse($pendingTopups as $topup)
                <div class="flex items-center justify-between rounded-lg bg-slate-800/70 p-3"><div><p class="font-medium">{{ $topup->user->name }}</p><p class="text-xs text-slate-400">{{ $topup->reference_no }} · {{ $topup->payment_method }}</p></div><span class="font-semibold text-amber-300">฿{{ number_format($topup->amount, 2) }}</span></div>
            @empty <p class="py-5 text-center text-slate-500">ไม่มีรายการรอตรวจสอบ</p>@endforelse
        </div>
    </section>
</div>
@endsection
