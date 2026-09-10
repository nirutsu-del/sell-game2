@extends('layouts.app')

@section('content')
<section class="mx-auto max-w-md rounded-2xl border border-slate-800 bg-slate-900 p-8 text-center shadow-xl">
    <div class="text-5xl">👋</div>
    <h1 class="mt-5 text-2xl font-bold">ต้องการออกจากระบบหรือไม่?</h1>
    <p class="mt-3 text-slate-400">คุณจะต้องเข้าสู่ระบบอีกครั้งเพื่อซื้อสินค้าและใช้งาน Wallet</p>
    <div class="mt-7 flex justify-center gap-3">
        <a href="{{ route('shop.index') }}" class="rounded-lg border border-slate-700 px-5 py-3 font-semibold hover:bg-slate-800">ยกเลิก</a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="rounded-lg bg-red-600 px-5 py-3 font-semibold hover:bg-red-500">ออกจากระบบ</button>
        </form>
    </div>
</section>
@endsection
