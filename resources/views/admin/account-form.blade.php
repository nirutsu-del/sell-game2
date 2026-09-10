@extends('layouts.app')

@section('content')
    <div class="max-w-2xl">
        <h1 class="text-2xl font-bold">{{ $account->exists ? 'แก้ไขไอดีเกม' : 'เพิ่มไอดีเกม' }}</h1>
        <p class="mt-1 text-sm text-slate-400">เพิ่มรูปภาพสินค้าได้สูงสุด 5 รูป (JPG, PNG หรือ WEBP ขนาดไม่เกิน 5 MB ต่อรูป)</p>

        <form method="POST" action="{{ $account->exists ? route('admin.accounts.update', $account) : route('admin.accounts.store') }}" enctype="multipart/form-data" class="mt-6 space-y-4">
            @csrf
            @if ($account->exists) @method('PUT') @endif
            <select name="category_id" class="w-full rounded bg-slate-800 p-3" required>
                <option value="" disabled @selected(!old('category_id', $account->category_id))>เลือกเกม</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected(old('category_id', $account->category_id) == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <input name="title" value="{{ old('title', $account->title) }}" placeholder="ชื่อสินค้า" class="w-full rounded bg-slate-800 p-3" required>
            <input name="price" value="{{ old('price', $account->price) }}" type="number" min="0" step="0.01" placeholder="ราคา" class="w-full rounded bg-slate-800 p-3" required>
            <textarea name="description" placeholder="รายละเอียด" class="min-h-28 w-full rounded bg-slate-800 p-3">{{ old('description', $account->description) }}</textarea>
            @if ($account->exists)
                <select name="status" class="w-full rounded bg-slate-800 p-3" required>
                    @foreach (['available' => 'พร้อมขาย', 'reserved' => 'จองแล้ว', 'sold' => 'ขายแล้ว'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $account->status) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            @endif
            <div>
                <label for="images" class="mb-2 block font-medium">รูปภาพไอดีเกม</label>
                <input id="images" name="images[]" type="file" accept="image/jpeg,image/png,image/webp" multiple class="block w-full rounded border border-dashed border-slate-600 bg-slate-900 p-3 text-sm file:mr-4 file:rounded file:border-0 file:bg-violet-600 file:px-3 file:py-2 file:text-white hover:file:bg-violet-500">
                @if (filled($account->images))
                    <p class="mt-2 text-xs text-slate-400">การเลือกรูปใหม่จะแทนที่รูปเดิมทั้งหมด</p>
                @endif
                <div id="image-preview" class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3"></div>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <input name="username" value="{{ old('username') }}" placeholder="{{ $account->exists ? 'Username (เว้นว่างเพื่อคงเดิม)' : 'Username' }}" class="w-full rounded bg-slate-800 p-3" @required(! $account->exists)>
                <input name="password_value" type="password" placeholder="{{ $account->exists ? 'Password (เว้นว่างเพื่อคงเดิม)' : 'Password' }}" class="w-full rounded bg-slate-800 p-3" @required(! $account->exists)>
            </div>
            <input name="code" value="{{ old('code') }}" placeholder="{{ $account->exists ? 'Code / Email recovery (เว้นว่างเพื่อคงเดิม)' : 'Code / Email recovery' }}" class="w-full rounded bg-slate-800 p-3">
            <button class="rounded bg-violet-600 px-5 py-3 font-semibold hover:bg-violet-500">{{ $account->exists ? 'บันทึกการแก้ไข' : 'บันทึกไอดีเกม' }}</button>
        </form>
    </div>
    <script>
        document.getElementById('images').addEventListener('change', function () {
            const preview = document.getElementById('image-preview');
            preview.replaceChildren();
            Array.from(this.files).slice(0, 5).forEach((file) => {
                const image = document.createElement('img');
                image.src = URL.createObjectURL(file);
                image.alt = `ตัวอย่างรูปภาพ ${file.name}`;
                image.className = 'h-28 w-full rounded-lg border border-slate-700 object-cover';
                preview.appendChild(image);
            });
        });
    </script>
@endsection
