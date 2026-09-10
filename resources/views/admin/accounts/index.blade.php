@extends('layouts.app')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div><p class="text-sm text-violet-300">ADMIN PANEL</p><h1 class="text-2xl font-bold">จัดการไอดีเกม</h1></div>
        <div class="flex gap-2"><a class="rounded border border-slate-700 px-4 py-2" href="{{ route('admin.categories.index') }}">จัดการเกม</a><a class="rounded border border-slate-700 px-4 py-2" href="{{ route('admin.topups.index') }}">ตรวจสอบเติมเงิน</a><a class="rounded bg-violet-600 px-4 py-2" href="{{ route('admin.accounts.create') }}">+ เพิ่มไอดี</a></div>
    </div>
    <div class="mt-6 overflow-x-auto rounded-xl border border-slate-800 bg-slate-900">
        <table class="w-full min-w-[720px] text-left text-sm"><thead class="border-b border-slate-800 text-slate-400"><tr><th class="p-4">สินค้า</th><th class="p-4">เกม</th><th class="p-4">ราคา</th><th class="p-4">สถานะ</th><th class="p-4 text-right">จัดการ</th></tr></thead><tbody>
        @forelse ($accounts as $account)
            <tr class="border-b border-slate-800 last:border-0"><td class="p-4 font-medium">{{ $account->title }}</td><td class="p-4">{{ $account->category->name }}</td><td class="p-4 text-emerald-400">฿{{ number_format($account->price, 2) }}</td><td class="p-4"><span class="rounded-full bg-slate-800 px-2 py-1 text-xs">{{ $account->status }}</span></td><td class="p-4 text-right"><a href="{{ route('admin.accounts.edit', $account) }}" class="rounded bg-slate-800 px-3 py-2 hover:bg-slate-700">แก้ไข</a>@if ($account->status !== 'sold')<form action="{{ route('admin.accounts.destroy', $account) }}" method="POST" class="ml-2 inline" onsubmit="return confirm('ยืนยันการลบไอดีนี้?')">@csrf @method('DELETE')<button class="rounded bg-red-900/60 px-3 py-2 text-red-200 hover:bg-red-800">ลบ</button></form>@endif</td></tr>
        @empty <tr><td colspan="5" class="p-8 text-center text-slate-500">ยังไม่มีไอดีเกม</td></tr>
        @endforelse
        </tbody></table>
    </div>
    <div class="mt-6">{{ $accounts->links() }}</div>
@endsection
