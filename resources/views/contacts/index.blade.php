@extends('layouts.app')
@section('content')
<div class="market-heading"><h1 class="text-3xl font-bold">เรื่องที่ฉันติดต่อ</h1><a href="{{ route('contact') }}" class="market-button">แจ้งเรื่องใหม่</a></div>
<div class="space-y-3">
    @forelse($messages as $message)
        <a href="{{ route('contact.show',$message) }}" class="block rounded-2xl border border-slate-700 bg-slate-900 p-5">
            <p class="text-sm text-orange-300">C-{{ $message->id }} · {{ $message->resolved_at ? 'จัดการแล้ว' : 'เปิดอยู่' }}</p>
            <h2 class="mt-2 break-words font-bold">{{ $message->subject }}</h2>
            <p class="mt-2 text-xs text-slate-400">อัปเดต {{ $message->updated_at->copy()->timezone('Asia/Bangkok')->format('d/m/Y H:i') }}</p>
        </a>
    @empty
        <p class="market-empty">ยังไม่มีเรื่องติดต่อ เมื่อส่งข้อความหลังเข้าสู่ระบบ เรื่องจะแสดงที่นี่</p>
    @endforelse
</div>
<div class="mt-6">{{ $messages->links() }}</div>
@endsection
