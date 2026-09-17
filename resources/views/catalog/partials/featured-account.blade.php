@php
    $ready = $account->status === 'available';
    $demoItem = collect(config('demo_catalog', []))->first(fn ($item) => $item['title'] === $account->title);
    $statusLabel = match($account->status) {
        'available' => 'พร้อมซื้อ',
        'sold' => 'ขายแล้ว',
        'reserved' => 'จองแล้ว',
        default => 'ไม่พร้อมขาย',
    };
@endphp
<a href="{{ route('accounts.show', $account) }}" class="featured-account {{ $demoItem ? 'demo-account demo-tone-'.$demoItem['tone'] : '' }}">
    <div class="featured-account-picture">
        @if(!empty($account->images[0]))
            <img src="{{ asset('storage/'.$account->images[0]) }}" alt="" loading="lazy" width="640" height="480">
        @else
            <div class="featured-account-placeholder"><span aria-hidden="true">✦</span><span>{{ $account->category->name }}</span></div>
        @endif
        <span class="featured-account-reference">#{{ str_pad((string) $account->id, 4, '0', STR_PAD_LEFT) }}</span>
        @if($demoItem)<span class="demo-art-label">CONCEPT ART</span>@endif
    </div>
    <div class="featured-account-body">
        <p class="featured-account-game">{{ $account->category->name }}</p>
        <h3>{{ $account->title }}</h3>
        @if($demoItem)<div class="demo-account-tags"><span>{{ $demoItem['rank'] }}</span><span>{{ $demoItem['collection'] }}</span></div>@endif
        <p class="featured-account-status {{ $ready ? 'is-ready' : '' }}"><span aria-hidden="true"></span>{{ $statusLabel }}</p>
        <div class="featured-account-purchase"><div><span class="featured-account-price-label">ราคาไอดี</span><p class="featured-account-price">฿{{ number_format($account->price, 2) }}</p></div><span class="featured-account-arrow" aria-hidden="true">↗</span></div>
        <span class="featured-account-details">ดูรายละเอียดไอดี <span aria-hidden="true">→</span></span>
    </div>
</a>
