<footer class="home-footer">
    <div class="home-footer-inner">
        <div class="home-footer-grid">
            <div class="home-footer-brand">
                <a class="brand" href="{{ route('shop.index') }}">@if($wordmarkSize)<img class="brand-wordmark" src="{{ asset('storage/'.$storeSettings->logo) }}" alt="{{ $storeSettings->name }}" width="{{ $wordmarkSize[0] }}" height="{{ $wordmarkSize[1] }}" loading="lazy">@else<span>{{ $storeSettings->name }}</span>@endif</a>
                <p>ไอดีที่ใช่ เกมที่คุณรัก<br>เลือกเกมถัดไปของคุณได้ที่นี่</p>
                <span class="home-footer-signature">FIND YOUR NEXT GAME.</span>
            </div>
            <nav aria-label="สำรวจร้าน"><h2>สำรวจร้าน</h2><a href="{{ route('catalog.index') }}">ไอดีเกมทั้งหมด</a><a href="{{ route('gacha.index') }}">กล่องสุ่ม</a><a href="{{ route('news.index') }}">ข่าวสารจากร้าน</a></nav>
            <nav aria-label="บัญชีและการซื้อ"><h2>บัญชีของคุณ</h2><a href="{{ route('user.dashboard') }}">บัญชีของฉัน</a><a href="{{ route('wallet.index') }}">เติมเงิน Wallet</a><a href="{{ route('orders.index') }}">ประวัติคำสั่งซื้อ</a></nav>
            <div class="home-footer-contact"><h2>ให้ร้านช่วยดูแล</h2><a href="{{ route('contact') }}">ติดต่อ / ฝากข้อความ <span aria-hidden="true">↗</span></a>@foreach($storeSettings->contactLinks() as $channel)<a href="{{ $channel['url'] }}" target="_blank" rel="noopener noreferrer">{{ $channel['label'] }} ↗</a>@endforeach @if($storeSettings->opening_hours)<p class="home-footer-hours">{{ $storeSettings->opening_hours }}</p>@endif</div>
        </div>
        <div class="home-footer-bottom"><p>© {{ now()->year }} {{ $storeSettings->name }}</p><a href="#main-content">กลับขึ้นด้านบน ↑</a></div>
    </div>
</footer>
