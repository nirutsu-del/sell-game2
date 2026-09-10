@extends('layouts.app')
@section('content')
<nav class="mb-6 text-sm text-slate-400"><a href="{{ route('catalog.index') }}">สินค้าทั้งหมด</a> / <a href="{{ route('catalog.category',$product->category) }}">{{ $product->category->name }}</a> / {{ $product->name }}</nav>
<div class="grid gap-8 lg:grid-cols-2">
    <div>@if($product->image)<a href="{{ asset('storage/'.$product->image) }}" target="_blank" rel="noopener"><img src="{{ asset('storage/'.$product->image) }}" alt="{{ $product->name }}" class="aspect-square w-full rounded-3xl bg-slate-900 object-contain"><p class="mt-2 text-center text-xs text-slate-400">แตะเพื่อขยายรูปภาพ</p></a>@else<div class="market-product-art rounded-3xl" style="height:380px"><span>🎮</span></div>@endif</div>
    <div><p class="text-xs tracking-widest text-orange-300">GAME SERVICE</p><h1 class="mt-3 text-3xl font-black">{{ $product->name }}</h1>
        <p class="mt-4 whitespace-pre-line text-sm leading-7 text-slate-300">{{ $product->description }}</p>
        <form id="product-order-form" method="POST" action="{{ route('orders.store',$product) }}" class="mt-6 space-y-5">
            @csrf<input type="hidden" name="request_id" value="{{ old('request_id',(string) Str::uuid()) }}">
            <fieldset><legend class="mb-3 font-bold">เลือกแพ็กเกจ</legend><div class="grid grid-cols-2 gap-3">
            @foreach($product->variants as $variant)
                <label class="market-variant"><input type="radio" name="variant_id" value="{{ $variant->id }}" data-price="{{ $variant->price }}" data-name="{{ $variant->name }}" data-stock="{{ $variant->stock ?? '' }}" @checked(old('variant_id') == $variant->id) @disabled($variant->stock === 0) required>
                <span><strong class="block">{{ $variant->name }}</strong><span class="text-orange-300">฿{{ number_format($variant->price,2) }}</span><small class="block text-slate-400">{{ $variant->stock === null ? 'เปิดรับคำสั่งซื้อ' : 'เหลือ '.$variant->stock.' ชิ้น' }}</small></span></label>
            @endforeach</div></fieldset>
            <label class="block"><span class="mb-2 block text-sm">{{ $product->recipient_label }}</span><input name="recipient" value="{{ old('recipient') }}" required maxlength="500" class="w-full rounded-lg bg-slate-800 p-3" placeholder="ตรวจสอบข้อมูลให้ถูกต้องก่อนสั่งซื้อ"></label>
            <label class="block"><span class="mb-2 block text-sm">จำนวน</span><input id="product-quantity" name="quantity" type="number" min="1" max="100" value="{{ old('quantity',1) }}" required class="w-28 rounded-lg bg-slate-800 p-3"></label>
            <details class="rounded-xl border border-slate-700 p-4" open><summary class="cursor-pointer font-bold">เงื่อนไขและการส่งมอบ</summary><p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-400">{{ $product->terms ?: 'ร้านจะดำเนินการตามข้อมูลที่ระบุในคำสั่งซื้อ คุณสามารถติดตามสถานะได้ในประวัติคำสั่งซื้อ' }}</p></details>
            <label class="flex gap-2 text-sm text-slate-300"><input type="checkbox" name="accept_terms" value="1" required>ฉันตรวจสอบข้อมูลผู้รับและอ่านเงื่อนไขแล้ว</label>
            <div class="flex items-center justify-between"><span>ราคารวม</span><strong id="product-total" class="text-2xl text-orange-400">เลือกแพ็กเกจ</strong></div>
            @auth
            <p class="text-sm text-slate-400">Wallet ฿{{ number_format(auth()->user()->balance,2) }} · <a class="text-orange-300 underline" href="{{ route('wallet.index') }}">เติมเงิน</a></p>
            <button class="market-button w-full" @disabled(!$product->variants->contains(fn($v)=>$v->stock === null || $v->stock > 0))>ตรวจสอบคำสั่งซื้อ</button>
            @else<a href="{{ route('login') }}" class="market-button flex justify-center">เข้าสู่ระบบเพื่อสั่งซื้อ</a>@endauth
        </form>
    </div>
</div>
<dialog id="product-confirm" class="gacha-result"><h2 class="text-xl font-bold">ยืนยันคำสั่งซื้อ</h2><p class="mt-3">{{ $product->name }}</p><p id="product-summary" class="mt-3 text-orange-300"></p><p id="product-recipient" class="mt-2 break-words text-sm"></p><p class="my-4 text-xs text-slate-400">เมื่อยืนยัน ระบบจะหักเงินจาก Wallet และส่งรายการให้ร้าน</p><button id="product-pay" class="market-button w-full">ยืนยันและชำระเงิน</button><button id="product-cancel" class="mt-4 text-sm text-slate-300">กลับไปแก้ไข</button></dialog>
@endsection
