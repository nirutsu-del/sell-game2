@extends('layouts.app')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div><p class="text-sm text-violet-300">ADMIN PANEL</p><h1 class="text-2xl font-bold">จัดการเกม</h1><p class="mt-1 text-sm text-slate-400">เกมที่เพิ่มที่นี่จะแสดงในช่อง “เลือกเกม” ตอนเพิ่มไอดี</p></div>
        <a class="rounded bg-violet-600 px-4 py-2 font-semibold hover:bg-violet-500" href="{{ route('admin.categories.create') }}">+ เพิ่มเกม</a>
    </div>
    <div class="mt-6 overflow-hidden rounded-xl border border-slate-800 bg-slate-900"><table class="w-full text-left"><thead class="border-b border-slate-800 text-sm text-slate-400"><tr><th class="p-4">ชื่อเกม</th><th class="p-4">จำนวนไอดี</th><th class="p-4 text-right">จัดการ</th></tr></thead><tbody>@forelse($categories as $category)<tr class="border-b border-slate-800 last:border-0"><td class="p-4 font-medium">{{ $category->name }}</td><td class="p-4">{{ $category->game_accounts_count }}</td><td class="p-4 text-right"><a class="rounded bg-slate-800 px-3 py-2 hover:bg-slate-700" href="{{ route('admin.categories.edit', $category) }}">แก้ไข</a>@if($category->game_accounts_count === 0)<form method="POST" action="{{ route('admin.categories.destroy', $category) }}" class="ml-2 inline" onsubmit="return confirm('ยืนยันการลบเกมนี้?')">@csrf @method('DELETE')<button class="rounded bg-red-900/60 px-3 py-2 text-red-200">ลบ</button></form>@endif</td></tr>@empty<tr><td colspan="3" class="p-8 text-center text-slate-500">ยังไม่มีเกม กด “เพิ่มเกม” เพื่อเริ่มต้น</td></tr>@endforelse</tbody></table></div>
@endsection
