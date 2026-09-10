@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-md rounded-2xl border border-slate-700 bg-slate-900 p-6">
<h1 class="text-2xl font-bold">ตั้งรหัสผ่านใหม่</h1><p class="mt-3 text-sm text-slate-400">ใช้รหัสผ่านอย่างน้อย 8 ตัวอักษร เมื่อบันทึกแล้วต้องเข้าสู่ระบบใหม่</p>
<form method="POST" action="{{ route('password.store') }}" class="market-form mt-5 space-y-4">@csrf
<input name="token" type="hidden" value="{{ $token }}">
<label>อีเมล<input name="email" type="email" value="{{ old('email',$email) }}" required autocomplete="email"></label>
<label>รหัสผ่านใหม่<input name="password" type="password" required minlength="8" maxlength="128" autocomplete="new-password"></label>
<label>ยืนยันรหัสผ่านใหม่<input name="password_confirmation" type="password" required minlength="8" maxlength="128" autocomplete="new-password"></label>
<button class="market-button w-full">บันทึกรหัสผ่านใหม่</button></form>
<a href="{{ route('password.request') }}" class="mt-5 block text-center text-sm text-orange-300">ลิงก์หมดอายุ? ขอใหม่</a>
</section>
@endsection
