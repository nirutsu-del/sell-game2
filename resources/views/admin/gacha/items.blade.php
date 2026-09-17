@extends('layouts.app')

@section('content')
<div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
    <div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.gacha.index') }}" class="text-sm font-medium text-violet-400 hover:underline">← กล่องสุ่มทั้งหมด</a>
            <span class="text-slate-600">/</span>
            <span class="text-sm text-slate-400">ของรางวัล</span>
        </div>
        <h1 class="mt-1 text-3xl font-bold text-white">{{ $box->name }}</h1>
        <p class="text-sm text-slate-400">ราคา: <span class="font-bold text-emerald-400">฿{{ number_format($box->price_per_spin, 2) }}</span> / ครั้ง</p>
    </div>
    <div class="flex items-center gap-3">
        <a href="{{ route('gacha.show', $box) }}" target="_blank" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold hover:bg-slate-700">👁️ ดูหน้าสุ่มจริง</a>
        <a href="{{ route('admin.gacha.edit', $box) }}" class="rounded-lg border border-slate-700 px-4 py-2 text-sm font-semibold hover:bg-slate-800">แก้ไขกล่อง</a>
    </div>
</div>

{{-- Summary Alert for Drop Rates --}}
<div class="mt-6 flex flex-wrap items-center justify-between gap-4 rounded-xl border p-4 {{ abs($totalRate - 100) < 0.01 ? 'border-emerald-500/40 bg-emerald-950/20 text-emerald-300' : 'border-amber-500/40 bg-amber-950/20 text-amber-300' }}">
    <div class="flex items-center gap-3">
        <span class="text-2xl">{{ abs($totalRate - 100) < 0.01 ? '✅' : '⚠️' }}</span>
        <div>
            <p class="font-bold">ผลรวมโอกาสออกของรางวัลในกล่อง: <span class="text-lg underline">{{ number_format($totalRate, 2) }}%</span></p>
            <p class="text-xs opacity-80">
                @if(abs($totalRate - 100) < 0.01)
                    อัตราโอกาสออกรวมครบ 100% พอดิบพอดี ระบบจะกระจายความน่าจะเป็นตามเรทนี้
                @else
                    หมายเหตุ: ผลรวมเรทยังไม่ครบ 100% ระบบจะใช้น้ำหนักถ่วงเฉลี่ย (Weighted Drop) ตามสัดส่วนของรางวัลที่มีอยู่จริง
                @endif
            </p>
        </div>
    </div>
</div>

<div class="mt-8 grid gap-8 lg:grid-cols-3">
    {{-- Left 2 Cols: Existing Items Table and Rate Adjuster --}}
    <div class="lg:col-span-2 space-y-6">
        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-6">
            <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                <h2 class="text-xl font-bold text-white">รายการของรางวัลในกล่อง ({{ $box->items->count() }})</h2>
                <span class="text-xs text-slate-400">แก้ไขเรทแล้วกดบันทึกด้านล่าง</span>
            </div>

            <form action="{{ route('admin.gacha.items.rates', $box) }}" method="POST" class="mt-4">
                @csrf
                @method('PUT')

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-slate-800 text-xs font-semibold uppercase text-slate-400">
                            <tr>
                                <th class="py-3 px-3">ประเภท</th>
                                <th class="py-3 px-3">รายละเอียดของรางวัล</th>
                                <th class="py-3 px-3">สถานะ</th>
                                <th class="py-3 px-3 w-36">Drop Rate (%)</th>
                                <th class="py-3 px-3 text-right">ลบ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 text-slate-300">
                            @forelse($box->items as $item)
                                <tr class="hover:bg-slate-800/30">
                                    <td class="py-3 px-3">
                                        @if($item->reward_type === 'game_account')
                                            <span class="inline-flex items-center gap-1 rounded bg-violet-950 px-2 py-1 text-xs font-semibold text-violet-300 border border-violet-800">
                                                🎮 ไอดีเกม
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded bg-emerald-950 px-2 py-1 text-xs font-semibold text-emerald-300 border border-emerald-800">
                                                💰 เครดิต
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-3">
                                        @if($item->reward_type === 'game_account' && $item->account)
                                            <div>
                                                <p class="font-bold text-white">{{ $item->account->title }}</p>
                                                <p class="text-xs text-slate-400">เกม: {{ $item->account->category->name ?? '-' }} · ราคาปกติ ฿{{ number_format($item->account->price, 2) }}</p>
                                            </div>
                                        @elseif($item->reward_type === 'credit')
                                            <div>
                                                <p class="font-bold text-emerald-400">คืนเงิน ฿{{ number_format($item->credit_amount, 2) }}</p>
                                                <p class="text-xs text-slate-400">เติมเข้ากระเป๋า Wallet ผู้สุ่มทันที</p>
                                            </div>
                                        @else
                                            <span class="text-xs text-rose-400 italic">ไอดีถูกลบหรือไม่มีข้อมูล</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-3">
                                        @if($item->reward_type === 'game_account')
                                            @if($item->account?->status === 'available')
                                                <span class="rounded bg-emerald-500/20 px-2 py-0.5 text-xs font-medium text-emerald-300">พร้อมสุ่ม</span>
                                            @else
                                                <span class="rounded bg-rose-500/20 px-2 py-0.5 text-xs font-medium text-rose-300">ถูกสุ่มไปแล้ว</span>
                                            @endif
                                        @else
                                            <span class="rounded bg-sky-500/20 px-2 py-0.5 text-xs font-medium text-sky-300">ไม่จำกัด</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-3">
                                        <div class="flex items-center gap-1">
                                            <input type="number" step="0.0001" min="0" max="100" name="rates[{{ $item->id }}]" value="{{ (float)$item->drop_rate }}" class="w-24 rounded border border-slate-700 bg-slate-800 px-2.5 py-1 text-sm font-semibold text-white focus:border-violet-500 focus:outline-none">
                                            <span class="text-xs text-slate-400">%</span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-3 text-right">
                                        <button type="submit" form="delete-item-{{ $item->id }}" class="text-rose-400 hover:text-rose-300 text-xs hover:underline" onclick="return confirm('ยืนยันลบรางวัลนี้ออกจากกล่อง?')">
                                            ✕ นำออก
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-slate-500">
                                        ยังไม่มีของรางวัลในกล่องนี้ กรุณาเพิ่มรางวัลจากแบบฟอร์มด้านขวา
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($box->items->isNotEmpty())
                    <div class="mt-5 flex justify-end">
                        <button type="submit" class="rounded-lg bg-emerald-600 px-5 py-2 font-bold text-white hover:bg-emerald-500 shadow-md shadow-emerald-600/20">
                            💾 บันทึกการเปลี่ยนแปลง Drop Rates
                        </button>
                    </div>
                @endif
            </form>

            @foreach($box->items as $item)
                <form id="delete-item-{{ $item->id }}" action="{{ route('admin.gacha.items.delete', [$box, $item]) }}" method="POST" class="hidden">
                    @csrf
                    @method('DELETE')
                </form>
            @endforeach
        </div>
    </div>

    {{-- Right 1 Col: Add New Item Form --}}
    <div class="space-y-6">
        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-6">
            <h2 class="text-lg font-bold text-white flex items-center gap-2">
                <span>➕</span> เพิ่มของรางวัลลงกล่อง
            </h2>
            <p class="text-xs text-slate-400 mt-1">เลือกเพิ่มไอดีเกม หรือรางวัลปลอบใจเป็นเครดิต</p>

            <form action="{{ route('admin.gacha.items.add', $box) }}" method="POST" class="mt-5 space-y-4" id="addItemForm">
                @csrf

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400">ประเภทรางวัล</label>
                    <div class="mt-2 grid grid-cols-2 gap-2">
                        <label class="flex cursor-pointer items-center justify-center rounded-lg border border-slate-700 bg-slate-800/80 p-3 text-sm font-semibold text-slate-300 has-[:checked]:border-violet-500 has-[:checked]:bg-violet-600/20 has-[:checked]:text-violet-300">
                            <input type="radio" name="reward_type" value="game_account" checked class="hidden" onchange="toggleRewardType('game_account')">
                            🎮 ไอดีเกม
                        </label>
                        <label class="flex cursor-pointer items-center justify-center rounded-lg border border-slate-700 bg-slate-800/80 p-3 text-sm font-semibold text-slate-300 has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-600/20 has-[:checked]:text-emerald-300">
                            <input type="radio" name="reward_type" value="credit" class="hidden" onchange="toggleRewardType('credit')">
                            💰 คืนเครดิต
                        </label>
                    </div>
                </div>

                {{-- Game Account Selection --}}
                <div id="accountField">
                    <label for="reward-game-filter" class="block text-xs font-semibold text-slate-400">หมวดหมู่เกม</label>
                    <select id="reward-game-filter" class="mt-1.5 mb-4 w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2.5 text-sm"><option value="">ทุกเกม</option>@foreach($availableAccounts->pluck('category')->filter()->unique('id') as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400">เลือกไอดีเกมที่พร้อมขาย</label>
                    <select name="game_account_id" class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2.5 text-sm text-white focus:border-violet-500 focus:outline-none">
                        @forelse($availableAccounts as $acc)
                            <option value="{{ $acc->id }}" data-category="{{ $acc->category_id }}">
                                [{{ $acc->category->name ?? 'เกม' }}] {{ $acc->title }} (฿{{ number_format($acc->price, 2) }})
                            </option>
                        @empty
                            <option value="" disabled selected>-- ไม่มีไอดีว่างในสต็อก --</option>
                        @endforelse
                    </select>
                    <p class="mt-2 text-xs text-cyan-300">เมื่อสุ่มไอดีออก ระบบจะเพิ่มไอดีพร้อมขายในหมวดเดียวกันมาแทน ด้วยอัตราสุ่มเดิม หากไม่มีไอดีเหลือจะหยุดรางวัลนั้น</p>
                    @if($availableAccounts->isEmpty())
                        <p class="mt-1 text-xs text-amber-400">ไม่มีไอดีว่าง <a href="{{ route('admin.accounts.create') }}" class="underline font-bold">กดเพิ่มไอดีใหม่ก่อน</a></p>
                    @endif
                </div>

                {{-- Credit Amount Input --}}
                <div id="creditField" class="hidden">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400">จำนวนเงินเครดิตคืน (บาท)</label>
                    <input type="number" step="0.01" min="0.01" name="credit_amount" placeholder="เช่น 5.00 หรือ 15.00" class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white focus:border-violet-500 focus:outline-none">
                </div>

                {{-- Drop Rate --}}
                <div>
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400">อัตราการออก (Drop Rate %)</label>
                        <span class="text-xs text-slate-500">ทศนิยมได้ 4 ตำแหน่ง</span>
                    </div>
                    <div class="mt-1.5 flex items-center gap-2">
                        <input type="number" step="0.0001" min="0.0001" max="100" name="drop_rate" required placeholder="เช่น 1.5 หรือ 50" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm font-semibold text-white focus:border-violet-500 focus:outline-none">
                        <span class="text-sm font-bold text-slate-400">%</span>
                    </div>
                </div>

                <button type="submit" class="w-full mt-2 rounded-lg bg-violet-600 py-2.5 font-bold text-white shadow-md shadow-violet-600/30 hover:bg-violet-500">
                    + เพิ่มรางวัลลงในกล่อง
                </button>
            </form>
        </div>

        {{-- Quick Guide Box --}}
        <div class="rounded-2xl border border-slate-800/80 bg-slate-950/50 p-5 text-xs text-slate-400 space-y-2">
            <p class="font-bold text-slate-300">💡 เคล็ดลับการตั้งค่ากล่องสุ่ม:</p>
            <p>1. <strong>รางวัลใหญ่ (Jackpot):</strong> ใส่ไอดีเกมราคาสูง แล้วตั้งเรทน้อย เช่น 0.5% - 2%</p>
            <p>2. <strong>รางวัลกลาง:</strong> ไอดีเกมทั่วไป ตั้งเรทประมาณ 5% - 15%</p>
            <p>3. <strong>รางวัลปลอบใจ:</strong> รางวัลคืนเครดิต (เช่น กล่องละ 20 คืนเครดิต 5 บาท เรท 60%, คืน 10 บาท เรท 25%) ช่วยให้ผู้เล่นรู้สึกไม่เสียเปล่าและอยากสุ่มต่อ</p>
        </div>
    </div>
</div>

<script>
const gameFilter = document.getElementById('reward-game-filter');
const accountSelect = document.querySelector('select[name="game_account_id"]');
const accountOptions = [...accountSelect.options].map(option => option.cloneNode(true));
gameFilter.addEventListener('change', () => {
    const options = accountOptions.filter(option => !gameFilter.value || option.dataset.category === gameFilter.value);
    accountSelect.replaceChildren(...options.map(option => option.cloneNode(true)));
    if (!options.length) accountSelect.add(new Option('ไม่มีไอดีพร้อมขายในเกมนี้', ''));
});
function toggleRewardType(type) {
    const accField = document.getElementById('accountField');
    const credField = document.getElementById('creditField');
    if (type === 'game_account') {
        accField.classList.remove('hidden');
        credField.classList.add('hidden');
    } else {
        accField.classList.add('hidden');
        credField.classList.remove('hidden');
    }
}
</script>
@endsection
