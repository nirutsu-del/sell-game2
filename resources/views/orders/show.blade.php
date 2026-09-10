@extends('layouts.app')
@section('content')
<a href="{{ route('orders.index') }}" class="text-sm text-orange-300">← คำสั่งซื้อทั้งหมด</a>
<section class="mx-auto mt-6 max-w-2xl rounded-3xl border border-slate-700 bg-slate-900 p-6 sm:p-8">
<p class="text-xs tracking-widest text-orange-300">ORDER S-{{ $order->id }}</p><h1 class="mt-3 text-2xl font-bold">{{ $order->product_name }}</h1>
<p class="mt-4 rounded-lg bg-orange-500/10 p-3 text-orange-300">{{ $order->statusLabel() }}</p>
<dl class="mt-6 grid grid-cols-2 gap-4 text-sm"><dt class="text-slate-400">แพ็กเกจ</dt><dd>{{ $order->variant_name }}</dd><dt class="text-slate-400">จำนวน</dt><dd>{{ $order->quantity }}</dd><dt class="text-slate-400">ชำระผ่าน Wallet</dt><dd>฿{{ number_format($order->total,2) }}</dd><dt class="text-slate-400">วันที่สั่งซื้อ</dt><dd>{{ $order->created_at->copy()->timezone('Asia/Bangkok')->format('d/m/Y H:i') }}</dd><dt class="text-slate-400">ข้อมูลผู้รับ</dt><dd class="break-words">{{ $order->recipient }}</dd></dl>
<h2 class="mt-6 font-bold">ข้อมูลจากร้าน</h2><p class="mt-2 whitespace-pre-line text-sm text-slate-300">{{ $order->delivery_note ?: 'ร้านได้รับคำสั่งซื้อแล้ว กรุณารอการดำเนินการตามเงื่อนไขสินค้า' }}</p>
<h2 class="mt-6 font-bold">ความคืบหน้าคำสั่งซื้อ</h2>
<ol class="mt-3 space-y-4 border-l border-slate-700 pl-4">
    <li><p class="text-sm">ร้านได้รับคำสั่งซื้อ</p><time class="text-xs text-slate-500">{{ $order->created_at->copy()->timezone('Asia/Bangkok')->format('d/m/Y H:i') }}</time></li>
    @foreach($order->events as $event)<li><p class="text-sm text-orange-300">{{ ['processing'=>'เริ่มดำเนินการ','completed'=>'ส่งมอบสำเร็จ','refunded'=>'คืนเงินเข้า Wallet แล้ว'][$event->to_status] ?? $event->to_status }}</p><time class="text-xs text-slate-500">{{ $event->created_at->copy()->timezone('Asia/Bangkok')->format('d/m/Y H:i') }}</time><p class="mt-1 whitespace-pre-line break-words text-sm text-slate-300">{{ $event->note }}</p></li>@endforeach
</ol>
@if($order->terms)<details class="mt-6"><summary class="cursor-pointer text-sm text-slate-400">เงื่อนไข ณ เวลาสั่งซื้อ</summary><p class="mt-3 whitespace-pre-line text-sm">{{ $order->terms }}</p></details>@endif
<a href="{{ route('contact') }}" class="mt-6 inline-block text-sm text-orange-300 underline">สอบถามร้านเกี่ยวกับคำสั่งซื้อนี้</a>
</section>
@endsection
