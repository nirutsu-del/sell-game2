@extends('layouts.app')
@section('content')
<div class="market-heading"><div><p>ADMIN / INBOX</p><h1 class="text-3xl font-bold">กล่องข้อความติดต่อ</h1></div><a href="{{ route('admin.dashboard') }}">ภาพรวมร้าน →</a></div>
<nav aria-label="สถานะข้อความ" class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
@foreach(['all'=>'ทั้งหมด','staff'=>'รอร้านตอบ','customer'=>'รอลูกค้าตอบ','resolved'=>'จัดการแล้ว'] as $status=>$label)
<a href="{{ route('admin.contacts.index',array_merge(request()->except(['status','page']),['status'=>$status])) }}" @if(request('status','all') === $status) aria-current="page" @endif class="rounded-xl border p-4 {{ request('status','all') === $status ? 'border-orange-500 bg-orange-500/10' : 'border-slate-700 bg-slate-900' }}"><p class="text-xs text-slate-400">{{ $label }}</p><strong class="mt-2 block text-2xl text-orange-300">{{ $counts[$status] }}</strong></a>
@endforeach
</nav>
<form class="mb-6 flex flex-wrap gap-3">
    <input type="hidden" name="status" value="{{ request('status','all') }}">
    <input aria-label="ค้นหาข้อความ" name="q" value="{{ request('q') }}" maxlength="100" placeholder="C-12, ชื่อ, อีเมล, หัวข้อ หรือข้อความ" class="min-w-0 flex-1 rounded-xl bg-slate-800 p-3">
    <button class="market-button">ค้นหา</button><a href="{{ route('admin.contacts.index') }}" class="py-3 text-sm text-slate-400">ล้างตัวกรอง</a>
</form>
<p class="mb-4 text-xs text-slate-400">พบ {{ $messages->total() }} รายการ · อัปเดตล่าสุดก่อน</p>
<div class="space-y-3">
@forelse($messages as $message)
    <a href="{{ route('admin.contacts.show',$message) }}" class="block rounded-2xl border border-slate-700 bg-slate-900 p-5 hover:border-orange-500/50">
        <div class="flex flex-wrap justify-between gap-2"><p class="text-xs text-orange-300">C-{{ $message->id }} · {{ $message->statusLabel() }}</p><time class="text-xs text-slate-500">{{ $message->created_at->copy()->timezone('Asia/Bangkok')->format('d/m/Y H:i') }}</time></div>
        <h2 class="mt-3 break-words font-bold">{{ $message->subject }}</h2>
        <p class="mt-2 text-sm text-slate-400">{{ $message->categoryLabel() }} · {{ $message->order_reference }}</p>
        <p class="mt-2 break-words text-sm text-slate-400">{{ $message->name }} · {{ $message->email }}</p>
        <p class="mt-2 break-words text-sm text-slate-300">{{ Str::limit($message->message,140) }}</p>
    </a>
@empty<p class="market-empty">ไม่พบข้อความที่ตรงกับตัวกรอง</p>@endforelse
</div>
<div class="mt-6">{{ $messages->links() }}</div>
@endsection
