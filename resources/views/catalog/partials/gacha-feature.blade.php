@if($featuredBox)
@php($boxReady = $featuredBox->eligibleItems()->isNotEmpty())
<section class="home-gacha" aria-labelledby="home-gacha-title">
    <div class="home-gacha-copy">
        <p class="home-gacha-eyebrow"><span aria-hidden="true">✦</span> MIZUKI MYSTERY BOX</p>
        <h2 id="home-gacha-title">เปิดกล่อง<br><span>ลุ้นไอดีถัดไปของคุณ</span></h2>
        <p class="home-gacha-description">สำรวจรางวัลในกล่อง {{ $featuredBox->name }}<br>ดูรายการรางวัลและโอกาสได้รับก่อนตัดสินใจสุ่ม</p>
        <div class="home-gacha-details">
            <div><span class="home-gacha-label">ราคาต่อครั้ง</span><strong>฿{{ number_format($featuredBox->price_per_spin, 2) }}</strong></div>
            <span class="home-gacha-status {{ $boxReady ? 'is-ready' : '' }}">{{ $boxReady ? 'มีรางวัลพร้อมสุ่ม' : 'รางวัลหมดชั่วคราว' }}</span>
        </div>
        <div class="home-gacha-actions">
            <a class="market-button" href="{{ route('gacha.show', $featuredBox) }}">ดูรางวัลในกล่อง <span aria-hidden="true">↗</span></a>
            <a class="home-gacha-all" href="{{ route('gacha.index') }}">ดูกล่องทั้งหมด →</a>
        </div>
    </div>
    <div class="home-gacha-art has-mystery-banner">
        @include('partials.mystery-banner-art')
    </div>
</section>
@endif
