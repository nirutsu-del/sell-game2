@extends('layouts.app')

@section('content')
    <div class="mx-auto grid max-w-3xl gap-6 md:grid-cols-2">
        <form method="POST" action="{{ route('login.store') }}" class="space-y-3 rounded-xl bg-slate-900 p-6">
            @csrf
            <h1 class="text-2xl font-bold">เข้าสู่ระบบ</h1>
            <input name="email" type="email" placeholder="อีเมล" class="w-full rounded bg-slate-800 p-3">
            <input name="password" type="password" placeholder="รหัสผ่าน" class="w-full rounded bg-slate-800 p-3">
            <button class="w-full rounded bg-violet-600 p-3 font-bold">เข้าสู่ระบบ</button>
            <a class="block text-center text-sm text-orange-300" href="{{ route('password.request') }}">ลืมรหัสผ่าน?</a>
        </form>
        <form method="POST" action="{{ route('register') }}" class="space-y-3 rounded-xl bg-slate-900 p-6">
            @csrf
            <h2 class="text-2xl font-bold">สมัครสมาชิก</h2>
            <input name="name" placeholder="ชื่อ" class="w-full rounded bg-slate-800 p-3">
            <input name="email" type="email" placeholder="อีเมล" class="w-full rounded bg-slate-800 p-3">
            <input name="password" type="password" placeholder="รหัสผ่านอย่างน้อย 8 ตัว" class="w-full rounded bg-slate-800 p-3">
            <input name="password_confirmation" type="password" placeholder="ยืนยันรหัสผ่าน" class="w-full rounded bg-slate-800 p-3">
            <button class="w-full rounded bg-violet-600 p-3 font-bold">สมัครสมาชิก</button>
        </form>
    </div>
@endsection
    
