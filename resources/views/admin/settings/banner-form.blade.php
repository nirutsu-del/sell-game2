@php
    $restore = old('banner_form') === $formKey;
@endphp
<form method="POST" enctype="multipart/form-data" action="{{ $banner->exists ? route('admin.settings.banners.update',$banner) : route('admin.settings.banners.store') }}" class="market-form mt-5 space-y-4">
    @csrf @if($banner->exists) @method('PUT') @endif
    <input type="hidden" name="banner_form" value="{{ $formKey }}">
    <label>ชื่อแบนเนอร์ / คำอธิบายรูป<input name="title" value="{{ $restore ? old('title') : $banner->title }}" required maxlength="150"></label>
    <img id="banner-preview-{{ $formKey }}" @if($banner->image) src="{{ asset('storage/'.$banner->image) }}" @else hidden @endif alt="ตัวอย่างแบนเนอร์" class="max-h-56 max-w-full rounded-xl object-contain">
    <label>รูปแบนเนอร์<input name="image" type="file" accept="image/png,image/jpeg,image/webp" data-image-preview="banner-preview-{{ $formKey }}" @required(!$banner->exists)></label>
    <label>ลิงก์เมื่อกด (เว้นว่างได้)<input name="link" value="{{ $restore ? old('link') : $banner->link }}" maxlength="2048" placeholder="/categories หรือ https://example.com"></label>
    <div class="grid gap-4 sm:grid-cols-2">
        <label>ลำดับแสดง<input type="number" name="sort_order" min="0" max="10000" value="{{ $restore ? old('sort_order') : ($banner->sort_order ?? 0) }}" required></label>
        <label class="self-end pb-3"><input type="checkbox" name="is_active" value="1" @checked($restore ? old('is_active',false) : ($banner->is_active ?? true))> เปิดแสดงบนหน้าแรก</label>
    </div>
    <button class="market-button">บันทึกแบนเนอร์นี้</button>
</form>
