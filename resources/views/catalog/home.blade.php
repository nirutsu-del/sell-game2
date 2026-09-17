@extends('layouts.app')
@section('content')
<div class="space-y-10">
    <div class="vault-hero-grid"><section class="market-hero {{ $banners->isNotEmpty() ? 'has-store-banner' : '' }}">
        @if($banners->isNotEmpty())
        <div id="store-banner-track" class="market-hero-banner-track" aria-label="แบนเนอร์ร้านค้า" tabindex="0">
            @foreach($banners as $banner)
            <div class="market-hero-banner-slide" data-store-banner>
                @if($banner->link)<a href="{{ $banner->link }}" aria-label="{{ $banner->title }}">@endif
                <img src="{{ asset('storage/'.$banner->image) }}" alt="{{ $banner->title }}" @if(!$loop->first) loading="lazy" @endif>
                @if($banner->link)</a>@endif
            </div>
            @endforeach
        </div>
        <div class="market-hero-banner-shade" aria-hidden="true"></div>
        @endif
        <div class="market-hero-copy"><p class="break-words text-xs font-bold tracking-[.25em] text-orange-300">{{ $settings->name }} / GAME STORE</p>
        <h1 class="mt-5 text-4xl font-black leading-tight sm:text-6xl">ไอดีที่ใช่<br><span class="text-orange-400">เกมที่คุณรัก</span></h1>
        <p class="mt-5 max-w-md whitespace-pre-line break-words text-sm leading-7 text-slate-300">{{ $settings->description }}</p>
        <div class="mt-7 flex flex-wrap gap-3"><a class="market-button hero-primary-action" href="{{ route('catalog.index') }}">เลือกไอดีเกม <span aria-hidden="true">↗</span></a></div>
        <p class="hero-signature"><span aria-hidden="true"></span>YOUR NEXT GAME STARTS HERE</p></div>
        @if($banners->count() > 1)<div class="market-hero-banner-controls"><button type="button" data-banner-direction="-1" aria-controls="store-banner-track" aria-label="แบนเนอร์ก่อนหน้า">←</button><button type="button" data-banner-direction="1" aria-controls="store-banner-track" aria-label="แบนเนอร์ถัดไป">→</button></div>@endif
        @if($banners->isEmpty())<div class="market-hero-art" aria-hidden="true"><div class="market-ring"></div><div class="market-controller">✦<span>PLAY<br>YOUR WAY.</span></div><div class="market-orb">+</div></div>@endif
    </section></div>
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach([[route('catalog.index'),'🎮','สินค้าทั้งหมด','เลือกเกมที่คุณชอบ'],[route('wallet.index'),'◈','เติมเงิน','PromptPay / TrueMoney'],[route('orders.index'),'▤','ประวัติคำสั่งซื้อ','ติดตามรายการของคุณ'],[route('contact'),'☏','ติดต่อร้าน','สอบถามก่อนสั่งซื้อ']] as $shortcut)
        <a class="market-shortcut" href="{{ $shortcut[0] }}"><span class="text-2xl text-orange-400">{{ $shortcut[1] }}</span><div><h2 class="font-bold">{{ $shortcut[2] }}</h2><p class="mt-1 text-xs text-slate-400">{{ $shortcut[3] }}</p></div></a>
        @endforeach
    </div>
    <section class="game-selection-section"><div class="market-heading"><div><p>CHOOSE YOUR WORLD</p><h2>เลือกเกมของคุณ</h2></div><a href="{{ route('catalog.index') }}">ดูเกมทั้งหมด →</a></div>
        @include('catalog.partials.categories')
        @if($categories->isEmpty())<p class="market-empty">ร้านกำลังเตรียมหมวดหมู่สินค้า</p>@endif
    </section>
    @if($products->isNotEmpty())<section><div class="market-heading"><div><p>GAME SERVICES</p><h2>สินค้าและบริการแนะนำ</h2></div><a href="{{ route('catalog.index') }}">ดูทั้งหมด →</a></div>@include('catalog.partials.products')</section>@endif
    <section class="featured-accounts-section"><div class="market-heading"><div><p>READY TO PLAY</p><h2>ไอดีเด่นพร้อมซื้อ</h2></div><a href="{{ route('accounts.index') }}">ดูไอดีทั้งหมด →</a></div>@include('catalog.partials.accounts')@if($accounts->isEmpty())<p class="market-empty">ยังไม่มีไอดีพร้อมขายในขณะนี้</p>@endif</section>
    @include('catalog.partials.gacha-feature')
    <section class="home-howto" aria-labelledby="home-howto-title">
        <div class="market-heading"><div><p>YOUR NEXT GAME STARTS HERE</p><h2 id="home-howto-title">ไอดีใหม่ เริ่มได้ใน 3 ขั้นตอน</h2></div></div>
        <ol class="home-howto-grid">
            <li><div class="home-howto-top"><span class="home-howto-number">01</span><span aria-hidden="true">⌕</span></div><h3>เลือกไอดีที่ใช่</h3><p>เลือกเกมที่ชอบ แล้วตรวจสอบรูปภาพ ราคา และรายละเอียดไอดีก่อนซื้อ</p><a href="{{ route('catalog.index') }}">ค้นหาไอดีเกม <span aria-hidden="true">↗</span></a></li>
            <li><div class="home-howto-top"><span class="home-howto-number">02</span><span aria-hidden="true">◈</span></div><h3>เตรียมยอดใน Wallet</h3><p>เข้าสู่ระบบ เลือกช่องทางเติมเงิน และทำตามคำแนะนำในหน้า Wallet</p><a href="{{ route('wallet.index') }}">ไปหน้าเติมเงิน <span aria-hidden="true">↗</span></a></li>
            <li><div class="home-howto-top"><span class="home-howto-number">03</span><span aria-hidden="true">✓</span></div><h3>ยืนยันซื้อและรับไอดี</h3><p>กลับมายืนยันซื้อไอดีที่เลือก แล้วดูข้อมูลการรับไอดีและรายการซื้อในบัญชี</p><a href="{{ route('user.dashboard') }}">ไปบัญชีของฉัน <span aria-hidden="true">↗</span></a></li>
        </ol>
        <div class="home-howto-help"><div><strong>ยังมีคำถามก่อนเลือกไอดี?</strong><p>สอบถามรายละเอียดสินค้า หรือแจ้งเลขอ้างอิงให้ร้านช่วยตรวจสอบรายการซื้อ</p></div><a href="{{ route('contact') }}">พูดคุยกับร้าน <span aria-hidden="true">→</span></a></div>
    </section>
</div>
@endsection
