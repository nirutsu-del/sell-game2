@extends('layouts.app')
@section('content')
<div class="market-heading"><div><p>ADMIN / CATALOG</p><h1 class="text-2xl font-bold">สินค้าและแพ็กเกจบริการ</h1></div><a class="market-button" href="{{ route('admin.products.create') }}">+ เพิ่มสินค้า</a></div>
<p class="mb-5 text-sm text-slate-400">สำหรับงานบริการที่ร้านดำเนินการให้ลูกค้า ส่วนไอดีส่งมอบอัตโนมัติใช้เมนูจัดการไอดีเดิม</p>
<div class="space-y-3">@forelse($products as $product)<a class="market-shortcut justify-between" href="{{ route('admin.products.edit',$product) }}"><div><p class="font-bold">{{ $product->name }}</p><p class="text-xs text-slate-400">{{ $product->category->name }} · {{ $product->variants->count() }} แพ็กเกจ · {{ $product->is_active ? 'เปิดขาย' : 'ปิดขาย' }}</p></div><span>แก้ไข →</span></a>@empty<p class="market-empty">ยังไม่มีสินค้า กดเพิ่มสินค้าเพื่อสร้างแพ็กเกจแรก</p>@endforelse</div>
<div class="mt-5">{{ $products->links() }}</div>
@endsection
