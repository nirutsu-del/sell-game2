@extends('layouts.app')

@section('content')
    <div class="mb-7">
        <h1 class="text-3xl font-bold">เลือกไอดีเกมของคุณ</h1>
        <p class="text-slate-400">ชำระผ่าน Wallet แล้วรับข้อมูลไอดีทันที</p>
    </div>
    <form class="mb-8 grid gap-3 rounded-xl bg-slate-900 p-4 md:grid-cols-4">
        <select name="category" class="rounded bg-slate-800 p-2">
            <option value="">ทุกเกม</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}">{{ $category->name }}</option>
            @endforeach
        </select>
        <input name="min_price" type="number" placeholder="ราคาต่ำสุด" class="rounded bg-slate-800 p-2">
        <input name="max_price" type="number" placeholder="ราคาสูงสุด" class="rounded bg-slate-800 p-2">
        <button class="rounded bg-violet-600 font-semibold">ค้นหา</button>
    </form>
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($accounts as $account)
            <a href="{{ route('accounts.show', $account) }}"
                class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900 transition hover:border-violet-500">
                @if (filled($account->images))
                    <img src="{{ request()->getBaseUrl() . '/storage/' . $account->images[0] }}" alt="{{ $account->title }}" class="h-36 w-full object-cover">
                @else
                    <div class="flex h-36 items-center justify-center bg-gradient-to-br from-violet-900 to-slate-800 text-4xl">🎮</div>
                @endif
                <div class="p-4">
                    <div class="text-xs text-violet-300">{{ $account->category->name }}</div>
                    <h2 class="mt-1 font-bold">{{ $account->title }}</h2>
                    <div class="mt-4 text-lg font-bold text-emerald-400">฿{{ number_format($account->price, 2) }}</div>
                </div>
            </a>
        @empty
            <p>ยังไม่มีสินค้า</p>
        @endforelse
    </div>
    <div class="mt-8">
        {{ $accounts->links() }}
    </div>
@endsection
    
