<!doctype html>
<html lang="th">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><title>{{ $storeSettings->name }}</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="min-h-screen bg-slate-950 text-slate-100">
@if(app()->environment('staging'))
<aside class="bg-amber-300 px-4 py-3 text-center font-bold text-slate-950" role="status">STAGING — ระบบทดสอบ ข้อมูลจำลอง ห้ามชำระเงินจริง</aside>
@endif
@if(!request()->routeIs('admin.*') && $storeSettings->announcement_enabled && filled($storeSettings->announcement))
<aside aria-label="ประกาศร้าน" class="border-b border-orange-500/30 bg-orange-500/15 px-4 py-3 text-center text-sm text-orange-200"><p class="mx-auto max-w-6xl whitespace-pre-line break-words">{{ $storeSettings->announcement }}</p></aside>
@endif
<header class="border-b border-slate-800 bg-slate-900">
    <div class="mx-auto flex max-w-6xl flex-wrap items-center gap-4 px-4 py-4">
        <a class="inline-flex max-w-full items-center gap-2 text-xl font-black tracking-tight text-orange-400" href="{{ route('shop.index') }}">@if($storeSettings->logo)<img src="{{ asset('storage/'.$storeSettings->logo) }}" alt="" class="h-9 w-9 rounded object-contain">@else<span aria-hidden="true">✦</span>@endif<span class="max-w-56 truncate">{{ $storeSettings->name }}</span></a>
        <nav class="order-3 flex w-full gap-5 overflow-x-auto whitespace-nowrap border-t border-slate-800 pt-3 text-sm lg:order-none lg:w-auto lg:border-0 lg:pt-0" aria-label="เมนูหลัก">
            <a href="{{ route('shop.index') }}">หน้าแรก</a><a href="{{ route('catalog.index') }}">สินค้าทั้งหมด</a><a href="{{ route('wallet.index') }}">เติมเงิน</a><a href="{{ route('gacha.index') }}">สุ่มไอดี</a><a href="{{ route('contact') }}">ติดต่อเรา</a>
        </nav>
        <div class="ml-auto flex flex-wrap items-center gap-3 text-xs">
        @auth
            <a id="notification-bell" href="{{ route('notifications.index') }}" data-count-url="{{ route('notifications.count') }}" aria-label="แจ้งเตือน ยังไม่อ่าน {{ $notificationUnread }} รายการ" class="relative inline-flex items-center gap-1 rounded-lg border border-slate-700 px-2 py-2 text-orange-300">
                <svg aria-hidden="true" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
                <span id="notification-badge" @if(!$notificationUnread) hidden @endif class="rounded-full bg-orange-600 px-1.5 py-0.5 text-[10px] font-bold text-white">{{ $notificationUnread > 99 ? '99+' : $notificationUnread }}</span>
            </a>
            @if(auth()->user()->isAdmin())<a href="{{ route('admin.dashboard') }}" class="market-small-button">หลังบ้าน</a>@else<a href="{{ route('user.dashboard') }}">บัญชีของฉัน</a>@endif
            <a href="{{ route('wallet.index') }}" class="text-orange-300">Wallet ฿{{ number_format(auth()->user()->balance,2) }}</a>
            <a href="{{ route('logout.form') }}" class="text-slate-400">ออกจากระบบ</a>
        @else
            <a href="{{ route('login') }}" class="market-small-button">เข้าสู่ระบบ / สมัครสมาชิก</a>
        @endauth
        </div>
    </div>
</header>
<main class="mx-auto max-w-6xl px-4 py-7">
    <form action="{{ route('catalog.index') }}" class="mb-7 flex max-w-md gap-2 rounded-full border border-slate-800 bg-slate-900 px-4 py-2">
        <input name="q" value="{{ request('q') }}" aria-label="ค้นหาทั้งร้าน" placeholder="ค้นหาไอดีเกม…" maxlength="100" class="min-w-0 flex-1 bg-transparent text-sm outline-none"><button aria-label="ค้นหา" class="text-orange-400">ค้นหา ↗</button>
    </form>
    @if(session('success'))<div role="status" class="mb-5 rounded-xl bg-emerald-900/60 p-4 text-emerald-200">{{ session('success') }}</div>@endif
    @if($errors->any())<div role="alert" class="mb-5 rounded-xl bg-red-900/60 p-4 text-red-200">{{ $errors->first() }}</div>@endif
    @yield('content')
</main>
<footer class="mt-12 border-t border-slate-800 px-4 py-8 text-sm text-slate-500"><div class="mx-auto flex max-w-6xl flex-wrap justify-between gap-4"><p>✦ {{ $storeSettings->name }} · ร้านค้าเกมของคุณ</p><div class="flex gap-5"><a href="{{ route('news.index') }}">ข่าวสาร</a><a href="{{ route('orders.index') }}">ประวัติคำสั่งซื้อ</a><a href="{{ route('contact') }}">ติดต่อร้าน</a></div></div></footer>
@include('partials.store-contact')
<p class="px-4 pb-5 text-center text-xs text-slate-500">วันและเวลาที่แสดงใช้เวลาไทย (Asia/Bangkok · UTC+7)</p>
</body></html>
