@extends('layouts.app')
@section('content')
<a href="{{ auth()->check() ? route('contact.index') : route('contact') }}" class="text-sm text-orange-300">← กลับหน้าติดต่อ</a>
<article class="mx-auto mt-6 max-w-3xl rounded-2xl border border-slate-700 bg-slate-900 p-6">
    <p class="text-sm text-orange-300">C-{{ $message->id }} · {{ $message->statusLabel() }}</p>
    <h1 class="mt-3 break-words text-2xl font-bold">{{ $message->subject }}</h1>
    <p class="mt-3 text-sm text-slate-400">ร้านจะตอบกลับในหน้านี้ กลับมาเปิดเพื่อตรวจสอบคำตอบได้</p>
    @if($trackingUrl)
        <div class="mt-5 rounded-xl border border-orange-500/40 p-4">
            <label class="block text-sm" for="tracking-link">เก็บลิงก์ส่วนตัวนี้ไว้เพื่อติดตามเรื่องและตอบกลับ</label>
            <input id="tracking-link" class="mt-2 w-full rounded bg-slate-950 p-3 text-sm" readonly value="{{ $trackingUrl }}">
            <p class="mt-2 text-xs text-slate-400">ผู้ที่มีลิงก์นี้สามารถอ่านและตอบกลับเรื่องนี้ได้ โปรดเก็บไว้เป็นส่วนตัว ระบบไม่ได้ส่งลิงก์ทางอีเมล</p>
        </div>
    @else
        <p class="mt-2 text-sm text-slate-400">เมื่อร้านตอบกลับ คุณจะได้รับการแจ้งเตือนในเว็บไซต์</p>
    @endif
    @include('partials.contact-conversation')
</article>
@endsection
