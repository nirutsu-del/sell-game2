@php($accountCount = $eligible->where('reward_type', 'game_account')->unique('game_account_id')->count())
<nav class="room-breadcrumb" aria-label="เส้นทางนำทาง"><a href="{{ route('shop.index') }}">⌂ &nbsp; หน้าแรก</a><span>›</span><a href="{{ route('gacha.index') }}">สุ่มไอดี</a><span>›</span><span>{{ $box->name }}</span></nav>
<header class="room-heading">
    <div class="room-heading-main"><div class="room-game-icon">@if($box->image)<img src="{{ asset('storage/'.$box->image) }}" alt="">@else<span aria-hidden="true">✦</span>@endif</div><div><h1>{{ $box->name }}</h1><p>{{ $box->description ?: 'ลุ้นรับเครดิต และไอดีเกมสุดพิเศษ' }}</p></div></div>
    <div class="room-motto" aria-hidden="true">GAME ACCOUNTS<br>CHANGE<br><span>GOOD DAYS</span></div>
</header>
<div class="room-columns">
    <section id="gacha-stage" class="gacha-stage room-stage" aria-label="ห้องเปิดกล่องสุ่ม">
        <div class="room-stage-heading"><span><b>02 /</b> CARD ROOM</span><span class="room-stage-tagline">RANDOM ACCOUNTS, BRIGHTER TOMORROW</span><button id="gacha-sound" type="button" aria-pressed="false">เสียง: ปิด</button></div>
        <div class="room-stage-art">
            <div id="gacha-cards-scene" class="gacha-cards-scene"><div class="gacha-card-choices">@for($i=0;$i<5;$i++)<button type="button" class="gacha-choice" data-card-index="{{ $i }}" aria-pressed="false" aria-label="เลือกการ์ดใบที่ {{ $i+1 }}"><span class="gacha-choice-back"><span class="room-card-number">0{{ $i+1 }}</span></span><span class="gacha-choice-front"></span></button>@endfor</div></div>
            <div id="gacha-wheel-scene" class="gacha-wheel-scene" hidden><div class="gacha-wheel-wrap"><span class="gacha-wheel-pointer" aria-hidden="true">▼</span><div id="gacha-wheel" class="gacha-wheel" aria-hidden="true"></div><span class="gacha-wheel-hub" aria-hidden="true">✦</span></div></div>
            <div id="gacha-chest" class="gacha-chest room-royal-chest" hidden><div class="gacha-orbit" aria-hidden="true"></div><img src="{{ asset('images/gacha/royal-chest-cutout.webp') }}" alt="หีบสมบัติกรมท่าขอบทอง พร้อมตราดาว" decoding="async" width="960" height="800"></div>
            <p id="gacha-box-note" hidden>ภาพเปิดกล่องเป็นแอนิเมชันแสดงผลรางวัลจากเซิร์ฟเวอร์เท่านั้น</p>
            <div id="gacha-reel" class="gacha-reel" hidden><div class="gacha-pointer" aria-hidden="true">▼</div><div id="gacha-track" class="gacha-track"></div></div>
        </div>
        <p id="gacha-status" role="status" aria-live="polite">เลือกการ์ด 1 ใบ แล้วกดสุ่ม · การเลือกใบไม่เปลี่ยนโอกาสได้รับรางวัล</p>
        <p id="gacha-error" role="alert" hidden></p>
    </section>
    <aside class="room-rewards" aria-labelledby="room-rewards-title">
        <div class="room-rewards-heading"><h2 id="room-rewards-title">♔ &nbsp;รางวัลในกล่องนี้</h2><span id="gacha-stock">{{ $accountCount ? 'เหลือ '.$accountCount.' ไอดี' : ($eligible->isNotEmpty() ? 'รางวัลเครดิต' : 'รางวัลหมด') }}</span></div>
        <div id="gacha-rewards-list">
            @forelse($rewards as $reward)
                <article class="room-reward {{ $reward['type'] === 'game_account' ? 'is-account' : '' }}">
                    <div class="room-reward-art">@if($reward['image'])<img src="{{ $reward['image'] }}" alt="" loading="lazy">@elseif($reward['type'] === 'credit')<span class="room-coin" aria-hidden="true">฿</span>@else<span aria-hidden="true">🎮</span>@endif</div>
                    <h3>{{ $reward['title'] }}</h3><span class="room-chance" aria-label="โอกาส {{ number_format($reward['chance'], 4) }}%">{{ rtrim(rtrim(number_format($reward['chance'], 4, '.', ''), '0'), '.') }}%</span>
                </article>
            @empty<p class="room-empty">รางวัลหมดชั่วคราว</p>@endforelse
        </div>
        <p class="room-rates-note">ⓘ &nbsp;โอกาสคำนวณจากรางวัลที่ยังพร้อมสุ่ม และอาจเปลี่ยนเมื่อมีผู้ได้รับไอดีไปแล้ว</p>
    </aside>
</div>
<section class="room-controls" aria-label="เลือกวิธีสุ่มและชำระเงิน">
    <div class="room-mode-group"><span>เลือกวิธีการสุ่ม</span><div class="gacha-modes" role="group" aria-label="รูปแบบการสุ่ม"><button type="button" data-gacha-mode="cards" aria-pressed="true">♧ &nbsp; เลือกการ์ด</button><button type="button" data-gacha-mode="wheel" aria-pressed="false">◉ &nbsp; วงล้อ</button><button type="button" data-gacha-mode="box" aria-pressed="false">◇ &nbsp; เปิดกล่อง</button></div></div>
    <div class="room-price">฿{{ number_format($box->price_per_spin, 2) }}<small>/ ครั้ง</small></div>
    <div class="room-submit">
        @auth
            <a class="room-wallet" href="{{ route('wallet.index') }}">Wallet <strong id="gacha-balance">฿{{ number_format(auth()->user()->balance, 2) }}</strong> · เติมเงิน</a>
            <button id="gacha-spin" class="gacha-primary" type="button">สุ่ม 1 ครั้ง · ฿{{ number_format($box->price_per_spin, 2) }}</button>
            <button id="gacha-skip" type="button" hidden>ข้ามฉากสุ่ม</button>
        @else<a href="{{ route('login') }}" class="gacha-primary">เข้าสู่ระบบเพื่อสุ่ม</a>@endauth
        <p>การเลือกการ์ดไม่เปลี่ยนโอกาสได้รับรางวัล</p>
    </div>
</section>
