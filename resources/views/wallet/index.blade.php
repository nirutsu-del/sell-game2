@extends('layouts.app')

@section('content')
    <h1 class="text-3xl font-bold">Wallet</h1>
    <div class="my-6 rounded-xl bg-violet-700 p-6 text-2xl font-bold">คงเหลือ ฿{{ number_format(auth()->user()->balance, 2) }}</div>

    @if($receipt)
        <section role="status" class="rounded-xl border border-violet-500 bg-slate-900 p-5">
            <h2 class="text-xl font-bold">รับรายการเติมเงินแล้ว</h2>
            <p class="mt-3 break-all">เลขอ้างอิง: {{ $receipt->reference_no }}</p>
            <p>จำนวน ฿{{ number_format($receipt->amount, 2) }}</p>
            <p>สถานะ: {{ ['pending'=>'รอตรวจสอบ','success'=>'สำเร็จ','failed'=>'ไม่อนุมัติ'][$receipt->status] }}</p>
            @if($receipt->status === 'failed')
                <p class="mt-3 whitespace-pre-line break-words">เหตุผล: {{ data_get($receipt->verification_payload, 'rejection_reason', 'รายการเก่ายังไม่ได้บันทึกเหตุผล กรุณาติดต่อร้าน') }}</p>
                <a class="text-orange-300" href="{{ route('contact') }}">ติดต่อร้านเพื่อตรวจสอบ →</a>
            @endif
            <p class="mt-3">รายการรอตรวจสอบยังไม่เพิ่มเครดิต กรุณาเก็บเลขอ้างอิงไว้ ไม่ต้องส่งรายการเดิมซ้ำ</p>
            <a class="mt-4 inline-block text-orange-300" href="{{ route('wallet.index') }}">เริ่มรายการเติมเงินใหม่ →</a>
        </section>
    @else
    <form id="topup-form" method="POST" enctype="multipart/form-data" action="{{ route('wallet.topups.store') }}" class="rounded-xl bg-slate-900 p-5">
        @csrf
        <input type="hidden" name="request_id" value="{{ $requestId }}">
        <div class="grid gap-4 md:grid-cols-3">
            <input name="amount" type="number" min="1" max="100000" step="0.01" value="{{ old('amount') }}" aria-label="จำนวนเงิน" placeholder="จำนวนเงิน" class="rounded bg-slate-800 p-3" required>
            <select id="payment-method" name="payment_method" class="rounded bg-slate-800 p-3">
                <option value="promptpay_slip" @selected(old('payment_method') === 'promptpay_slip')>PromptPay</option>
                <option value="truemoney_gift" @selected(old('payment_method') === 'truemoney_gift')>TrueMoney Gift</option>
            </select>
            <input name="slip" type="file" accept="image/*" class="rounded bg-slate-800 p-3">
        </div>

        <div id="promptpay-qr" class="mt-5 rounded-lg border border-violet-500/40 bg-slate-800/70 p-5 text-center">
            <p class="font-semibold text-violet-200">สแกน QR Code เพื่อชำระเงินผ่าน PromptPay</p>
            @if($paymentSettings->promptpay_instructions)<p class="mt-3 whitespace-pre-line text-sm text-slate-300">{{ $paymentSettings->promptpay_instructions }}</p>@endif
            @if ($promptpayQrImage)
                <img src="{{ $promptpayQrImage }}" alt="PromptPay QR Code" class="mx-auto mt-4 w-56 rounded-lg bg-white p-2">
                <p class="mt-3 text-sm text-slate-400">ชำระแล้วแนบสลิปด้านบนเพื่อแจ้งเติมเงิน</p>
            @else
                <p class="mt-3 text-sm text-amber-300">ยังไม่ได้ตั้งค่ารูป QR PromptPay</p>
            @endif
        </div>

        <div id="truemoney-qr" class="mt-5 hidden rounded-lg border border-orange-500/40 bg-slate-800/70 p-5 text-center">
            <p class="font-semibold text-orange-200">สแกน QR Code เพื่อชำระเงินผ่าน TrueMoney</p>
            @if($paymentSettings->truemoney_instructions)<p class="mt-3 whitespace-pre-line text-sm text-slate-300">{{ $paymentSettings->truemoney_instructions }}</p>@endif
            @if ($truemoneyQrImage)
                <img src="{{ $truemoneyQrImage }}" alt="TrueMoney QR Code" class="mx-auto mt-4 w-56 rounded-lg bg-white p-2">
                <p class="mt-3 text-sm text-slate-400">ชำระแล้วแนบสลิปด้านบนเพื่อแจ้งเติมเงิน</p>
            @else
                <p class="mt-3 text-sm text-amber-300">ยังไม่ได้ตั้งค่ารูป QR TrueMoney</p>
            @endif
        </div>

        <button id="topup-submit" class="mt-4 w-full rounded bg-violet-600 p-3 font-bold disabled:opacity-50 md:w-auto">แจ้งเติมเงิน</button>
        <p id="topup-progress" role="status" aria-live="polite" class="mt-3 text-sm"></p>
    </form>
    @endif

    <h2 class="mt-10 text-xl font-bold">ประวัติเติมเงิน</h2>
    @if(request('topup'))<a href="{{ route('wallet.index') }}" class="mt-2 inline-block text-sm text-orange-300">ดูประวัติเติมเงินทั้งหมด →</a>@endif
    <div class="mt-3 overflow-x-auto"><table class="w-full text-left"><tr class="border-b border-slate-700"><th>เลขอ้างอิง</th><th>จำนวน</th><th>สถานะ</th></tr>@forelse($transactions as $t)<tr id="topup-{{ $t->id }}" class="border-b border-slate-800"><td class="py-3">{{ $t->reference_no }}</td><td>฿{{ number_format($t->amount,2) }}</td><td>{{ ['pending'=>'รอตรวจสอบ','success'=>'สำเร็จ','failed'=>'ไม่อนุมัติ'][$t->status] ?? $t->status }}</td></tr>@empty<tr><td colspan="3" class="py-4 text-slate-400">ไม่พบรายการเติมเงิน</td></tr>@endforelse</table></div>
    <div class="mt-5">{{ $transactions->links() }}</div>
    @foreach($transactions->where('status', 'failed') as $rejected)
        <a class="mt-3 block break-all text-orange-300" href="{{ route('wallet.index', ['topup'=>$rejected->id]) }}">ดูเหตุผลที่ไม่อนุมัติ · {{ $rejected->reference_no }} →</a>
    @endforeach

    @if(!$receipt)
    <script>
        const paymentMethod = document.getElementById('payment-method');
        const promptpayQr = document.getElementById('promptpay-qr');
        const truemoneyQr = document.getElementById('truemoney-qr');
        const syncPaymentMethod = () => {
            promptpayQr.classList.toggle('hidden', paymentMethod.value !== 'promptpay_slip');
            truemoneyQr.classList.toggle('hidden', paymentMethod.value !== 'truemoney_gift');
        };
        paymentMethod.addEventListener('change', syncPaymentMethod);
        syncPaymentMethod();
        const topupForm = document.getElementById('topup-form');
        const topupSubmit = document.getElementById('topup-submit');
        const topupProgress = document.getElementById('topup-progress');
        topupForm.addEventListener('submit', (event) => {
            if (topupSubmit.disabled) { event.preventDefault(); return; }
            topupSubmit.disabled = true;
            topupSubmit.textContent = 'กำลังส่ง…';
            topupProgress.textContent = 'กำลังบันทึกรายการ กรุณารอ หากการเชื่อมต่อขัดข้องให้ตรวจประวัติก่อนส่งซ้ำ';
        });
        window.addEventListener('pageshow', () => {
            topupSubmit.disabled = false;
            topupSubmit.textContent = 'แจ้งเติมเงิน';
            topupProgress.textContent = '';
        });
    </script>
    @endif
@endsection
