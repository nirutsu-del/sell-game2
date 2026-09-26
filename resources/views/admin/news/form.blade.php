@extends('layouts.app')
@section('content')
<div class="mx-auto max-w-3xl">
    <a class="text-violet-300" href="{{ route('admin.news.index') }}">← กลับหน้าจัดการข่าวสาร</a>
    <h1 class="my-6 text-3xl font-bold">{{ $news->exists ? 'แก้ไขข่าวสาร' : 'เพิ่มข่าวสาร' }}</h1>
    <form method="POST" action="{{ $news->exists ? route('admin.news.update', $news) : route('admin.news.store') }}" class="space-y-5 rounded-xl border border-slate-800 bg-slate-900 p-6">
        @csrf
        @if($news->exists) @method('PUT') @endif
        <div><label for="news-title" class="mb-2 block">หัวข้อข่าว</label><input id="news-title" name="title" required maxlength="255" value="{{ (is_string(old('title', $news->title)) ? old('title', $news->title) : '') }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 p-3">@error('title')<p class="mt-2 text-sm text-red-300">{{ $message }}</p>@enderror</div>
        <div><label for="news-content" class="mb-2 block">เนื้อหาข่าว</label><textarea id="news-content" name="content" rows="12" required maxlength="50000" class="w-full rounded-lg border border-slate-700 bg-slate-950 p-3">{{ (is_string(old('content', $news->content)) ? old('content', $news->content) : '') }}</textarea><p class="mt-2 text-sm text-slate-400">ใช้ข้อความธรรมดาและขึ้นบรรทัดใหม่ได้</p>@error('content')<p class="mt-2 text-sm text-red-300">{{ $message }}</p>@enderror</div>
        <div><label for="news-status" class="mb-2 block">สถานะ</label><select id="news-status" name="is_published" class="w-full rounded-lg border border-slate-700 bg-slate-950 p-3"><option value="0" @selected(!in_array(old('is_published', $news->is_published), [true, 1, '1'], true))>ฉบับร่าง — ยังไม่แสดงหน้าร้าน</option><option value="1" @selected(in_array(old('is_published', $news->is_published), [true, 1, '1'], true))>เผยแพร่ — แสดงหน้าร้านทันที</option></select>@error('is_published')<p class="mt-2 text-sm text-red-300">{{ $message }}</p>@enderror</div>
        <div class="flex flex-wrap gap-3"><button type="submit" class="market-button">บันทึกข่าวสาร</button><a class="market-small-button" href="{{ route('admin.news.index') }}">ยกเลิก</a></div>
    </form>
</div>
@endsection
