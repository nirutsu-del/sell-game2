@extends('layouts.app')
@section('content')
<a href="{{ route('admin.contacts.index') }}" class="text-sm text-orange-300">← กล่องข้อความทั้งหมด</a>
<article class="mx-auto mt-6 max-w-3xl rounded-2xl border border-slate-700 bg-slate-900 p-6">
    <p class="text-xs text-orange-300">C-{{ $message->id }} · {{ $message->statusLabel() }}</p>
    <h1 class="mt-3 break-words text-2xl font-bold">{{ $message->subject }}</h1>
    <dl class="mt-5 grid grid-cols-[auto_1fr] gap-x-5 gap-y-2 text-sm"><dt class="text-slate-400">ผู้ส่ง</dt><dd class="break-words">{{ $message->name }}</dd><dt class="text-slate-400">อีเมล</dt><dd class="break-all">{{ $message->email }}</dd><dt class="text-slate-400">เวลาส่ง</dt><dd>{{ $message->created_at->copy()->timezone('Asia/Bangkok')->format('d/m/Y H:i:s') }}</dd></dl>
    <p class="mt-6 whitespace-pre-wrap break-words rounded-xl bg-slate-950 p-5 text-sm leading-7">{{ $message->message }}</p>
    @if($message->read_at)<p class="mt-4 text-xs text-slate-400">ทำเครื่องหมายอ่านแล้วเมื่อ {{ $message->read_at->copy()->timezone('Asia/Bangkok')->format('d/m/Y H:i:s') }}</p>@endif
    @unless($message->resolved_at)
        @unless($message->read_at)
        <form method="POST" action="{{ route('admin.contacts.update',$message) }}" class="mt-5">@csrf @method('PATCH')<input type="hidden" name="action" value="read"><button class="market-small-button">ทำเครื่องหมายว่าอ่านแล้ว</button></form>
        @endunless
        <form method="POST" action="{{ route('admin.contacts.update',$message) }}" class="market-form mt-6 space-y-3">
            @csrf @method('PATCH')<input type="hidden" name="action" value="resolve">
            <label>บันทึกการจัดการภายในร้าน (ไม่ส่งให้ลูกค้า)<textarea name="resolution_note" maxlength="3000" rows="3">{{ old('resolution_note') }}</textarea></label>
            <button class="market-button">ทำเครื่องหมายว่าจัดการแล้ว</button>
        </form>
    @else
        <section class="mt-6 rounded-xl border border-emerald-500/30 p-4"><h2 class="font-bold text-emerald-300">จัดการแล้ว</h2><p class="mt-2 text-xs text-slate-400">โดย {{ $message->resolved_by_name }} · {{ $message->resolved_at->copy()->timezone('Asia/Bangkok')->format('d/m/Y H:i:s') }}</p><p class="mt-3 whitespace-pre-wrap break-words text-sm">{{ $message->resolution_note ?: 'ไม่มีบันทึกเพิ่มเติม' }}</p></section>
    @endunless
</article>
@endsection
