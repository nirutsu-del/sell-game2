@extends('layouts.app')

@section('content')
    <div class="max-w-xl"><h1 class="text-2xl font-bold">{{ $category->exists ? 'แก้ไขเกม' : 'เพิ่มเกม' }}</h1><p class="mt-1 text-sm text-slate-400">ชื่อเกมนี้จะปรากฏในช่องเลือกเกมของหน้าเพิ่มไอดี</p>
    <form method="POST" enctype="multipart/form-data" action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}" class="mt-6 space-y-4">@csrf @if($category->exists) @method('PUT') @endif
        <label class="block">หมวดหมู่หลัก<select name="parent_id" class="mt-2 w-full rounded bg-slate-800 p-3"><option value="">เป็นหมวดหมู่หลัก</option>@foreach($parents as $parent)<option value="{{ $parent->id }}" @selected(old('parent_id',$category->parent_id) == $parent->id)>{{ $parent->name }}</option>@endforeach</select></label>
        <label class="block">ภาพแบนเนอร์หมวดหมู่<input name="image" type="file" accept="image/jpeg,image/png,image/webp" class="mt-2 block"></label>
        @if($category->image)<img src="{{ asset('storage/'.$category->image) }}" alt="{{ $category->name }}" class="h-28 rounded">@endif
        <label class="block">ลำดับแสดง<input type="number" name="sort_order" min="0" value="{{ old('sort_order',$category->sort_order ?? 0) }}" class="ml-3 w-24 rounded bg-slate-800 p-2"></label>
        <label class="block"><input name="is_featured" type="checkbox" value="1" @checked(old('is_featured',$category->is_featured))> หมวดหมู่แนะนำ</label>
        <div><label class="mb-2 block text-sm">ชื่อเกม</label><input name="name" value="{{ old('name', $category->name) }}" placeholder="เช่น Free Fire" class="w-full rounded bg-slate-800 p-3" required></div>
        <div><label class="mb-2 block text-sm">Slug (ภาษาอังกฤษ, เว้นว่างได้)</label><input name="slug" value="{{ old('slug', $category->slug) }}" placeholder="free-fire" class="w-full rounded bg-slate-800 p-3"><p class="mt-1 text-xs text-slate-500">ใช้เป็นรหัสเฉพาะของเกม หากไม่ใส่ระบบจะสร้างให้</p></div>
        <div class="flex gap-3"><button class="rounded bg-violet-600 px-5 py-3 font-semibold">บันทึก</button><a class="rounded border border-slate-700 px-5 py-3" href="{{ route('admin.categories.index') }}">ยกเลิก</a></div>
    </form></div>
@endsection
