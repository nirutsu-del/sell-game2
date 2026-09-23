@extends('layouts.app')

@section('content')
<div class="max-w-xl">
    <a href="{{ route('admin.members.index') }}" class="text-sm text-violet-300">← กลับหน้าจัดการสมาชิก</a>
    <h1 class="mt-4 text-2xl font-bold">{{ $member->exists ? 'แก้ไขสมาชิก' : 'เพิ่มสมาชิก' }}</h1>
    @if($member->exists)<p class="mt-2 text-sm text-slate-400">สมาชิก #{{ $member->id }} · ยอด Wallet ฿{{ number_format($member->balance, 2) }}</p>@endif
    <form method="POST" action="{{ $member->exists ? route('admin.members.update', $member) : route('admin.members.store') }}" class="mt-6 space-y-4">
        @csrf
        @if($member->exists) @method('PUT') @endif
        <label class="block">ชื่อสมาชิก<input name="name" value="{{ old('name', $member->name) }}" maxlength="100" autocomplete="name" class="mt-2 w-full rounded bg-slate-800 p-3" required>@error('name')<span class="mt-1 block text-sm text-red-300">{{ $message }}</span>@enderror</label>
        <label class="block">อีเมล<input type="email" name="email" value="{{ old('email', $member->email) }}" maxlength="255" autocomplete="email" class="mt-2 w-full rounded bg-slate-800 p-3" required>@error('email')<span class="mt-1 block text-sm text-red-300">{{ $message }}</span>@enderror</label>
        @if($member->is(auth()->user()))
            <input type="hidden" name="role" value="admin">
            <p class="rounded bg-slate-800 p-3">สิทธิ์: ผู้ดูแลระบบ <span class="mt-1 block text-sm text-slate-400">บัญชีที่กำลังใช้งานไม่สามารถลดสิทธิ์ตัวเองได้</span></p>
            <input type="hidden" name="status" value="active">
            <div class="rounded bg-slate-800 p-3">
                <span class="text-sm text-slate-400">สถานะบัญชี:</span> <span class="font-medium text-emerald-400">🟢 เปิดใช้งาน</span>
                <span class="mt-1 block text-sm text-slate-400">บัญชีที่กำลังใช้งานไม่สามารถระงับการใช้งานตัวเองได้</span>
            </div>
        @else
            <label class="block">สิทธิ์<select name="role" class="mt-2 w-full rounded bg-slate-800 p-3" required><option value="user" @selected(old('role', $member->role) === 'user')>สมาชิก</option><option value="admin" @selected(old('role', $member->role) === 'admin')>ผู้ดูแลระบบ</option></select>@error('role')<span class="mt-1 block text-sm text-red-300">{{ $message }}</span>@enderror</label>
            <p class="text-sm text-slate-400">ผู้ดูแลระบบสามารถเข้าถึงและจัดการข้อมูลหลังบ้านทั้งหมด</p>
            <label class="block">สถานะบัญชี
                <select name="status" class="mt-2 w-full rounded bg-slate-800 p-3" required>
                    <option value="active" @selected(old('status', $member->status ?? 'active') === 'active')>🟢 เปิดใช้งาน</option>
                    <option value="suspended" @selected(old('status', $member->status ?? 'active') === 'suspended')>🔴 ระงับการใช้งาน</option>
                </select>
                @error('status')<span class="mt-1 block text-sm text-red-300">{{ $message }}</span>@enderror
            </label>
            <p class="text-sm text-slate-400">บัญชีที่ถูกระงับจะไม่สามารถเข้าสู่ระบบหรือทำรายการใดๆ ในร้านได้</p>
        @endif
        @unless($member->exists)
            <label class="block">รหัสผ่าน<input type="password" name="password" minlength="8" maxlength="128" autocomplete="new-password" class="mt-2 w-full rounded bg-slate-800 p-3" required><span class="mt-1 block text-sm text-slate-400">อย่างน้อย 8 ตัวอักษร</span>@error('password')<span class="mt-1 block text-sm text-red-300">{{ $message }}</span>@enderror</label>
            <label class="block">ยืนยันรหัสผ่าน<input type="password" name="password_confirmation" minlength="8" maxlength="128" autocomplete="new-password" class="mt-2 w-full rounded bg-slate-800 p-3" required></label>
        @endunless
        <div class="flex gap-3"><button class="rounded bg-violet-600 px-5 py-3 font-semibold">{{ $member->exists ? 'บันทึกการแก้ไข' : 'เพิ่มสมาชิก' }}</button><a href="{{ route('admin.members.index') }}" class="rounded border border-slate-700 px-5 py-3">ยกเลิก</a></div>
    </form>
</div>
@endsection
