<div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
@foreach($accounts as $account)
    @if(request()->routeIs('shop.index'))
        @include('catalog.partials.featured-account', ['account' => $account])
    @else
    <a href="{{ route('accounts.show',$account) }}" class="market-card">
        <div class="market-product-art">@if(!empty($account->images[0]))<img src="{{ asset('storage/'.$account->images[0]) }}" alt="{{ $account->title }}" loading="lazy">@else<span>🕹️</span>@endif</div>
        <div class="p-4"><p class="text-xs text-emerald-300">● {{ $account->category->name }} · พร้อมซื้อ</p><h3 class="my-2 font-bold">{{ $account->title }}</h3><div class="vault-card-price"><p class="font-bold text-orange-400">฿{{ number_format($account->price,2) }}</p><span aria-hidden="true">↗</span></div><p class="mt-3 text-xs text-slate-400">ดูรายละเอียดไอดี →</p></div>
    </a>
    @endif
@endforeach
</div>
