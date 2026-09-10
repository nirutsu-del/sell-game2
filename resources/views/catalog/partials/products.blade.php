<div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
@foreach($products as $product)
    @php $variants = $product->variants; $ready = $variants->contains(fn($v)=>$v->stock === null || $v->stock > 0); @endphp
    <a href="{{ route('products.show',$product) }}" class="market-card">
        <div class="market-product-art">@if($product->image)<img src="{{ asset('storage/'.$product->image) }}" alt="{{ $product->name }}" loading="lazy">@else<span>🎮</span>@endif</div>
        <div class="p-4"><p class="text-xs text-orange-300">{{ $product->category->name }} · บริการ</p><h3 class="my-2 font-bold">{{ $product->name }}</h3>
        <p class="font-bold text-orange-400">@if($variants->isNotEmpty())฿{{ number_format($variants->min('price'),2) }}@if($variants->min('price') != $variants->max('price')) – {{ number_format($variants->max('price'),2) }}@endif @else รอตั้งราคา @endif</p>
        <p class="mt-3 text-xs text-slate-400">{{ $ready ? 'เลือกแพ็กเกจ →' : 'สินค้าหมดชั่วคราว' }}</p></div>
    </a>
@endforeach
</div>
