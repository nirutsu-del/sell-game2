@extends('layouts.app')
@section('content')
<div class="space-y-10">
    @if($banners->isNotEmpty())
    <section aria-label="แบนเนอร์ร้านค้า">
        <div id="store-banner-track" class="flex snap-x snap-mandatory gap-4 overflow-x-auto rounded-2xl" tabindex="0">
            @foreach($banners as $banner)
            <div class="w-full shrink-0 snap-center" data-store-banner>
                @if($banner->link)<a href="{{ $banner->link }}" class="block">@endif
                <img src="{{ asset('storage/'.$banner->image) }}" alt="{{ $banner->title }}" class="aspect-[3/1] w-full rounded-2xl bg-slate-900 object-contain" @if(!$loop->first) loading="lazy" @endif>
                @if($banner->link)</a>@endif
            </div>
            @endforeach
        </div>
        @if($banners->count() > 1)<div class="mt-3 flex items-center justify-end gap-3"><p class="mr-auto text-xs text-slate-400">เลื่อนเพื่อดูแบนเนอร์ทั้งหมด</p><button type="button" data-banner-direction="-1" aria-controls="store-banner-track" class="market-small-button">← ก่อนหน้า</button><button type="button" data-banner-direction="1" aria-controls="store-banner-track" class="market-small-button">ถัดไป →</button></div>@endif
    </section>
    @endif
    <section class="market-hero">
        <div class="market-hero-copy"><p class="break-words text-xs font-bold tracking-[.25em] text-orange-300">{{ $settings->name }} / GAME STORE</p>
        <h1 class="mt-5 text-4xl font-black leading-tight sm:text-6xl">โลกเกมของคุณ<br><span class="text-orange-400">เริ่มต้นที่นี่</span></h1>
        <p class="mt-5 max-w-md whitespace-pre-line break-words text-sm leading-7 text-slate-300">{{ $settings->description }}</p>
        <div class="mt-7 flex flex-wrap gap-3"><a class="market-button" href="{{ route('catalog.index') }}">เลือกซื้อสินค้าทั้งหมด ↗</a><a class="rounded-xl border border-white/20 px-5 py-3" href="{{ route('gacha.index') }}">เข้าห้องสุ่ม ✦</a></div></div>
        <div class="market-hero-art" aria-hidden="true"><div class="market-ring"></div><div class="market-controller">✦<span>PLAY<br>YOUR WAY.</span></div><div class="market-orb">+</div></div>
    </section>
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach([[route('catalog.index'),'🎮','สินค้าทั้งหมด','เลือกเกมที่คุณชอบ'],[route('wallet.index'),'◈','เติมเงิน','PromptPay / TrueMoney'],[route('orders.index'),'▤','ประวัติคำสั่งซื้อ','ติดตามรายการของคุณ'],[route('contact'),'☏','ติดต่อร้าน','สอบถามก่อนสั่งซื้อ']] as $shortcut)
        <a class="market-shortcut" href="{{ $shortcut[0] }}"><span class="text-2xl text-orange-400">{{ $shortcut[1] }}</span><div><h2 class="font-bold">{{ $shortcut[2] }}</h2><p class="mt-1 text-xs text-slate-400">{{ $shortcut[3] }}</p></div></a>
        @endforeach
    </div>
    <div class="grid grid-cols-3 rounded-2xl border border-slate-800 bg-slate-900/70 py-5 text-center">
        @foreach(['สมาชิกทั้งหมด','สินค้าที่เปิดขาย','รายการส่งมอบแล้ว'] as $label)<div><strong class="text-2xl text-orange-300">{{ number_format($stats[$loop->index]) }}</strong><p class="mt-1 text-xs text-slate-400">{{ $label }}</p></div>@endforeach
    </div>
    <section><div class="market-heading"><div><p>EXPLORE CATEGORIES</p><h2>หมวดหมู่แนะนำสำหรับคุณ</h2></div><a href="{{ route('catalog.index') }}">ดูทั้งหมด →</a></div>
        @include('catalog.partials.categories')
        @if($categories->isEmpty())<p class="market-empty">ร้านกำลังเตรียมหมวดหมู่สินค้า</p>@endif
    </section>
    @if($products->isNotEmpty())<section><div class="market-heading"><div><p>GAME SERVICES</p><h2>สินค้าและบริการแนะนำ</h2></div><a href="{{ route('catalog.index') }}">ดูทั้งหมด →</a></div>@include('catalog.partials.products')</section>@endif
    <section><div class="market-heading"><div><p>READY TO PLAY</p><h2>ไอดีเกมพร้อมส่ง</h2></div><a href="{{ route('accounts.index') }}">ดูทั้งหมด →</a></div>@include('catalog.partials.accounts')@if($accounts->isEmpty())<p class="market-empty">ยังไม่มีไอดีพร้อมขายในขณะนี้</p>@endif</section>
</div>
@endsection
