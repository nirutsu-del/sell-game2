@extends('layouts.app')

@section('content')
<div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
    <div>
        <p class="text-sm font-medium text-violet-300">ADMIN PANEL</p>
        <h1 class="text-3xl font-bold">จัดการกล่องสุ่มไอดี (Gacha Boxes)</h1>
        <p class="text-sm text-slate-400">สร้างและจัดการกล่องสุ่ม กำหนดราคา และตั้งค่าของรางวัลในกล่อง</p>
    </div>
    <div class="flex gap-3">
        <a href="{{ route('admin.dashboard') }}" class="rounded-lg border border-slate-700 px-4 py-2 font-semibold hover:bg-slate-800">← กลับแดชบอร์ด</a>
        <a href="{{ route('admin.gacha.create') }}" class="rounded-lg bg-violet-600 px-4 py-2 font-semibold hover:bg-violet-500">+ สร้างกล่องสุ่มใหม่</a>
    </div>
</div>

<div class="mt-6 overflow-hidden rounded-xl border border-slate-800 bg-slate-900">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-slate-800 bg-slate-950/60 text-xs font-semibold uppercase text-slate-400">
                <tr>
                    <th class="px-5 py-4">กล่องสุ่ม</th>
                    <th class="px-5 py-4">ราคา / ครั้ง</th>
                    <th class="px-5 py-4">รางวัลทั้งหมด</th>
                    <th class="px-5 py-4">ไอดีพร้อมสุ่ม</th>
                    <th class="px-5 py-4">จำนวนครั้งที่สุ่ม</th>
                    <th class="px-5 py-4">ยอดเงินรวม</th>
                    <th class="px-5 py-4">สถานะ</th>
                    <th class="px-5 py-4 text-right">จัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800 text-slate-300">
                @forelse($boxes as $box)
                    <tr class="hover:bg-slate-800/40">
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-violet-950/60 border border-violet-800/40 text-2xl">
                                    @if($box->image)
                                        <img src="{{ asset('storage/'.$box->image) }}" alt="{{ $box->name }}" class="h-full w-full rounded-lg object-cover">
                                    @else
                                        🎁
                                    @endif
                                </div>
                                <div>
                                    <p class="font-bold text-white">{{ $box->name }}</p>
                                    <p class="text-xs text-slate-400 line-clamp-1">{{ $box->description ?: 'ไม่มีคำอธิบาย' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4 font-bold text-emerald-400">
                            ฿{{ number_format($box->price_per_spin, 2) }}
                        </td>
                        <td class="px-5 py-4">
                            <span class="rounded bg-slate-800 px-2.5 py-1 text-xs font-medium">{{ $box->items_count }} รายการ</span>
                        </td>
                        <td class="px-5 py-4">
                            @php $avail = $box->availableAccountsCount(); @endphp
                            <span class="rounded px-2.5 py-1 text-xs font-semibold {{ $avail > 0 ? 'bg-emerald-950 text-emerald-300 border border-emerald-800' : 'bg-rose-950 text-rose-300 border border-rose-800' }}">
                                {{ $avail }} ไอดี
                            </span>
                        </td>
                        <td class="px-5 py-4 font-medium text-slate-200">
                            {{ number_format($box->spins_count) }} ครั้ง
                        </td>
                        <td class="px-5 py-4 font-medium text-violet-300">
                            ฿{{ number_format((float)($box->spins_sum_price_paid ?? 0), 2) }}
                        </td>
                        <td class="px-5 py-4">
                            @if($box->is_active)
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/15 px-2.5 py-0.5 text-xs font-medium text-emerald-400">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span> เปิดใช้งาน
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-700/40 px-2.5 py-0.5 text-xs font-medium text-slate-400">
                                    <span class="h-1.5 w-1.5 rounded-full bg-slate-500"></span> ปิดใช้งาน
                                </span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.gacha.items', $box) }}" class="rounded bg-purple-600/20 border border-purple-500/40 px-3 py-1.5 text-xs font-semibold text-purple-300 hover:bg-purple-600/30">
                                    ⚙️ ของรางวัล
                                </a>
                                <a href="{{ route('gacha.show', $box) }}" target="_blank" class="rounded bg-slate-800 px-3 py-1.5 text-xs font-medium text-slate-300 hover:bg-slate-700">
                                    👁️ ดูหน้าร้าน
                                </a>
                                <a href="{{ route('admin.gacha.edit', $box) }}" class="rounded bg-slate-800 px-3 py-1.5 text-xs font-medium text-slate-300 hover:bg-slate-700">
                                    แก้ไข
                                </a>
                                <form action="{{ route('admin.gacha.destroy', $box) }}" method="POST" onsubmit="return confirm('ยืนยันลบกล่องสุ่มนี้? กล่องจะหยุดเปิดให้สุ่ม โดยยังเก็บประวัติการสุ่มเดิมไว้')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded bg-rose-600/10 border border-rose-500/20 px-3 py-1.5 text-xs font-medium text-rose-400 hover:bg-rose-600/20">
                                        ลบ
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-5 py-12 text-center text-slate-500">
                            ยังไม่มีกล่องสุ่มในระบบ กดปุ่ม "+ สร้างกล่องสุ่มใหม่" ด้านบนเพื่อเริ่มสร้าง
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">
    {{ $boxes->links() }}
</div>
@endsection
