@extends('layouts.app')

@section('content')
    <div class="market-heading"><div><p>GAME ACCOUNTS</p><h1 class="text-3xl font-bold">เลือกไอดีเกมของคุณ</h1><p class="mt-2 !tracking-normal !text-slate-400">ดูรายละเอียดและยอด Wallet ให้พร้อมก่อนยืนยันซื้อ</p></div></div>
    <form class="market-form mb-8 grid gap-4 rounded-2xl border border-slate-800 bg-slate-900 p-5 md:grid-cols-4">
        <label>เลือกเกม<select name="category">
            <option value="">ทุกเกม</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((string) request('category') === (string) $category->id)>{{ $category->name }}</option>
            @endforeach
        </select></label>
        <label>ราคาต่ำสุด<input name="min_price" type="number" min="0" step="0.01" value="{{ request('min_price') }}" placeholder="เช่น 100"></label>
        <label>ราคาสูงสุด<input name="max_price" type="number" min="0" step="0.01" value="{{ request('max_price') }}" placeholder="เช่น 1,000"></label>
        <div class="flex items-end gap-3"><button class="market-button flex-1">ใช้ตัวกรอง</button>@if(request()->hasAny(['category','min_price','max_price']))<a class="py-3 text-sm text-slate-400 underline" href="{{ route('accounts.index') }}">ล้าง</a>@endif</div>
    </form>
    @if($accounts->isNotEmpty()) @include('catalog.partials.accounts') @else <div class="market-empty"><p class="font-bold">ไม่พบไอดีที่ตรงกับช่วงราคาหรือเกมที่เลือก</p><a href="{{ route('accounts.index') }}" class="mt-3 inline-block text-orange-300">ล้างตัวกรองและดูทั้งหมด →</a></div> @endif
    <div class="mt-8">
        {{ $accounts->links() }}
    </div>
@endsection
    
