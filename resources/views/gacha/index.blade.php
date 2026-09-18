@extends('layouts.app')

@section('content')
<div class="space-y-10">
    {{-- Hero Section --}}
    <div class="mystery-catalog-hero relative overflow-hidden rounded-3xl border border-violet-800/30 bg-gradient-to-br from-violet-950/60 via-slate-900 to-slate-950 p-8 sm:p-12">
        @include('partials.mystery-banner-art', ['lazy'=>false])
        <div class="mystery-catalog-shade" aria-hidden="true"></div>
        <div class="absolute -right-16 -top-16 h-64 w-64 rounded-full bg-violet-600/20 blur-3xl pointer-events-none"></div>
        <div class="absolute -left-16 -bottom-16 h-64 w-64 rounded-full bg-fuchsia-600/15 blur-3xl pointer-events-none"></div>

        <div class="mystery-catalog-copy relative z-10 max-w-2xl">
            <div class="inline-flex items-center gap-2 rounded-full border border-violet-500/30 bg-violet-500/10 px-3 py-1 text-xs font-semibold text-violet-300">
                <span class="animate-pulse">✨</span> LUCKY GACHA REWARDS
            </div>
            <h1 class="mt-3 text-3xl sm:text-5xl font-extrabold tracking-tight text-white">
                กล่องสุ่มไอดีเกม <span class="bg-gradient-to-r from-violet-400 via-fuchsia-400 to-pink-400 bg-clip-text text-transparent">ลุ้นรางวัลใหญ่</span>
            </h1>
            <p class="mt-4 text-sm sm:text-base text-slate-300 leading-relaxed">
                เลือกกล่องที่คุณสนใจ เปิดฉากสุ่มและลุ้นรับไอดีเกมหรือเครดิตเข้า Wallet ดูรายการรางวัลและโอกาสได้รับก่อนเริ่มทุกครั้ง
            </p>

            <div class="mt-6 flex flex-wrap gap-4 text-xs sm:text-sm text-slate-400">
                <div class="flex items-center gap-2">
                    <span class="text-emerald-400">✓</span> ส่งมอบไอดีอัตโนมัติทันที
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-emerald-400">✓</span> เรทออกโปร่งใส ตรวจสอบได้
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-emerald-400">✓</span> คืนเครดิตเข้ากระเป๋าจริง
                </div>
            </div>
        </div>
    </div>

    {{-- Live Lucky Winners Ticker --}}
    @if(isset($recentWinners) && $recentWinners->isNotEmpty())
        <div class="flex items-center gap-3 overflow-hidden rounded-xl border border-amber-500/30 bg-amber-950/20 px-4 py-3 text-sm text-amber-200">
            <div class="flex items-center gap-1.5 shrink-0 font-bold text-amber-400">
                <span class="animate-bounce">🏆</span> ผู้โชคดีล่าสุด:
            </div>
            <div class="flex items-center gap-6 overflow-x-auto whitespace-nowrap scrollbar-none py-1">
                @foreach($recentWinners as $winner)
                    <div class="flex items-center gap-2 text-xs">
                        <span class="font-semibold text-white">{{ Str::mask($winner->user->name ?? 'User', '*', 2, 4) }}</span>
                        <span class="text-slate-400">ได้รับ</span>
                        <span class="font-bold text-amber-300 bg-amber-500/20 px-2 py-0.5 rounded border border-amber-500/30">
                            {{ $winner->item->account->title ?? 'ไอดีเกมสุดแรร์' }}
                        </span>
                        <span class="text-slate-500 text-[10px]">({{ $winner->created_at->copy()->locale('th')->diffForHumans() }})</span>
                        <span class="text-slate-700">|</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Boxes Catalog Grid --}}
    <div>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl font-bold text-white">กล่องสุ่มทั้งหมดที่เปิดให้บริการ</h2>
                <p class="text-sm text-slate-400">เลือกกล่องสุ่มที่คุณสนใจเพื่อเข้าสู่ห้องสุ่มไอดี</p>
            </div>
            @if(auth()->check() && auth()->user()->isAdmin())
                <a href="{{ route('admin.gacha.index') }}" class="text-xs text-violet-400 hover:underline">
                    ⚙️ จัดการกล่องสุ่ม (Admin)
                </a>
            @endif
        </div>

        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($boxes as $box)
                @php
                    $eligible = $box->eligibleItems();
                    $availableCount = $eligible->where('reward_type', 'game_account')->unique('game_account_id')->count();
                    $hasCredit = $eligible->contains('reward_type', 'credit');
                    $ready = $eligible->isNotEmpty();
                    $stockLabel = $availableCount > 0
                        ? 'เหลือ '.$availableCount.' ไอดี'.($hasCredit ? ' + รางวัลเครดิต' : '')
                        : ($hasCredit ? 'พร้อมสุ่มรางวัลเครดิต' : 'รางวัลหมดชั่วคราว');
                    $topAccount = $eligible
                        ->where('reward_type', 'game_account')
                        ->filter(fn($i) => $i->account && $i->account->status === 'available')
                        ->sortByDesc(fn($i) => $i->account->price)
                        ->first()?->account;
                @endphp
                <div class="group relative flex flex-col justify-between overflow-hidden rounded-2xl border border-slate-800 bg-slate-900 transition duration-300 hover:border-violet-500/60 hover:shadow-xl hover:shadow-violet-900/20">
                    
                    {{-- Header / Cover --}}
                    <div class="relative aspect-[16/10] overflow-hidden bg-slate-950 flex items-center justify-center">
                        @if($box->image)
                            <img src="{{ asset('storage/'.$box->image) }}" alt="{{ $box->name }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                        @else
                            <div class="flex flex-col items-center justify-center p-6 text-center animate-glow-pulse">
                                <span class="text-6xl filter drop-shadow-lg">🎁</span>
                                <span class="mt-2 text-xs font-semibold tracking-wider uppercase text-violet-400">Mystery Box</span>
                            </div>
                        @endif

                        {{-- Available Stock Badge --}}
                        <div class="absolute top-3 right-3">
                            <span class="rounded-full px-3 py-1 text-xs font-bold backdrop-blur-md {{ $ready ? 'bg-emerald-600/80 text-emerald-100 border border-emerald-400/40' : 'bg-rose-600/80 text-rose-100 border border-rose-400/40' }}">
                                {{ $stockLabel }}
                            </span>
                        </div>

                        {{-- Price Tag --}}
                        <div class="absolute bottom-3 left-3">
                            <span class="rounded-lg bg-slate-950/80 px-3 py-1.5 text-base font-extrabold text-emerald-400 backdrop-blur-md border border-slate-800">
                                ฿{{ number_format($box->price_per_spin, 2) }} <span class="text-xs font-normal text-slate-400">/ ครั้ง</span>
                            </span>
                        </div>
                    </div>

                    {{-- Body --}}
                    <div class="flex flex-1 flex-col justify-between p-6">
                        <div>
                            <h3 class="text-xl font-bold text-white group-hover:text-violet-300 transition">
                                {{ $box->name }}
                            </h3>
                            <p class="mt-2 text-xs text-slate-400 line-clamp-2 leading-relaxed">
                                {{ $box->description ?: 'เปิดกล่องเพื่อลุ้นรับไอดีเกมหรือเครดิต Wallet ตรวจสอบรางวัลในห้องสุ่มได้เลย' }}
                            </p>

                            {{-- Highlight Jackpot --}}
                            @if($topAccount)
                                <div class="mt-4 rounded-xl border border-violet-500/20 bg-violet-950/30 p-3 text-xs">
                                    <p class="font-bold text-violet-300 flex items-center gap-1">
                                        <span>👑</span> รางวัลใหญ่ในกล่องนี้:
                                    </p>
                                    <p class="mt-1 font-medium text-slate-200 truncate">{{ $topAccount->title }}</p>
                                    <p class="text-[11px] text-slate-400">มูลค่าปกติ ฿{{ number_format($topAccount->price, 2) }}</p>
                                </div>
                            @endif
                        </div>

                        {{-- Action Buttons --}}
                        <div class="mt-6 pt-4 border-t border-slate-800/80 flex items-center justify-between gap-3">
                            <div class="text-xs text-slate-500">
                                สุ่มแล้ว {{ number_format($box->spins_count) }} ครั้ง
                            </div>
                            <a href="{{ route('gacha.show', $box) }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-violet-600 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-violet-600/30 transition hover:bg-violet-500 hover:shadow-violet-600/50">
                                เข้าห้องสุ่มไอดี ➔
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full rounded-2xl border border-slate-800 bg-slate-900 p-12 text-center">
                    <span class="text-5xl">🎁</span>
                    <h3 class="mt-4 text-xl font-bold text-white">ยังไม่มีกล่องสุ่มที่เปิดให้บริการในขณะนี้</h3>
                    <p class="mt-2 text-sm text-slate-400">ระบบกำลังเตรียมกล่องสุ่มใหม่ๆ โปรดติดตามเร็วๆ นี้</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
