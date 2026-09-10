@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-2xl">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm font-medium text-violet-300">ADMIN PANEL</p>
            <h1 class="text-3xl font-bold">{{ $box->exists ? 'แก้ไขกล่องสุ่ม' : 'สร้างกล่องสุ่มใหม่' }}</h1>
        </div>
        <a href="{{ route('admin.gacha.index') }}" class="rounded-lg border border-slate-700 px-4 py-2 font-semibold hover:bg-slate-800">← กลับ</a>
    </div>

    <form method="POST" action="{{ $box->exists ? route('admin.gacha.update', $box) : route('admin.gacha.store') }}" enctype="multipart/form-data" class="mt-8 space-y-6 rounded-2xl border border-slate-800 bg-slate-900 p-6 sm:p-8">
        @csrf
        @if($box->exists)
            @method('PUT')
        @endif

        <div>
            <label class="block text-sm font-medium text-slate-300">ชื่อกล่องสุ่ม <span class="text-rose-400">*</span></label>
            <input type="text" name="name" value="{{ old('name', $box->name) }}" required placeholder="เช่น กล่องสุ่มเทพ ROV สกินแรร์ 100%" class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-800/80 px-4 py-3 text-white placeholder-slate-500 focus:border-violet-500 focus:outline-none focus:ring-1 focus:ring-violet-500">
            @error('name') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-300">ราคาต่อการสุ่ม 1 ครั้ง (บาท) <span class="text-rose-400">*</span></label>
            <input type="number" step="0.01" min="0" name="price_per_spin" value="{{ old('price_per_spin', $box->price_per_spin) }}" required placeholder="เช่น 20.00" class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-800/80 px-4 py-3 text-white placeholder-slate-500 focus:border-violet-500 focus:outline-none focus:ring-1 focus:ring-violet-500">
            @error('price_per_spin') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-300">คำอธิบายกล่องสุ่ม</label>
            <textarea name="description" rows="3" placeholder="ระบุรายละเอียด เช่น ลุ้นรับไอดีฮีโร่ครบ หรือสกินลิมิเต็ด รางวัลปลอบใจคืนเครดิตสูงสุด 50 บาท" class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-800/80 px-4 py-3 text-white placeholder-slate-500 focus:border-violet-500 focus:outline-none focus:ring-1 focus:ring-violet-500">{{ old('description', $box->description) }}</textarea>
            @error('description') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-300">รูปภาพหน้าปกกล่องสุ่ม</label>
            @if($box->image)
                <div class="my-3 flex items-center gap-4">
                    <img src="{{ asset('storage/'.$box->image) }}" alt="Preview" class="h-20 w-20 rounded-xl object-cover border border-slate-700">
                    <span class="text-xs text-slate-400">รูปภาพปัจจุบัน (อัปโหลดใหม่เพื่อเปลี่ยน)</span>
                </div>
            @endif
            <input type="file" name="image" accept="image/*" class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-800/80 px-4 py-2.5 text-sm text-slate-300 file:mr-4 file:rounded-md file:border-0 file:bg-violet-600 file:px-3 file:py-1 file:text-xs file:font-semibold file:text-white hover:file:bg-violet-500">
            <p class="mt-1 text-xs text-slate-500">ไฟล์รูปภาพ (jpg, png, webp) ไม่เกิน 5MB</p>
            @error('image') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
        </div>

        <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-4">
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $box->is_active ?? true) ? 'checked' : '' }} class="h-5 w-5 rounded border-slate-700 bg-slate-800 text-violet-600 focus:ring-violet-500">
                <div>
                    <span class="font-medium text-white">เปิดให้ผู้เล่นสุ่มหน้าร้าน</span>
                    <p class="text-xs text-slate-400">หากปิด กล่องนี้จะไม่แสดงในหน้าสุ่มของผู้เล่น</p>
                </div>
            </label>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
            <a href="{{ route('admin.gacha.index') }}" class="rounded-lg border border-slate-700 px-5 py-2.5 font-semibold text-slate-300 hover:bg-slate-800">ยกเลิก</a>
            <button type="submit" class="rounded-lg bg-violet-600 px-6 py-2.5 font-bold text-white shadow-lg shadow-violet-600/30 hover:bg-violet-500">
                {{ $box->exists ? 'บันทึกการแก้ไข' : 'บันทึกและไปหน้าใส่ของรางวัล →' }}
            </button>
        </div>
    </form>
</div>
@endsection
