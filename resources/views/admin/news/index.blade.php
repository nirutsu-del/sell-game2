@extends('layouts.app')
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <h1 class="text-3xl font-bold">จัดการข่าวสาร</h1>
    <a class="market-button" href="{{ route('admin.news.create') }}">+ เพิ่มข่าวสาร</a>
</div>
<div class="space-y-4">
    @forelse($items as $news)
    <article class="rounded-xl border border-slate-800 bg-slate-900 p-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0"><h2 class="break-words text-lg font-bold">{{ $news->title }}</h2>
                <p class="mt-2 text-sm text-slate-400">{{ $news->is_published ? 'เผยแพร่แล้ว' : 'ฉบับร่าง' }}@if($news->published_at) · เผยแพร่ครั้งแรก {{ $news->published_at->copy()->timezone('Asia/Bangkok')->format('d/m/Y H:i') }}@endif</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                @if($news->is_published)<a class="text-cyan-300" href="{{ route('news.show', $news) }}">ดูข่าว</a>@endif
                <a class="market-small-button" href="{{ route('admin.news.edit', $news) }}">แก้ไข</a>
                <form method="POST" action="{{ route('admin.news.destroy', $news) }}" onsubmit="return confirm('ยืนยันการลบข่าวนี้? เมื่อลบแล้วจะกู้คืนไม่ได้')">@csrf @method('DELETE')<button type="submit" class="rounded-lg bg-red-900/60 px-3 py-2 text-red-200">ลบ</button></form>
            </div>
        </div>
    </article>
    @empty
    <p class="market-empty">ยังไม่มีข่าวสาร กด “เพิ่มข่าวสาร” เพื่อสร้างข่าวแรก</p>
    @endforelse
</div>
<div class="mt-6">{{ $items->links() }}</div>
@endsection
