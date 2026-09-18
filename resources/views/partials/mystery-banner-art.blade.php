<div class="mystery-banner-picture golden-dawn-banner">
    <img src="{{ asset('storage/gacha-banners/golden-dawn-full.webp') }}" alt="หีบสมบัติเปิดฝา มีลำแสงทองส่องออกจากด้านในกล่อง" width="1672" height="941" @if($lazy ?? true) loading="lazy" @endif decoding="async">
    <svg class="golden-dawn-effects" viewBox="0 0 1672 941" preserveAspectRatio="xMaxYMid slice" aria-hidden="true" focusable="false">
        <g class="golden-dawn-rays" fill="#ffe5a2"><path d="M1180 474 L925 100 L1090 90 Z" opacity=".09"/><path d="M1250 476 L1200 24 L1320 30 Z" opacity=".12"/><path d="M1320 475 L1455 85 L1530 125 Z" opacity=".08"/></g>
        <ellipse class="golden-dawn-glow" cx="1245" cy="465" rx="230" ry="64" fill="#ffcc71" opacity=".14"/>
        @for($i=0;$i<24;$i++)
        <circle class="golden-dawn-particle" cx="{{ 955 + ($i * 71) % 580 }}" cy="{{ 400 + ($i * 19) % 100 }}" r="{{ $i % 4 === 0 ? 3 : 1.7 }}" fill="{{ $i % 3 === 0 ? '#fff5d7' : '#ffd17c' }}" style="--spark-delay:-{{ $i * 0.31 }}s; --spark-drift:{{ ($i % 2 === 0 ? 1 : -1) * (14 + $i * 2) }}px"/>
        @endfor
    </svg>
</div>
