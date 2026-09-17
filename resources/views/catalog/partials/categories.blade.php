<div class="vault-categories grid gap-5 md:grid-cols-2">
@foreach($categories as $categoryCard)
    @if(request()->routeIs('shop.index'))
    <a class="game-selection-card" href="{{ route('catalog.category',$categoryCard) }}">
        <div class="game-selection-art">
            @if($categoryCard->image)<img src="{{ asset('storage/'.$categoryCard->image) }}" alt="" loading="lazy" width="640" height="640">@else<span class="game-selection-fallback" aria-hidden="true">✦</span>@endif
        </div>
        <div class="game-selection-caption"><div><span class="game-selection-eyebrow">EXPLORE GAME</span><h3>{{ $categoryCard->name }}</h3></div><span class="game-selection-arrow" aria-hidden="true">↗</span></div>
    </a>
    @else
    <a class="market-category" href="{{ route('catalog.category',$categoryCard) }}">
        <div class="market-category-art">
            @if($categoryCard->image)<img src="{{ asset('storage/'.$categoryCard->image) }}" alt="{{ $categoryCard->name }}" loading="lazy">@else
            <span aria-hidden="true">✦</span><strong>{{ $categoryCard->name }}</strong>
            @endif
        </div>
        <div class="flex items-center justify-between gap-3 p-5"><div><h3 class="text-lg font-bold">{{ $categoryCard->name }}</h3><p class="mt-1 text-xs text-slate-400">เลือกหมวดหมู่เพื่อดูไอดีเกม</p></div><span class="market-small-button">ดูสินค้า →</span></div>
    </a>
    @endif
@endforeach
</div>
