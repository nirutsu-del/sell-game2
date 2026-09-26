@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-xl rounded-2xl border border-slate-700 bg-slate-900 p-6" aria-labelledby="profile-heading">
    <h1 id="profile-heading" class="text-2xl font-bold">ข้อมูลส่วนตัว</h1>
    <p class="mt-3 text-sm leading-6 text-slate-400">แก้ไขชื่อที่ใช้แสดงในร้านและบัญชีของคุณ</p>
    <form method="POST" action="{{ route('user.profile.update') }}" class="market-form mt-6 space-y-5">
        @csrf @method('PUT')
        <div>
            <label for="profile-name">ชื่อที่ใช้ในร้าน</label>
            <input id="profile-name" name="name" autocomplete="name" required maxlength="100" value="{{ old('name', $user->name) }}" @error('name') aria-invalid="true" aria-describedby="profile-name-error" @enderror>
            @error('name')<p id="profile-name-error" class="mt-2 text-sm text-red-300">{{ $message }}</p>@enderror
        </div>
        <div>
            <p class="mb-2 font-medium">อีเมลที่ใช้เข้าสู่ระบบ</p>
            <p class="break-words rounded-lg border border-slate-700 bg-slate-950 p-3 text-slate-300">{{ $user->email }}</p>
            <p class="mt-2 text-sm leading-6 text-slate-400">หากต้องการแก้ไขอีเมล กรุณา<a class="text-violet-300" href="{{ route('contact') }}">ติดต่อร้าน</a></p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" class="market-button">บันทึกข้อมูล</button>
            <a class="market-small-button" href="{{ route('user.dashboard') }}">กลับหน้าบัญชี</a>
        </div>
    </form>
    <div class="mt-6 border-t border-slate-700 pt-5"><a class="text-violet-300" href="{{ route('password.edit') }}">เปลี่ยนรหัสผ่าน →</a></div>
</section>
@endsection
