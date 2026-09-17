@extends('layouts.app')
@section('content')
<nav aria-label="เส้นทางหมวดหมู่" class="mb-6 flex flex-wrap gap-2 text-sm text-slate-400"><a href="{{ route('shop.index') }}">หน้าแรก</a><span>/</span><a href="{{ route('catalog.index') }}">สินค้าทั้งหมด</a>@foreach($breadcrumbs as $crumb)<span>/</span><a href="{{ route('catalog.category',$crumb) }}">{{ $crumb->name }}</a>@endforeach</nav>
<div class="market-heading"><div><p>SELECT YOUR GAME</p><h1 class="text-3xl font-black">{{ $category?->name ?? 'ไอดีเกมทั้งหมด' }}</h1></div></div>
@include('catalog.partials.categories')
<form class="catalog-filters my-8 grid gap-3 rounded-2xl border border-slate-800 bg-slate-900 p-4 sm:grid-cols-4">
    <label class="sm:col-span-2"><span class="mb-2 block text-xs text-slate-400">ค้นหาในหมวดนี้</span><input aria-label="ค้นหาสินค้า" name="q" value="{{ request('q') }}" placeholder="ชื่อสินค้า หรือไอดี…" class="w-full rounded-lg bg-slate-800 p-3"></label>
    <label><span class="mb-2 block text-xs text-slate-400">เรียงตาม</span><select aria-label="เรียงสินค้า" name="sort" class="w-full rounded-lg bg-slate-800 p-3">@foreach(['newest'=>'สินค้าใหม่ล่าสุด','price_asc'=>'ราคาต่ำไปสูง','price_desc'=>'ราคาสูงไปต่ำ'] as $value=>$label)<option value="{{ $value }}" @selected(request('sort','newest') === $value)>{{ $label }}</option>@endforeach</select></label>
    <label class="flex items-end gap-2 pb-3 text-sm"><input type="checkbox" name="available" value="1" @checked(request('available'))>เฉพาะพร้อมสั่งซื้อ</label>
    <div class="flex items-end gap-3 sm:col-span-4"><button class="market-button">ใช้ตัวกรอง</button>@if(request()->hasAny(['q','sort','available']))<a href="{{ $category ? route('catalog.category',$category) : route('catalog.index') }}" class="py-3 text-sm text-slate-400 underline">ล้างตัวกรอง</a>@endif</div>
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
