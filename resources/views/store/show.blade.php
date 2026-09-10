@extends('layouts.app')

@section('content')
    <div class="grid gap-8 md:grid-cols-2">
        <div class="overflow-hidden rounded-2xl bg-slate-900">
            @if (filled($account->images))
                <img src="{{ request()->getBaseUrl() . '/storage/' . $account->images[0] }}" alt="{{ $account->title }}" class="min-h-72 w-full object-cover">
            @else
                <div class="flex min-h-72 items-center justify-center bg-gradient-to-br from-violet-900 to-slate-800 text-7xl">🎮</div>
            @endif
        </div>
        <div>
            <span class="text-violet-300">{{ $account->category->name }}</span>
            <h1 class="mt-2 text-3xl font-bold">{{ $account->title }}</h1>
            <p class="mt-5 whitespace-pre-line text-slate-300">{{ $account->description }}</p>
            <div class="mt-8 text-3xl font-black text-emerald-400">฿{{ number_format($account->price, 2) }}</div>
            <p class="mt-2 text-sm text-slate-400">ข้อมูล Username/Password จะแสดงหลังชำระเงินเท่านั้น</p>
            @auth
                <form method="POST" action="{{ route('accounts.buy', $account) }}" class="mt-6">
                    @csrf
                    <button class="rounded-lg bg-violet-600 px-6 py-3 font-bold">ซื้อด้วย Wallet</button>
                </form>
            @endauth
        </div>
    </div>
@endsection 
