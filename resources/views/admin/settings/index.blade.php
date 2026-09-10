@extends('layouts.app')
@section('content')
<div class="market-heading"><div><p>STORE SETTINGS</p><h1 class="text-3xl font-bold">ตั้งค่าร้านค้า</h1></div><a href="{{ route('shop.index') }}" target="_blank" rel="noopener">ดูหน้าร้าน ↗</a></div>
<p class="mb-6 text-sm text-slate-400">รองรับรูป JPG, PNG และ WEBP ขนาดไม่เกิน 5 MB ต่อรูป การไม่เลือกรูปใหม่จะคงรูปเดิม</p>
<form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="market-form space-y-6">
    @csrf @method('PUT')
    <section class="rounded-2xl border border-slate-700 bg-slate-900 p-5">
        <h2 class="mb-5 text-xl font-bold">ข้อมูลร้าน</h2>
        <div class="grid gap-5 md:grid-cols-2">
            <label>ชื่อร้าน<input name="name" value="{{ old('name',$settings->name) }}" maxlength="100" required></label>
            <label>โลโก้ร้าน<input type="file" name="logo" accept="image/png,image/jpeg,image/webp" data-image-preview="logo-preview"></label>
        </div>
        <img id="logo-preview" @if($settings->logo) src="{{ asset('storage/'.$settings->logo) }}" @else hidden @endif alt="ตัวอย่างโลโก้" class="my-4 max-h-24 max-w-full rounded-lg object-contain">
        <label class="mt-5">ข้อความแนะนำร้าน<textarea name="description" maxlength="1000" rows="3">{{ old('description',$settings->description) }}</textarea></label>
    </section>
    <section class="rounded-2xl border border-slate-700 bg-slate-900 p-5">
        <h2 class="text-xl font-bold">QR และคำแนะนำการเติมเงิน</h2><p class="mt-2 text-xs text-slate-400">ใช้ QR จากบัญชีรับเงินของร้าน ตรวจชื่อผู้รับในแอปธนาคารก่อนบันทึก รูปเดิมที่ตั้งไว้ยังใช้งานจนกว่าจะอัปโหลดรูปใหม่</p>
        <div class="mt-5 grid gap-6 md:grid-cols-2">
        @foreach(['promptpay'=>'PromptPay','truemoney'=>'TrueMoney'] as $method=>$label)
            <div><h3 class="mb-3 font-bold text-orange-300">{{ $label }}</h3>
                <img id="{{ $method }}-preview" @if($settings->qrUrl($method)) src="{{ $settings->qrUrl($method) }}" @else hidden @endif alt="QR {{ $label }}" class="mb-4 h-48 max-w-full rounded-xl bg-white p-2 object-contain">
                <label>รูป QR ใหม่<input type="file" name="{{ $method }}_qr" accept="image/png,image/jpeg,image/webp" data-image-preview="{{ $method }}-preview"></label>
                <label class="mt-4">คำแนะนำการชำระเงิน<textarea name="{{ $method }}_instructions" maxlength="2000" rows="3">{{ old($method.'_instructions',$settings->getAttribute($method.'_instructions')) }}</textarea></label>
            </div>
        @endforeach
        </div>
    </section>
    <button class="market-button">บันทึกข้อมูลร้านและ QR</button>
</form>
<form method="POST" action="{{ route('admin.settings.contact.update') }}" class="market-form mt-10 space-y-5">
    @csrf @method('PUT')
    <section class="rounded-2xl border border-slate-700 bg-slate-900 p-5">
        <h2 class="text-xl font-bold">ช่องทางติดต่อร้าน</h2>
        <p class="mt-2 text-sm text-slate-400">ใส่ลิงก์เต็มที่ขึ้นต้นด้วย https:// ช่องที่เว้นว่างจะไม่แสดงบนหน้าร้าน</p>
        <div class="mt-5 space-y-4">
            @foreach(['facebook'=>'Facebook','line'=>'LINE','discord'=>'Discord'] as $key=>$label)
            <label>{{ $label }}<input type="url" name="{{ $key }}_url" value="{{ old($key.'_url',$settings->getAttribute($key.'_url')) }}" maxlength="2048" placeholder="https://..."></label>
            @endforeach
            <label>เวลาทำการ / เวลาตอบข้อความ<textarea name="opening_hours" rows="2" maxlength="300" placeholder="เช่น ทุกวัน 10:00–22:00 น.">{{ old('opening_hours',$settings->opening_hours) }}</textarea></label>
            <input type="hidden" name="floating_contact_enabled" value="0">
            <label><input type="checkbox" name="floating_contact_enabled" value="1" @checked(old('floating_contact_enabled',$settings->floating_contact_enabled))> แสดงปุ่มติดต่อแบบลอยบนหน้าร้าน</label>
            <p class="text-xs text-slate-400">ปุ่มจะปรากฏเมื่อเปิดใช้งานและมีลิงก์อย่างน้อยหนึ่งช่องทาง</p>
        </div>
    </section>
    <section class="rounded-2xl border border-slate-700 bg-slate-900 p-5">
        <h2 class="text-xl font-bold">ประกาศร้าน</h2>
        <p class="mt-2 text-sm text-slate-400">แสดงแถบประกาศด้านบนหน้าร้าน เช่น โปรโมชัน เวลาทำการ หรือแจ้งหยุดรับงาน ข้อความนี้ไม่ปิดระบบซื้อสินค้า</p>
        <label class="mt-4">ข้อความประกาศ<textarea name="announcement" rows="3" maxlength="1000">{{ old('announcement',$settings->announcement) }}</textarea></label>
        <input type="hidden" name="announcement_enabled" value="0">
        <label class="mt-4"><input type="checkbox" name="announcement_enabled" value="1" @checked(old('announcement_enabled',$settings->announcement_enabled))> เปิดแสดงประกาศ</label>
    </section>
    <button class="market-button">บันทึกช่องทางติดต่อและประกาศ</button>
</form>
<section class="mt-12">
    <h2 class="text-2xl font-bold">แบนเนอร์หน้าแรก</h2><p class="mt-2 text-sm text-slate-400">เลขลำดับน้อยแสดงก่อน ปิดแบนเนอร์เพื่อซ่อนจากหน้าร้านโดยเก็บข้อมูลไว้ แนะนำภาพแนวนอนอัตราส่วนประมาณ 3:1</p>
    <details class="mt-5 rounded-2xl border border-orange-500/40 bg-slate-900 p-5" @if(old('banner_form') === 'new') open @endif>
        <summary class="cursor-pointer font-bold text-orange-300">+ เพิ่มแบนเนอร์</summary>
        @include('admin.settings.banner-form',['banner'=>new \App\Models\StoreBanner(),'formKey'=>'new'])
    </details>
    <div class="mt-5 space-y-5">
        @foreach($banners as $banner)
        <details class="rounded-2xl border border-slate-700 bg-slate-900 p-5" @if(old('banner_form') === (string)$banner->id) open @endif>
            <summary class="cursor-pointer font-bold">{{ $banner->sort_order }} · {{ $banner->title }} <span class="ml-2 text-xs text-orange-300">{{ $banner->is_active ? 'เปิดแสดง' : 'ซ่อนอยู่' }}</span></summary>
            @include('admin.settings.banner-form',['formKey'=>(string)$banner->id])
        </details>
        @endforeach
    </div>
</section>
@endsection
