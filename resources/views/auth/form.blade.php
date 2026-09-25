@extends('layouts.app')
@section('content')
<div class="auth-page">
    <a class="auth-back" href="{{ route('shop.index') }}">← กลับหน้าร้าน</a>
    <div class="auth-shell">
        <aside class="auth-intro auth-intro-image-only">
            @include('auth.banner')
        </aside>
        <section class="auth-panel" aria-labelledby="auth-heading">
            <div class="auth-panel-heading">
                <span class="auth-eyebrow">{{ $register ? 'JOIN THE COMMUNITY' : 'WELCOME BACK' }}</span>
                <h1 id="auth-heading">{{ $register ? 'สมัครสมาชิก' : 'เข้าสู่ระบบ' }}</h1>
                <p>{{ $register ? 'สร้างบัญชี แล้วเริ่มค้นหาไอดีเกมที่ใช่สำหรับคุณ' : 'ยินดีต้อนรับกลับมา เข้าสู่ระบบเพื่อไปต่อกันเลย' }}</p>
            </div>
            <form method="POST" action="{{ $register ? route('register') : route('login.store') }}" class="auth-form">
                @csrf
                @if($register)
                <div class="auth-field">
                    <label for="auth-name">ชื่อที่ใช้ในร้าน</label>
                    <input id="auth-name" name="name" autocomplete="name" required maxlength="100" value="{{ old('name') }}" placeholder="ชื่อของคุณ" @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
                    @error('name')<p id="name-error" class="auth-error">{{ $message }}</p>@enderror
                </div>
                @endif
                <div class="auth-field">
                    <label for="auth-email">อีเมล</label>
                    <input id="auth-email" name="email" type="email" autocomplete="email" required value="{{ old('email') }}" placeholder="you@example.com" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                    @error('email')<p id="email-error" class="auth-error">{{ $message }}</p>@enderror
                </div>
                <div class="auth-field">
                    <label for="auth-password">รหัสผ่าน</label>
                    <div class="auth-password-wrap">
                        <input id="auth-password" name="password" type="password" autocomplete="{{ $register ? 'new-password' : 'current-password' }}" @if($register) minlength="8" @endif required placeholder="{{ $register ? 'อย่างน้อย 8 ตัวอักษร' : 'กรอกรหัสผ่านของคุณ' }}" @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                        <button type="button" data-password-toggle="auth-password" aria-controls="auth-password" aria-pressed="false" aria-label="แสดงรหัสผ่าน">แสดง</button>
                    </div>
                    @error('password')<p id="password-error" class="auth-error">{{ $message }}</p>@enderror
                </div>
                @if($register)
                <div class="auth-field">
                    <label for="auth-confirm">ยืนยันรหัสผ่าน</label>
                    <div class="auth-password-wrap">
                        <input id="auth-confirm" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required placeholder="กรอกรหัสผ่านอีกครั้ง">
                        <button type="button" data-password-toggle="auth-confirm" aria-controls="auth-confirm" aria-pressed="false" aria-label="แสดงรหัสผ่านยืนยัน">แสดง</button>
                    </div>
                </div>
                @else
                <label class="auth-remember"><input name="remember" type="checkbox" value="1" @checked(old('remember'))> จดจำการเข้าสู่ระบบ</label>
                @endif
                <button class="auth-submit" type="submit">{{ $register ? 'สร้างบัญชี' : 'เข้าสู่ระบบ' }} <span aria-hidden="true">→</span></button>
            </form>
            <p class="auth-switch">{{ $register ? 'มีบัญชีอยู่แล้ว?' : 'ยังไม่มีบัญชี?' }} <a href="{{ $register ? route('login') : route('register.form') }}">{{ $register ? 'เข้าสู่ระบบ' : 'สมัครสมาชิก' }} ↗</a></p>
            <p class="auth-help">ต้องการความช่วยเหลือ? <a href="{{ route('contact') }}">ติดต่อร้าน</a></p>
        </section>
    </div>
</div>
@endsection
