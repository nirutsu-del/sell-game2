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
<div id="gacha-room" class="room-layout" style="--room-background: url('{{ asset('images/gacha/card-room-background.webp') }}'); --room-card-back: url('{{ asset('images/gacha/mizuki-card-back.webp') }}')">
    <script type="application/json" id="gacha-settings">@json($settings)</script>
    @include('gacha.room-layout')
    <section class="room-history rounded-2xl border border-slate-800 bg-slate-900 p-5">
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
