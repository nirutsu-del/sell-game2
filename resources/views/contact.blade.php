@extends('layouts.app')
@section('content')
<div class="market-heading"><div><p>CONTACT US</p><h1 class="text-3xl font-bold">ติดต่อ {{ $settings->name }}</h1></div></div>
<div class="grid gap-7 lg:grid-cols-2">
    <section class="rounded-2xl border border-slate-700 bg-slate-900 p-6">
        <h2 class="text-xl font-bold">ช่องทางติดต่อร้าน</h2>
        <div class="mt-5 space-y-3">
            @forelse($settings->contactLinks() as $channel)
                <a href="{{ $channel['url'] }}" target="_blank" rel="noopener noreferrer" class="market-shortcut justify-between"><span>{{ $channel['label'] }}</span><span class="text-orange-300">เปิดแชต / ช่องทางติดต่อ ↗</span></a>
            @empty
                <p class="text-sm text-slate-400">ฝากข้อความถึงร้านผ่านแบบฟอร์มด้านข้างได้เลย</p>
            @endforelse
        </div>
        @if($settings->opening_hours)<h3 class="mt-6 font-semibold">เวลาทำการ / เวลาตอบข้อความ</h3><p class="mt-2 whitespace-pre-line break-words text-sm text-slate-300">{{ $settings->opening_hours }}</p>@endif
        <p class="mt-6 text-xs leading-6 text-slate-400">หากสอบถามคำสั่งซื้อ กรุณาระบุเลขอ้างอิง เช่น S-12 หรือ A-8 เพื่อให้ร้านตรวจสอบได้สะดวก</p>
    </section>
    <form method="POST" action="{{ route('contact.store') }}" class="market-form space-y-4 rounded-2xl border border-slate-700 bg-slate-900 p-6">
        @csrf<h2 class="text-xl font-bold">ฝากข้อความถึงร้าน</h2>
        <label>ชื่อ<input name="name" value="{{ old('name',auth()->user()?->name) }}" required maxlength="100"></label>
        <label>อีเมล<input name="email" type="email" value="{{ old('email',auth()->user()?->email) }}" required></label>
        <label>หัวข้อ<input name="subject" value="{{ old('subject') }}" required maxlength="150"></label>
        <label>ข้อความ<textarea name="message" rows="5" required maxlength="3000">{{ old('message') }}</textarea></label>
        <button class="market-button">ส่งข้อความ</button>
    </form>
</div>
@endsection
