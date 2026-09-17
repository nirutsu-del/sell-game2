@extends('layouts.app')
@section('content')
@php
    $eligible = $box->eligibleItems();
    $weight = $eligible->sum('drop_rate');
    $rewards = $eligible->map(fn($i) => [
        'id' => $i->id,
        'title' => $i->reward_type === 'credit' ? 'เครดิต ฿'.number_format($i->credit_amount, 2) : $i->account->title,
        'image' => $i->account && !empty($i->account->images[0]) ? asset('storage/'.$i->account->images[0]) : null,
        'type' => $i->reward_type,
        'chance' => $weight > 0 ? round($i->drop_rate / $weight * 100, 4) : 0,
    ])->values();
    $settings = [
        'url' => route('gacha.spin', $box), 'csrf' => csrf_token(),
        'balance' => (float) (auth()->user()?->balance ?? 0), 'price' => (float) $box->price_per_spin,
        'active' => $box->is_active, 'rewards' => $rewards,
        'storageKey' => 'gacha:'.(auth()->id() ?? 'guest').':'.$box->id,
        'walletUrl' => route('wallet.index'),
    ];
@endphp
<div id="gacha-room" class="mx-auto max-w-5xl space-y-7">
    <script type="application/json" id="gacha-settings">@json($settings)</script>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <a href="{{ route('gacha.index') }}" class="text-sm text-violet-300">← กลับไปเลือกกล่อง</a>
            <p class="mt-5 text-xs tracking-[.3em] text-violet-400">{{ \App\Models\StoreSetting::current()->name }} / MYSTERY ROOM</p>
            <h1 class="mt-2 text-3xl font-black sm:text-4xl">{{ $box->name }}</h1>
            <p class="mt-2 text-sm text-slate-400">{{ $box->description }}</p>
        </div>
        @auth
        <a href="{{ route('wallet.index') }}" class="rounded-xl border border-slate-700 bg-slate-900 px-5 py-3 text-sm">
            Wallet <strong id="gacha-balance" class="ml-3 text-emerald-400">฿{{ number_format(auth()->user()->balance, 2) }}</strong> · เติมเงิน
        </a>
        @endauth
    </div>
    <section id="gacha-stage" class="gacha-stage" aria-label="ห้องเปิดกล่องสุ่ม">
        <div class="relative z-10 flex items-center justify-between text-xs text-violet-300">
            <span>01 / OPEN YOUR MYSTERY</span>
            <button id="gacha-sound" type="button" aria-pressed="false" class="rounded-full border border-violet-400/30 px-3 py-2">เสียง: ปิด</button>
        </div>
        <div class="gacha-modes" role="group" aria-label="รูปแบบการสุ่ม"><button type="button" data-gacha-mode="cards" aria-pressed="true">✦ เลือกการ์ด</button><button type="button" data-gacha-mode="wheel" aria-pressed="false">◉ วงล้อ</button><button type="button" data-gacha-mode="box" aria-pressed="false">◇ เปิดกล่อง</button></div>
        <div id="gacha-cards-scene" class="gacha-cards-scene"><p>เลือกการ์ด 1 ใบ แล้วกดปุ่มสุ่มด้านล่าง</p><div class="gacha-card-choices">@for($i=0;$i<5;$i++)<button type="button" class="gacha-choice" data-card-index="{{ $i }}" aria-pressed="false" aria-label="เลือกการ์ดใบที่ {{ $i+1 }}"><span class="gacha-choice-back"><b>✦</b><small>MIZUKI</small><span>0{{ $i+1 }}</span></span><span class="gacha-choice-front"></span></button>@endfor</div></div>
        <div id="gacha-wheel-scene" class="gacha-wheel-scene" hidden><div class="gacha-wheel-wrap"><span class="gacha-wheel-pointer" aria-hidden="true">▼</span><div id="gacha-wheel" class="gacha-wheel" aria-hidden="true"></div><span class="gacha-wheel-hub" aria-hidden="true">✦</span></div><p>วงล้อเป็นภาพแสดงผล ช่องมีขนาดเท่ากันและไม่ใช่สัดส่วนโอกาสได้รับ</p><ol id="gacha-wheel-legend" class="gacha-wheel-legend"></ol></div>
        <div id="gacha-chest" class="gacha-chest" hidden>
            <div class="gacha-orbit"></div>
            @if($box->image)
                <img src="{{ asset('storage/'.$box->image) }}" alt="{{ $box->name }}" class="relative h-40 w-40 rounded-2xl object-contain">
            @else
                <div class="gacha-cube" aria-hidden="true"><span>✦</span></div>
            @endif
        </div>
        <div id="gacha-reel" class="gacha-reel" hidden>
            <div class="gacha-pointer" aria-hidden="true">▼</div>
            <div id="gacha-track" class="gacha-track"></div>
        </div>
        <p id="gacha-status" role="status" aria-live="polite" class="relative mt-5 text-center text-sm text-violet-200">เลือกการ์ดที่ชอบ การเลือกใบไม่เปลี่ยนโอกาสได้รับรางวัล</p>
        <div class="relative mt-6 flex flex-col items-center gap-3">
            @auth
                <button id="gacha-spin" class="gacha-primary" type="button">สุ่ม 1 ครั้ง · ฿{{ number_format($box->price_per_spin, 2) }}</button>
                <button id="gacha-skip" class="text-sm text-slate-300 underline" type="button" hidden>ข้ามฉากสุ่ม</button>
            @else
                <a href="{{ route('login') }}" class="gacha-primary">เข้าสู่ระบบเพื่อสุ่ม</a>
            @endauth
            <p class="text-xs text-slate-400">฿{{ number_format($box->price_per_spin, 2) }} ต่อครั้ง · ผลถูกบันทึกในประวัติ</p>
        </div>
        <p id="gacha-error" role="alert" class="relative mt-4 text-center text-sm text-rose-300" hidden></p>
    </section>
    <section>
        <h2 class="text-xl font-bold">รางวัลและโอกาสได้รับ</h2>
        <p class="mt-2 text-xs text-slate-400">คำนวณจากรางวัลที่ยังพร้อมสุ่มขณะเปิดหน้านี้ โอกาสอาจเปลี่ยนเมื่อมีผู้ได้รับไอดีไปแล้ว สีการ์ดบอกประเภทรางวัล</p>
        <div id="gacha-rewards-list" class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3">
            @forelse($rewards as $reward)
                <article class="rounded-2xl border border-slate-800 bg-slate-900 p-4">
                    @if($reward['image'])
                    <img src="{{ $reward['image'] }}" alt="{{ $reward['title'] }}" class="mb-3 h-28 w-full rounded-lg object-cover" loading="lazy">
                    @else
                    <div class="mb-3 flex h-28 items-center justify-center rounded-lg bg-slate-950 text-4xl">{{ $reward['type'] === 'credit' ? '💎' : '🎮' }}</div>
                    @endif
                    <p class="text-xs text-violet-300">{{ number_format($reward['chance'], 4) }}%</p>
                    <h3 class="mt-2 break-words text-sm font-bold">{{ $reward['title'] }}</h3>
                </article>
            @empty
                <p class="col-span-full rounded-xl bg-slate-900 p-8 text-center text-slate-400">รางวัลหมดชั่วคราว</p>
            @endforelse
        </div>
    </section>
    <section class="rounded-2xl border border-slate-800 bg-slate-900 p-5">
        <h2 class="text-lg font-bold">ประวัติการสุ่มของคุณ</h2>
        <p class="mt-1 text-xs text-slate-400">รีเฟรชหรือออกจากฉากสุ่ม รางวัลยังอยู่ที่นี่</p>
        <div id="gacha-history" class="mt-4 divide-y divide-slate-800">
        @foreach($recentSpins ?? [] as $spin)
            <div class="flex flex-wrap items-center justify-between gap-2 py-3 text-sm">
                <div><p>{{ $spin->result_data['result'] ?? ($spin->item?->reward_type === 'credit' ? 'เครดิต ฿'.number_format($spin->item->credit_amount, 2) : $spin->item?->account?->title) }}</p>
                <p class="mt-1 text-xs text-slate-500">#{{ $spin->id }} · {{ $spin->created_at->copy()->timezone('Asia/Bangkok')->format('d/m/Y H:i') }}</p></div>
                @if($spin->purchase_history_id)<a class="text-violet-300 underline" href="{{ route('purchases.show', $spin->purchase_history_id) }}">ดูข้อมูลไอดี</a>@endif
            </div>
        @endforeach
        </div>
        @guest<p class="mt-3 text-sm text-slate-400">เข้าสู่ระบบเพื่อดูประวัติ</p>@endguest
    </section>
</div>
<dialog id="gacha-result" class="gacha-result" aria-labelledby="gacha-result-title">
    <div class="gacha-sparkles" aria-hidden="true">✦ · ✧ · ✦</div>
    <p class="text-xs tracking-[.3em] text-violet-300">YOUR REWARD</p>
    <div id="gacha-result-art" class="my-6 text-7xl"></div>
    <h2 id="gacha-result-title" class="text-2xl font-black">ได้รับรางวัลแล้ว!</h2>
    <p id="gacha-result-text" class="mt-3 text-sm text-slate-300"></p>
    <a id="gacha-purchase" class="mt-5 inline-block text-violet-300 underline" hidden>ดูข้อมูลไอดีของคุณ</a>
    <div class="mt-6 grid gap-3">
        <button id="gacha-again" class="gacha-primary">สุ่มอีกครั้ง · ฿{{ number_format($box->price_per_spin, 2) }}</button>
        <button id="gacha-close" class="rounded-xl border border-slate-700 px-4 py-3">ปิดและดูประวัติ</button>
        <a href="{{ route('gacha.index') }}" class="text-sm text-slate-400">กลับไปเลือกกล่อง</a>
    </div>
</dialog>
@endsection
