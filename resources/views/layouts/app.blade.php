<!doctype html>
<html lang="th">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#0b1020"><meta name="csrf-token" content="{{ csrf_token() }}"><title>{{ $storeSettings->name }}</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="vault-app {{ request()->routeIs('shop.index') ? 'vault-home' : '' }} {{ request()->routeIs('admin.*') ? 'vault-admin' : 'vault-store' }} {{ request()->routeIs('gacha.*','admin.gacha.*') ? 'vault-gacha' : '' }} min-h-screen text-slate-100 antialiased">
<a href="#main-content" class="skip-link">ข้ามไปยังเนื้อหา</a>
@if(app()->environment('staging'))
<aside class="bg-amber-300 px-4 py-3 text-center font-bold text-slate-950" role="status">STAGING — ระบบทดสอบ ข้อมูลจำลอง ห้ามชำระเงินจริง</aside>
@endif
@if(!request()->routeIs('admin.*') && $storeSettings->announcement_enabled && filled($storeSettings->announcement))
<aside aria-label="ประกาศร้าน" class="border-b border-orange-500/30 bg-orange-500/15 px-4 py-3 text-center text-sm text-orange-200"><p class="mx-auto max-w-6xl whitespace-pre-line break-words">{{ $storeSettings->announcement }}</p></aside>
@endif
<header class="site-header">
    <div class="mx-auto max-w-6xl px-4">
        <div class="site-header-row">
        @php($wordmarkSize = ['store-settings/mizuki-vault-logo-03.png' => [1120, 248], 'store-settings/mizuki-speed-logo-02.png' => [1200, 220]][$storeSettings->logo] ?? null)
        <a class="brand" href="{{ route('shop.index') }}">@if($wordmarkSize)<img class="brand-wordmark" src="{{ asset('storage/'.$storeSettings->logo) }}" alt="{{ $storeSettings->name }}" width="{{ $wordmarkSize[0] }}" height="{{ $wordmarkSize[1] }}">@else<span class="brand-mark">@if($storeSettings->logo)<img src="{{ asset('storage/'.$storeSettings->logo) }}" alt="">@else✦@endif</span><span class="max-w-56 truncate">{{ $storeSettings->name }}</span>@endif</a>
        <nav class="main-nav" aria-label="เมนูหลัก">
            <a class="{{ request()->routeIs('shop.index') ? 'is-active' : '' }}" href="{{ route('shop.index') }}">หน้าแรก</a><a class="{{ request()->routeIs('catalog.*','products.*','accounts.*') ? 'is-active' : '' }}" href="{{ route('catalog.index') }}">สินค้า</a><a class="{{ request()->routeIs('wallet.*') ? 'is-active' : '' }}" href="{{ route('wallet.index') }}">เติมเงิน</a><a class="{{ request()->routeIs('gacha.*') ? 'is-active' : '' }}" href="{{ route('gacha.index') }}">สุ่มไอดี</a><a class="{{ request()->routeIs('contact') ? 'is-active' : '' }}" href="{{ route('contact') }}">ติดต่อ</a>
        </nav>
        @if(request()->routeIs('shop.index'))
        <form action="{{ route('catalog.index') }}" class="hero-header-search" role="search"><input name="q" aria-label="ค้นหาไอดีเกม" placeholder="ค้นหาเกมหรือไอดีที่ชอบ…" maxlength="100"><button aria-label="ค้นหา">⌕</button></form>
        @endif
        <div class="header-actions">
        @auth
            <a id="notification-bell" href="{{ route('notifications.index') }}" data-count-url="{{ route('notifications.count') }}" aria-label="แจ้งเตือน ยังไม่อ่าน {{ $notificationUnread }} รายการ" class="icon-button relative">
                <svg aria-hidden="true" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
                <span id="notification-badge" @if(!$notificationUnread) hidden @endif class="rounded-full bg-orange-600 px-1.5 py-0.5 text-[10px] font-bold text-white">{{ $notificationUnread > 99 ? '99+' : $notificationUnread }}</span>
            </a>
            @if(auth()->user()->isAdmin())<a href="{{ route('admin.dashboard') }}" class="market-small-button">หลังบ้าน</a>@else<a href="{{ route('user.dashboard') }}" class="account-link">บัญชีของฉัน</a>@endif
            <a href="{{ route('wallet.index') }}" class="wallet-chip"><span>฿</span>{{ number_format(auth()->user()->balance,2) }}</a>
            <a href="{{ route('logout.form') }}" class="logout-link">ออกจากระบบ</a>
        @else
            <a href="{{ route('login') }}" class="market-button compact">เข้าสู่ระบบ / สมัครสมาชิก</a>
        @endauth
        </div>
    </div>
        </div>
</header>
<div class="vault-workspace">
    @if(request()->routeIs('admin.*'))
    <details class="vault-admin-menu"><summary>☰ เมนูจัดการร้าน</summary>@include('partials.admin-nav')</details>
    @endif
<main id="main-content" class="mx-auto max-w-6xl px-4 py-7">
    @if(request()->routeIs('admin.*'))
        <div class="vault-admin-heading"><span>พื้นที่จัดการร้าน</span><a href="{{ route('shop.index') }}">เปิดหน้าร้าน ↗</a></div>
    @elseif(auth()->check() && !request()->routeIs('shop.index','catalog.*','products.*','accounts.*','gacha.*','contact','news.*'))
        @include('partials.account-nav')
    @endif
    @if(request()->routeIs('products.show','accounts.show','gacha.*','news.*','contact'))
    <form action="{{ route('catalog.index') }}" class="global-search mb-8">
        <span aria-hidden="true">⌕</span><input name="q" value="{{ request('q') }}" aria-label="ค้นหาทั้งร้าน" placeholder="ค้นหาไอดีเกม สินค้า หรือบริการ…" maxlength="100"><button aria-label="ค้นหา">ค้นหา</button>
    </form>
    @endif
    @if(session('success'))<div role="status" class="mb-5 rounded-xl bg-emerald-900/60 p-4 text-emerald-200">{{ session('success') }}</div>@endif
    @if($errors->any())<div role="alert" class="mb-5 rounded-xl bg-red-900/60 p-4 text-red-200">{{ $errors->first() }}</div>@endif
    @yield('content')
</main>
</div>
@unless(request()->routeIs('admin.*'))
<nav class="vault-bottom-nav" aria-label="เมนูมือถือ">
    @foreach([[route('catalog.index'),request()->routeIs('shop.index','catalog.*','accounts.*','products.*'),'⌂','ร้านไอดี'],[route('gacha.index'),request()->routeIs('gacha.*'),'✦','กล่องสุ่ม'],[route('wallet.index'),request()->routeIs('wallet.*'),'◈','Wallet'],[auth()->check() ? route('user.dashboard') : route('login'),request()->routeIs('user.*','orders.*','purchases.*','notifications.*','password.*','login'),'◎','บัญชี']] as $item)
    <a href="{{ $item[0] }}" class="{{ $item[1] ? 'is-active' : '' }}" @if($item[1]) aria-current="page" @endif><span aria-hidden="true">{{ $item[2] }}</span>{{ $item[3] }}</a>
    @endforeach
</nav>
@endunless
@if(request()->routeIs('shop.index'))
@include('partials.home-footer')
@else
<footer class="mt-12 border-t border-slate-800 px-4 py-8 text-sm text-slate-500"><div class="mx-auto flex max-w-6xl flex-wrap justify-between gap-4"><p>✦ {{ $storeSettings->name }} · ร้านค้าเกมของคุณ</p><div class="flex gap-5"><a href="{{ route('news.index') }}">ข่าวสาร</a><a href="{{ route('orders.index') }}">ประวัติคำสั่งซื้อ</a><a href="{{ route('contact') }}">ติดต่อร้าน</a></div></div></footer>
@endif
@include('partials.store-contact')
<p class="px-4 pb-5 text-center text-xs text-slate-500">วันและเวลาที่แสดงใช้เวลาไทย (Asia/Bangkok · UTC+7)</p>
</body></html>
