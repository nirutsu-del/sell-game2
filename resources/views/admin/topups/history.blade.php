@extends('layouts.app')
@section('content')
<h1 class="text-2xl font-bold">ประวัติการตรวจรายการเติมเงิน</h1>
<p class="my-3 text-sm text-slate-400">แสดงรายการที่ดำเนินการหลังเปิดระบบประวัตินี้เท่านั้น เวลาแสดงเป็นเวลาไทย ไม่มีการสร้างข้อมูลผู้ตรวจย้อนหลังแทนรายการเก่า</p>
<a href="{{ route('admin.topups.index') }}" class="text-orange-300">← กลับรายการเติมเงิน</a>
<form method="GET" class="my-5 flex flex-wrap gap-3">
    <label>การดำเนินการ<select name="action" class="ml-2 rounded bg-slate-800 p-2"><option value="">ทั้งหมด</option><option value="approved" @selected(request('action') === 'approved')>อนุมัติ</option><option value="rejected" @selected(request('action') === 'rejected')>ปฏิเสธ</option></select></label>
    <input aria-label="เลขอ้างอิงรายการ" name="reference" value="{{ request('reference') }}" maxlength="100" placeholder="เลขอ้างอิงตรงกันทั้งหมด" class="rounded bg-slate-800 p-2">
    <button class="rounded bg-violet-700 px-4 py-2">ค้นหา</button><a href="{{ route('admin.topups.history') }}" class="p-2">ล้างตัวกรอง</a>
</form>
<div class="space-y-4">
@forelse($reviews as $review)
    <article class="rounded-xl border border-slate-700 bg-slate-900 p-5">
        <h2 class="font-bold">{{ $review->action === 'approved' ? 'อนุมัติ' : 'ปฏิเสธ' }} · ฿{{ number_format($review->amount,2) }}</h2>
        <a class="break-all text-orange-300" href="{{ route('admin.topups.index',['topup'=>$review->topup_id]) }}">{{ $review->reference_no }}</a>
        <p>ผู้ดำเนินการ: {{ $review->admin_name }} · {{ $review->created_at->timezone('Asia/Bangkok')->format('d/m/Y H:i:s') }} (ไทย)</p>
        <p class="text-sm text-slate-400">{{ $review->from_status }} → {{ $review->to_status }}</p>
        @if($review->reason)<p class="mt-2 whitespace-pre-line break-words">เหตุผล: {{ $review->reason }}</p>@endif
    </article>
@empty
    <p class="rounded bg-slate-900 p-5">ยังไม่มีประวัติที่ตรงกับเงื่อนไข</p>
@endforelse
</div>
<div class="mt-5">{{ $reviews->links() }}</div>
@endsection
