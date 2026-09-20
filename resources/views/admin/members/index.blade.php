@extends('layouts.app')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3">
    <div><p class="text-sm text-violet-300">ADMIN PANEL</p><h1 class="text-2xl font-bold">จัดการสมาชิก</h1><p class="mt-1 text-sm text-slate-400">ค้นหาและจัดการข้อมูลบัญชีสมาชิกในร้าน</p></div>
    <a href="{{ route('admin.members.create') }}" class="rounded bg-violet-600 px-4 py-2">+ เพิ่มสมาชิก</a>
</div>
<form method="GET" action="{{ route('admin.members.index') }}" class="mt-6 flex flex-wrap items-end gap-3" role="search">
    <label class="block min-w-0 flex-1">ค้นหาสมาชิก<input type="search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100" placeholder="ชื่อ อีเมล หรือรหัสสมาชิก" class="mt-2 w-full rounded bg-slate-800 p-3"></label>
    <label class="block">สิทธิ์<select name="role" class="mt-2 block rounded bg-slate-800 p-3"><option value="">ทั้งหมด</option><option value="user" @selected(($filters['role'] ?? '') === 'user')>สมาชิก</option><option value="admin" @selected(($filters['role'] ?? '') === 'admin')>ผู้ดูแลระบบ</option></select></label>
    <button class="rounded bg-violet-600 px-4 py-3">ค้นหา</button>
    <a href="{{ route('admin.members.index') }}" class="rounded border border-slate-700 px-4 py-3">ล้างตัวกรอง</a>
</form>
<p class="mt-4 text-sm text-slate-400">พบ {{ number_format($members->total()) }} บัญชี</p>
<div class="mt-4 overflow-x-auto rounded-xl border border-slate-800 bg-slate-900">
    <table class="w-full min-w-[720px] text-left text-sm">
        <thead class="border-b border-slate-800 text-slate-400"><tr><th class="p-4">สมาชิก</th><th class="p-4">สิทธิ์</th><th class="p-4">ยอด Wallet</th><th class="p-4">วันที่สมัคร (เวลาไทย)</th><th class="p-4 text-right">จัดการ</th></tr></thead>
        <tbody>
        @forelse($members as $member)
            <tr class="border-b border-slate-800 last:border-0">
                <td class="p-4"><p class="font-medium break-all">{{ $member->name }} @if($member->is(auth()->user()))<span class="text-xs text-violet-300">(คุณ)</span>@endif</p><p class="mt-1 break-all text-slate-400">{{ $member->email }}</p><p class="mt-1 text-xs text-slate-500">#{{ $member->id }}</p></td>
                <td class="p-4"><span class="rounded-full bg-slate-800 px-2 py-1 text-xs">{{ $member->isAdmin() ? 'ผู้ดูแลระบบ' : 'สมาชิก' }}</span></td>
                <td class="p-4 text-emerald-400">฿{{ number_format($member->balance, 2) }}</td>
                <td class="p-4">{{ $member->created_at?->copy()->timezone('Asia/Bangkok')->format('d/m/Y H:i') ?? '—' }}</td>
                <td class="p-4 text-right"><a href="{{ route('admin.members.edit', $member) }}" class="rounded bg-slate-800 px-3 py-2" aria-label="แก้ไขสมาชิก {{ $member->name }}">แก้ไข</a></td>
            </tr>
        @empty
            <tr><td colspan="5" class="p-8 text-center text-slate-400">ไม่พบสมาชิกที่ตรงกับการค้นหา</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-6">{{ $members->links() }}</div>
@endsection
