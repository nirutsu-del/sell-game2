@extends('layouts.app')
@section('content')
<div class="mx-auto max-w-3xl">
    <div class="market-heading"><div><p>NOTIFICATIONS</p><h1 class="text-3xl font-bold">การแจ้งเตือน</h1></div>
    <form method="POST" action="{{ route('notifications.read-all') }}">@csrf<button class="market-small-button">อ่านทั้งหมด</button></form></div>
    <nav aria-label="ตัวกรองแจ้งเตือน" class="mb-5 flex gap-3">
        @foreach(['all'=>'ทั้งหมด','unread'=>'ยังไม่อ่าน'] as $filter=>$label)<a href="{{ route('notifications.index',['filter'=>$filter]) }}" @if(request('filter','all') === $filter) aria-current="page" @endif class="rounded-xl border px-4 py-2 text-sm {{ request('filter','all') === $filter ? 'border-orange-500 text-orange-300 bg-orange-500/10' : 'border-slate-700 text-slate-400' }}">{{ $label }}</a>@endforeach
    </nav>
    <p class="mb-5 text-xs text-slate-400">กระดิ่งอัปเดตอัตโนมัติทุก 30 วินาที · <a class="text-orange-300 underline" href="{{ request()->fullUrl() }}">โหลดรายการล่าสุด</a></p>
    <div class="space-y-3">
    @forelse($notifications as $notification)
        <article class="rounded-2xl border p-5 {{ $notification->read_at ? 'border-slate-800 bg-slate-900/60' : 'border-orange-500/40 bg-slate-900' }}">
            <div class="flex items-start justify-between gap-3"><h2 class="break-words font-bold">{{ $notification->data['title'] ?? 'แจ้งเตือนจากร้าน' }}</h2>@unless($notification->read_at)<span class="shrink-0 rounded-full bg-orange-500/15 px-2 py-1 text-[10px] text-orange-300">ยังไม่อ่าน</span>@endunless</div>
            <p class="mt-2 break-words text-sm text-slate-300">{{ $notification->data['message'] ?? '' }}</p>
            <div class="mt-4 flex flex-wrap items-center justify-between gap-3"><time class="text-xs text-slate-500" datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->copy()->timezone('Asia/Bangkok')->format('d/m/Y H:i') }}</time>
            <form method="POST" action="{{ route('notifications.open',$notification->id) }}">@csrf<button class="text-sm font-semibold text-orange-300">ดูรายการ →</button></form></div>
        </article>
    @empty
        <div class="market-empty"><p class="text-lg">ไม่มี{{ request('filter') === 'unread' ? 'แจ้งเตือนที่ยังไม่อ่าน' : 'แจ้งเตือนในขณะนี้' }}</p><p class="mt-2 text-sm">เมื่อมีรายการใหม่หรือสถานะเปลี่ยน ระบบจะแจ้งให้คุณทราบที่นี่</p></div>
    @endforelse
    </div>
    <div class="mt-6">{{ $notifications->links() }}</div>
</div>
@endsection
