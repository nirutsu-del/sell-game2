@extends('layouts.app')
@section('content')
<a class="collection-browse mb-5" href="{{ route('user.collection') }}">← ห้องโชว์คอลเลกชันของฉัน</a>
<button type="button" class="reveal-replay" data-replay-reveal>✦ ชมฉากเปิดตัวการ์ด</button>
<dialog class="vault-dialog acquisition-dialog" id="acquisition-reveal" aria-labelledby="acquisition-title" data-acquisition-reveal data-purchase-id="{{ $purchase->id }}" data-theme-user="{{ auth()->id() }}" data-auto-reveal="{{ (string)session('reveal_purchase_id') === (string)$purchase->id ? 'true' : 'false' }}">
    <button type="button" class="acquisition-close" data-close-dialog aria-label="ปิดฉากและดูข้อมูลไอดี">×</button>
    <div class="acquisition-glow" aria-hidden="true"></div><p class="acquisition-eyebrow">NEW IN YOUR COLLECTION</p><h2 id="acquisition-title">ไอดีใหม่เข้าคอลเลกชันแล้ว!</h2>
    <div class="acquisition-card">@if(!empty($purchase->account->images[0]))<img src="{{ asset('storage/'.$purchase->account->images[0]) }}" alt="{{ $purchase->account->title }}">@else<span class="acquisition-placeholder" aria-hidden="true">✦</span>@endif<div><span>{{ $purchase->account->category?->name ?? 'ไอดีเกม' }}</span><h3>{{ $purchase->account->title }}</h3></div></div>
    <div class="acquisition-actions"><a class="market-button" href="{{ route('user.collection') }}">ไปห้องคอลเลกชัน ↗</a><button type="button" data-close-dialog>ดูข้อมูลไอดีที่ได้รับ</button></div>
</dialog>
<div class="mx-auto max-w-3xl">
    <div class="market-heading"><div><p>PURCHASE A-{{ $purchase->id }}</p><h1 class="text-3xl font-bold">รับข้อมูลไอดีเรียบร้อย</h1><p class="mt-2 !tracking-normal !text-slate-400">ข้อมูลนี้แสดงเฉพาะเจ้าของรายการ โปรดเก็บไว้ในที่ปลอดภัย</p></div><a href="{{ route('orders.index') }}">ประวัติทั้งหมด →</a></div>
    <section class="rounded-2xl border border-emerald-500/30 bg-slate-900 p-6 sm:p-8">
        <div class="flex flex-wrap items-start justify-between gap-4"><div><p class="text-sm text-emerald-300">ซื้อสำเร็จ</p><h2 class="mt-1 text-xl font-bold">{{ $purchase->account->title }}</h2></div><div class="text-right"><p class="font-bold text-orange-300">฿{{ number_format($purchase->price_paid, 2) }}</p><time class="text-xs text-slate-400">{{ $purchase->created_at->copy()->timezone('Asia/Bangkok')->format('d/m/Y H:i') }}</time></div></div>
        <dl class="mt-6 space-y-4">@foreach($credentials as $key=>$value)<div><dt class="mb-2 text-sm text-slate-400">{{ ucfirst($key) }}</dt><dd class="vault-credential"><code data-credential-value>{{ $value }}</code><button type="button" data-copy-credential>คัดลอก</button></dd></div>@endforeach</dl>
        <p id="credential-status" role="status" aria-live="polite" class="mt-4 text-sm text-cyan-300"></p>
    </section>
    <p class="mt-4 text-sm text-slate-400">แนะนำให้เปลี่ยนรหัสผ่านและข้อมูลกู้คืนเมื่อเกมรองรับ</p>
</div>
@endsection
