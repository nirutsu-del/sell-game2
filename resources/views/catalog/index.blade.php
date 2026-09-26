@extends('layouts.app')
@section('content')
<nav aria-label="เส้นทางหมวดหมู่" class="mb-6 flex flex-wrap gap-2 text-sm text-slate-400"><a href="{{ route('shop.index') }}">หน้าแรก</a><span>/</span><a href="{{ route('catalog.index') }}">สินค้าทั้งหมด</a>@foreach($breadcrumbs as $crumb)<span>/</span><a href="{{ route('catalog.category',$crumb) }}">{{ $crumb->name }}</a>@endforeach</nav>
<div class="market-heading"><div><p>SELECT YOUR GAME</p><h1 class="text-3xl font-black">{{ $category?->name ?? 'ไอดีเกมทั้งหมด' }}</h1></div></div>
@include('catalog.partials.categories')
<form class="catalog-filters" method="get" action="{{ $category ? route('catalog.category',$category) : route('catalog.index') }}" role="search" aria-label="ค้นหาและกรองสินค้า">
    @if(request()->filled('q') || request('sort', 'newest') !== 'newest' || request('available'))
        <div class="catalog-filters-heading">
            <a class="catalog-filters-reset" href="{{ $category ? route('catalog.category',$category) : route('catalog.index') }}">ล้างตัวกรอง <span aria-hidden="true">↺</span></a>
        </div>
    @endif
    <div class="catalog-filters-fields">
        <label class="catalog-filter-search">
            <span class="catalog-filter-label">{{ $category ? 'ค้นหาในหมวดนี้' : 'ค้นหาสินค้าทั้งหมด' }}</span>
            <span class="catalog-filter-input">
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 4 4"/></svg>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="ชื่อสินค้า หรือไอดีเกม…" aria-label="ค้นหาสินค้า">
            </span>
        </label>
        <label class="catalog-filter-sort">
            <span class="catalog-filter-label">เรียงตาม</span>
            <select name="sort" aria-label="เรียงสินค้า">@foreach(['newest'=>'สินค้าใหม่ล่าสุด','price_asc'=>'ราคาต่ำไปสูง','price_desc'=>'ราคาสูงไปต่ำ'] as $value=>$label)<option value="{{ $value }}" @selected(request('sort','newest') === $value)>{{ $label }}</option>@endforeach</select>
        </label>
        <label class="catalog-filter-availability">
            <input type="checkbox" role="switch" name="available" value="1" @checked(request('available'))>
            <span class="catalog-filter-switch" aria-hidden="true"></span>
            <span>เฉพาะพร้อมสั่งซื้อ</span>
        </label>
        <button type="submit" class="market-button catalog-filter-submit">ค้นหาสินค้า <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 12h14m-6-6 6 6-6 6"/></svg></button>
    </div>
</form>
@if(config('store.service_catalog_enabled'))
<h2 class="mb-4 text-xl font-bold">สินค้าและบริการ</h2>
@include('catalog.partials.products')
@if($products->isEmpty())<p class="market-empty">ไม่พบสินค้าและบริการในรายการนี้</p>@endif
<div class="my-5">{{ $products->links() }}</div>
@endif
<h2 class="mb-4 mt-8 text-xl font-bold">ไอดีเกมพร้อมส่ง</h2>
@include('catalog.partials.accounts')
@if($accounts->isEmpty())<p class="market-empty">ไม่พบไอดีพร้อมขายในรายการนี้</p>@endif
<div class="my-5">{{ $accounts->links() }}</div>
@endsection
