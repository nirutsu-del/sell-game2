@extends('layouts.app')
@section('content')
<div class="mx-auto max-w-3xl">
    <h1 class="mb-4 text-3xl font-bold">นโยบายร้าน</h1>
    <p class="mb-6 leading-7 text-slate-400">กรอกเงื่อนไขที่ร้านให้บริการจริง แล้วเลือกเผยแพร่เพื่อให้ลูกค้าอ่านได้ ข้อความแนะนำด้านล่างเป็นหัวข้อช่วยเขียนและจะไม่แสดงในหน้าร้าน</p>
    @foreach(\App\Models\StorePolicy::PAGES as $slug => $title)
    @php($policy = $policies->get($slug))
    @php($submitted = session('_old_input') && old('_policy') === $slug)
    @php($content = $submitted ? old('content') : $policy?->content)
    <form method="POST" action="{{ route('admin.policies.update', $slug) }}" class="mb-6 space-y-4 rounded-xl border border-slate-800 bg-slate-900 p-6">
        @csrf @method('PUT')
        <input type="hidden" name="_policy" value="{{ $slug }}">
        <h2 class="text-xl font-bold">{{ $title }}</h2>
        <p id="{{ $slug }}-help" class="text-sm leading-7 text-slate-400">{{ \App\Models\StorePolicy::GUIDANCE[$slug] }}</p>
        <label for="{{ $slug }}-content" class="block">เนื้อหา</label>
        <textarea id="{{ $slug }}-content" name="content" rows="10" maxlength="50000" aria-describedby="{{ $slug }}-help" class="w-full rounded-lg border border-slate-700 bg-slate-950 p-3">{{ is_string($content) ? $content : '' }}</textarea>
        @if($submitted) @error('content')<p class="text-sm text-red-300">{{ $message }}</p>@enderror @endif
        <label for="{{ $slug }}-status" class="block">สถานะ</label>
        @php($published = in_array($submitted ? old('is_published') : $policy?->is_published, [true, 1, '1'], true))
        <select id="{{ $slug }}-status" name="is_published" class="w-full rounded-lg border border-slate-700 bg-slate-950 p-3"><option value="0" @selected(!$published)>ฉบับร่าง — ยังไม่แสดงเนื้อหาหน้าร้าน</option><option value="1" @selected($published)>เผยแพร่ — แสดงหน้าร้านทันที</option></select>
        <div class="flex flex-wrap items-center gap-4"><button type="submit" class="market-button">บันทึก</button><a class="text-violet-300" href="{{ route('policies.show', $slug) }}">ดูหน้าร้าน →</a></div>
    </form>
    @endforeach
</div>
@endsection
