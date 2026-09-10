@extends('layouts.app')
@section('content')
<h1 class="text-2xl font-bold">{{ $product->exists ? 'แก้ไขสินค้าและแพ็กเกจ' : 'เพิ่มสินค้าและแพ็กเกจ' }}</h1>
<form method="POST" enctype="multipart/form-data" action="{{ $product->exists ? route('admin.products.update',$product) : route('admin.products.store') }}" class="mt-6 max-w-3xl space-y-5 market-form">
@csrf @if($product->exists) @method('PUT') @endif
<label>ชื่อสินค้า<input name="name" value="{{ old('name',$product->name) }}" required maxlength="150"></label>
<label>หมวดหมู่<select name="category_id" required><option value="">เลือกหมวดหมู่</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(old('category_id',$product->category_id) == $category->id)>{{ $category->name }}</option>@endforeach</select></label>
<label>รูปสินค้า<input name="image" type="file" accept="image/jpeg,image/png,image/webp"></label>
@if($product->image)<img src="{{ asset('storage/'.$product->image) }}" alt="{{ $product->name }}" class="h-28 rounded">@endif
<label>รายละเอียด<textarea name="description" rows="4">{{ old('description',$product->description) }}</textarea></label>
<label>เงื่อนไขและระยะเวลาดำเนินการ<textarea name="terms" rows="4">{{ old('terms',$product->terms) }}</textarea></label>
<label>ชื่อช่องข้อมูลผู้รับ<input name="recipient_label" value="{{ old('recipient_label',$product->recipient_label ?? 'Username / UID') }}" required></label>
<div class="flex gap-5"><label><input type="checkbox" name="is_active" value="1" @checked(old('is_active',$product->is_active ?? true))> เปิดขาย</label><label><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured',$product->is_featured))> สินค้าแนะนำ</label></div>
<h2 class="text-xl font-bold">แพ็กเกจสินค้า</h2><p class="text-xs text-slate-400">สต๊อกว่าง = ไม่จำกัด / 0 = หมด ปิดแพ็กเกจแทนการลบเพื่อรักษาประวัติ</p>
<div id="variant-editor" class="space-y-3">
@foreach(old('variants',$product->exists ? $product->variants->toArray() : [['name'=>'','price'=>'','stock'=>'','is_active'=>true]]) as $i=>$variant)
<div class="variant-row rounded-xl border border-slate-700 p-4">
    <input type="hidden" name="variants[{{ $i }}][id]" value="{{ $variant['id'] ?? '' }}">
    <div class="grid gap-3 sm:grid-cols-3"><label>ชื่อแพ็กเกจ<input name="variants[{{ $i }}][name]" value="{{ $variant['name'] }}" required></label><label>ราคา<input name="variants[{{ $i }}][price]" type="number" min="1" step=".01" value="{{ $variant['price'] }}" required></label><label>สต๊อก<input name="variants[{{ $i }}][stock]" type="number" min="0" value="{{ $variant['stock'] ?? '' }}"></label></div>
    <label class="mt-3"><input type="checkbox" name="variants[{{ $i }}][is_active]" value="1" @checked($variant['is_active'] ?? false)> เปิดแพ็กเกจ</label>
</div>
@endforeach
</div>
<button type="button" id="add-variant" class="rounded-lg border border-orange-400 px-4 py-2 text-orange-300">+ เพิ่มแพ็กเกจ</button>
<div class="flex gap-4"><button class="market-button">บันทึกสินค้า</button><a href="{{ route('admin.products.index') }}" class="p-3">กลับ</a></div>
</form>
@endsection
