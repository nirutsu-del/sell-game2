@extends('layouts.app')
@section('content')
<article class="mx-auto max-w-3xl">
    <a class="text-violet-300" href="{{ route('shop.index') }}">← กลับหน้าร้าน</a>
    <h1 class="my-6 text-3xl font-bold">{{ $title }}</h1>
    @if($policy)
    <p class="mb-5 text-sm text-slate-400">อัปเดตล่าสุด {{ $policy->updated_at->copy()->timezone('Asia/Bangkok')->format('d/m/Y H:i') }}</p>
    <div class="whitespace-pre-line break-words rounded-xl border border-slate-800 bg-slate-900 p-6 leading-8">{{ $policy->content }}</div>
    @else
    <div class="rounded-xl border border-slate-800 bg-slate-900 p-6 leading-8"><p>ร้านยังไม่ได้เผยแพร่ข้อมูลหน้านี้</p><p class="text-slate-400">กรุณาติดต่อร้านเพื่อสอบถามรายละเอียดก่อนทำรายการ</p></div>
    @endif
    <a class="market-button mt-6 inline-block" href="{{ route('contact') }}">ติดต่อร้าน</a>
</article>
@endsection
