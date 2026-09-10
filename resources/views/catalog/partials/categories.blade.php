<div class="grid gap-5 md:grid-cols-2">
@foreach($categories as $categoryCard)
    <a class="market-category" href="{{ route('catalog.category',$categoryCard) }}">
        <div class="market-category-art">
            @if($categoryCard->image)<img src="{{ asset('storage/'.$categoryCard->image) }}" alt="{{ $categoryCard->name }}" loading="lazy">@else
            <span aria-hidden="true">✦</span><strong>{{ $categoryCard->name }}</strong>
            @endif
        </div>
        <div class="flex items-center justify-between gap-3 p-5"><div><h3 class="text-lg font-bold">{{ $categoryCard->name }}</h3><p class="mt-1 text-xs text-slate-400">เลือกหมวดหมู่เพื่อดูไอดีเกม</p></div><span class="market-small-button">ดูสินค้า →</span></div>
    </a>
@endforeach
</div>
