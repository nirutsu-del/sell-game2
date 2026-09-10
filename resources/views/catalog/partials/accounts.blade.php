<div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
@foreach($accounts as $account)
    <a href="{{ route('accounts.show',$account) }}" class="market-card">
        <div class="market-product-art">@if(!empty($account->images[0]))<img src="{{ asset('storage/'.$account->images[0]) }}" alt="{{ $account->title }}" loading="lazy">@else<span>🕹️</span>@endif</div>
        <div class="p-4"><p class="text-xs text-emerald-300">{{ $account->category->name }} · รับไอดีทันที</p><h3 class="my-2 font-bold">{{ $account->title }}</h3><p class="font-bold text-orange-400">฿{{ number_format($account->price,2) }}</p><p class="mt-3 text-xs text-slate-400">พร้อมส่ง 1 ไอดี →</p></div>
    </a>
@endforeach
</div>
