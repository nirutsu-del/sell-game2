@extends('layouts.app')
@section('content')
<div class="market-heading"><div><p>CONTACT US</p><h1 class="text-3xl font-bold">ติดต่อ {{ $settings->name }}</h1></div></div>
@auth
<a href="{{ route('contact.index') }}" class="market-small-button mb-5 inline-block">เรื่องที่ฉันติดต่อ / อ่านคำตอบจากร้าน →</a>
@endauth
@if(session('contact_tracking_url'))<p class="mb-5"><a class="text-orange-300 underline" href="{{ session('contact_tracking_url') }}">ติดตามเรื่องล่าสุดที่ส่งโดยไม่ได้เข้าสู่ระบบ →</a></p>@endif
<div>
    <form method="POST" action="{{ route('contact.store') }}" enctype="multipart/form-data" class="market-form space-y-4 rounded-2xl border border-slate-700 bg-slate-900 p-6">
        @csrf<h2 class="text-xl font-bold">ฝากข้อความถึงร้าน</h2>
        <p class="text-sm text-slate-400">หลังส่ง คุณจะเข้าสู่หน้าติดตามเรื่อง ร้านจะตอบกลับในหน้านั้น @guest กรุณาเก็บลิงก์ส่วนตัวที่ได้รับ หรือเข้าสู่ระบบก่อนส่งเพื่อดูประวัติและรับแจ้งเตือนในเว็บไซต์ @endguest</p>
        <label>ชื่อ<input name="name" value="{{ old('name',auth()->user()?->name) }}" required maxlength="100"></label>
        <label>อีเมล<input name="email" type="email" value="{{ old('email',auth()->user()?->email) }}" required></label>
        <label>ประเภทปัญหา<select name="category" required>@foreach(App\Models\ContactMessage::CATEGORIES as $value=>$label)<option value="{{ $value }}" @selected(old('category','general') === $value)>{{ $label }}</option>@endforeach</select></label>
        <label>ข้อความ<textarea name="message" rows="5" required maxlength="3000">{{ old('message') }}</textarea></label>
        @include('partials.contact-upload')
        <button class="market-button">ส่งข้อความ</button>
    </form>
</div>
@endsection
