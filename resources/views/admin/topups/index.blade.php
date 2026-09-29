@extends('layouts.app')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div><p class="text-sm text-violet-300">ADMIN PANEL</p><h1 class="text-2xl font-bold">ตรวจสอบรายการเติมเงิน</h1></div>
        <a href="{{ route('admin.accounts.index') }}" class="rounded border border-slate-700 px-4 py-2 hover:bg-slate-800">จัดการไอดีเกม</a>
    </div>
    <p role="note" class="mt-4 rounded bg-amber-950 p-4 text-amber-200">ห้ามอนุมัติจากรูปสลิปอย่างเดียว ตรวจบัญชีรับเงินและยอดเข้าจริงก่อนทุกครั้ง ระบบตรวจไฟล์ซ้ำไม่ได้ยืนยันว่าสลิปแท้</p>
    <div class="mt-6 overflow-x-auto rounded-xl border border-slate-800 bg-slate-900">
        <a href="{{ route('admin.topups.history') }}" class="block p-4 text-orange-300">ประวัติการอนุมัติ / ปฏิเสธ →</a>
        @if(request('topup'))<a href="{{ route('admin.topups.index') }}" class="block p-4 text-sm text-orange-300">ดูรายการเติมเงินทั้งหมด →</a>@endif
        <table class="w-full min-w-[850px] text-left text-sm"><thead class="border-b border-slate-800 text-slate-400"><tr><th class="p-4">ผู้ใช้ / อ้างอิง</th><th class="p-4">ช่องทาง</th><th class="p-4">จำนวน</th><th class="p-4">สลิป</th><th class="p-4">สถานะ</th><th class="p-4 text-right">จัดการ</th></tr></thead><tbody>
        @forelse ($topups as $topup)
            <tr class="border-b border-slate-800">
                <td class="p-4"><p>{{ $topup->user->name }}</p><p class="text-xs text-slate-400">{{ $topup->reference_no }} · {{ $topup->created_at->timezone('Asia/Bangkok')->format('d/m/Y H:i') }} (ไทย)</p></td>
                <td class="p-4">{{ $topup->payment_method === 'promptpay_slip' ? 'PromptPay' : 'TrueMoney Gift' }}</td>
                <td class="p-4">฿{{ number_format($topup->amount, 2) }}</td>
                <td class="p-4">@if($topup->slip_path)<a href="{{ route('admin.topups.slip', $topup) }}" target="_blank" rel="noopener" class="text-violet-300">ดูสลิป</a>@else<span class="text-amber-300">ไม่มีสลิป — ต้องตรวจยอดเข้าจากบัญชีจริง</span>@endif</td>
                <td class="p-4">{{ $topup->status }}</td>
                <td class="p-4">
                    @if($topup->status === 'pending')
                    @php($restoreForm = $errors->any() && (string) old('review_topup') === (string) $topup->id)
                    <div class="flex flex-wrap items-start justify-end gap-2">
                    <form action="{{ route('admin.topups.approve', $topup) }}" method="POST">
                        @csrf
                        <button type="submit" class="topup-review-action rounded bg-emerald-700">อนุมัติ</button>
                    </form>
                    <details name="topup-review" @if($restoreForm && old('review_action') === 'reject') open @endif>
                    <summary class="topup-review-action rounded bg-red-900">ปฏิเสธ</summary>
                    <form action="{{ route('admin.topups.reject', $topup) }}" method="POST" class="mt-3 w-80 rounded-lg border border-slate-700 p-4" onsubmit="return confirm('ยืนยันการปฏิเสธและส่งเหตุผลให้ลูกค้า?')">
                        @csrf
                        <input type="hidden" name="review_topup" value="{{ $topup->id }}">
                        <input type="hidden" name="review_action" value="reject">
                        <label class="block">เหตุผลปฏิเสธ (ลูกค้าจะเห็น)<textarea name="reason" required minlength="5" maxlength="500" rows="3" class="mt-2 block w-full rounded bg-slate-800 p-2" placeholder="เช่น ยอดโอนไม่ตรง กรุณาติดต่อร้านเพื่อตรวจสอบ">{{ $restoreForm ? old('reason') : '' }}</textarea></label>
                        <p class="my-2 text-xs text-slate-400">ระบุ 5–500 ตัวอักษร ไม่ใส่ข้อมูลลับหรือข้อมูลของลูกค้าคนอื่น</p>
                        <button class="rounded bg-red-900 px-3 py-2">ยืนยันปฏิเสธ</button>
                    </form>
                    </details>
                    </div>
                    @else
                        <span>ดำเนินการแล้ว</span>
                        @if(data_get($topup->verification_payload, 'rejection_reason'))<p class="mt-2 whitespace-pre-line break-words">เหตุผล: {{ data_get($topup->verification_payload, 'rejection_reason') }}</p>@endif
                        @if(data_get($topup->verification_payload, 'method') === 'manual')
                        <p class="mt-2 text-xs">ผู้ตรวจ #{{ data_get($topup->verification_payload, 'reviewer_id') }} @if(data_get($topup->verification_payload, 'transfer_reference')) · อ้างอิง {{ data_get($topup->verification_payload, 'transfer_reference') }} @endif</p>
                        @endif
                    @endif
                </td>
            </tr>
        @empty <tr><td colspan="6" class="p-8 text-center text-slate-500">ยังไม่มีรายการเติมเงิน</td></tr>
        @endforelse
        </tbody></table>
    </div>
    <div class="mt-6">{{ $topups->links() }}</div>
@endsection
