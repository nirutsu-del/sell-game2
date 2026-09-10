@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-md rounded-2xl border border-slate-700 bg-slate-900 p-6">
<h1 class="text-2xl font-bold">เปลี่ยนรหัสผ่าน</h1><p class="mt-3 text-sm text-slate-400">กรอกรหัสผ่านปัจจุบันเพื่อยืนยันตัวตน หลังเปลี่ยนสำเร็จจะต้องเข้าสู่ระบบใหม่</p>
<form method="POST" action="{{ route('password.update') }}" class="market-form mt-5 space-y-4">@csrf @method('PUT')
<label>รหัสผ่านปัจจุบัน<input name="current_password" type="password" required autocomplete="current-password"></label>
<label>รหัสผ่านใหม่<input name="password" type="password" required minlength="8" maxlength="128" autocomplete="new-password"></label>
<label>ยืนยันรหัสผ่านใหม่<input name="password_confirmation" type="password" required minlength="8" maxlength="128" autocomplete="new-password"></label>
<button class="market-button w-full">เปลี่ยนรหัสผ่าน</button></form>
</section>
@endsection
