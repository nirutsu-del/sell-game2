@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-md rounded-2xl border border-slate-700 bg-slate-900 p-6">
<h1 class="text-2xl font-bold">ลืมรหัสผ่าน</h1><p class="mt-3 text-sm text-slate-400">กรอกอีเมลที่ใช้สมัครสมาชิกเพื่อรับลิงก์ตั้งรหัสผ่านใหม่</p>
<form method="POST" action="{{ route('password.email') }}" class="market-form mt-5 space-y-4">@csrf
<label>อีเมล<input name="email" type="email" maxlength="254" value="{{ old('email') }}" required autocomplete="email"></label>
<button class="market-button w-full">ขอลิงก์ตั้งรหัสผ่านใหม่</button></form>
<a href="{{ route('login') }}" class="mt-5 block text-center text-sm text-orange-300">กลับไปเข้าสู่ระบบ</a>
</section>
@endsection
