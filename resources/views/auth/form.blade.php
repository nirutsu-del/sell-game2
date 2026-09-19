@extends('layouts.app')

@section('content')
    <div class="mx-auto grid max-w-3xl gap-6 md:grid-cols-2">
        <form method="POST" action="{{ route('login.store') }}" class="market-form space-y-4 rounded-2xl border border-slate-700 bg-slate-900 p-6 shadow-xl">
            @csrf
            <h1 class="text-2xl font-bold">เข้าสู่ระบบ</h1>
            <label>อีเมล<input name="email" type="email" autocomplete="email" required placeholder="you@example.com"></label>
            <label>รหัสผ่าน<input name="password" type="password" autocomplete="current-password" required placeholder="กรอกรหัสผ่าน"></label>
            <button class="market-button w-full">เข้าสู่ระบบ</button>
        </form>
        <form method="POST" action="{{ route('register') }}" class="market-form space-y-4 rounded-2xl border border-slate-700 bg-slate-900 p-6 shadow-xl">
            @csrf
            <h2 class="text-2xl font-bold">สมัครสมาชิก</h2>
            <label>ชื่อที่ใช้ในร้าน<input name="name" autocomplete="name" required placeholder="ชื่อของคุณ"></label>
            <label>อีเมล<input name="email" type="email" autocomplete="email" required placeholder="you@example.com"></label>
            <label>รหัสผ่าน<input name="password" type="password" autocomplete="new-password" minlength="8" required placeholder="อย่างน้อย 8 ตัวอักษร"></label>
            <label>ยืนยันรหัสผ่าน<input name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required placeholder="กรอกรหัสผ่านอีกครั้ง"></label>
            <button class="market-button w-full">สมัครสมาชิก</button>
        </form>
    </div>
@endsection
    
