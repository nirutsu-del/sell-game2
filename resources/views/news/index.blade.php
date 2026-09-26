@extends('layouts.app')
@section('content')
<h1 class="mb-6 text-3xl font-bold">ข่าวสารและประกาศ</h1>
<div class="space-y-4">
    @forelse($items as $news)
    <a href="{{ route('news.show', $news) }}" class="block rounded-xl bg-slate-900 p-5"><h2 class="break-words font-bold">{{ $news->title }}</h2><p class="mt-2 text-sm text-slate-400">{{ $news->published_at?->copy()->timezone('Asia/Bangkok')->format('d/m/Y') }}</p></a>
    @empty
    <div class="rounded-xl border border-slate-800 bg-slate-900 p-8 text-center"><p class="text-slate-300">ยังไม่มีข่าวสารในขณะนี้</p><a class="mt-4 inline-block text-violet-300" href="{{ route('shop.index') }}">กลับหน้าร้าน →</a></div>
    @endforelse
</div>
<div class="mt-6">{{ $items->links() }}</div>
@endsection
