@extends('layouts.app')
@section('content')
<div class="market-heading"><div><p>SALES REPORT</p><h1 class="text-3xl font-bold">รายงานยอดขาย</h1></div><a href="{{ route('admin.dashboard') }}">ภาพรวมร้าน →</a></div>
<form class="market-form mb-6 grid gap-3 rounded-2xl border border-slate-700 bg-slate-900 p-4 sm:grid-cols-4">
    <label>ช่วงเวลา<select name="period">@foreach(['today'=>'วันนี้','month'=>'เดือนนี้','custom'=>'กำหนดเอง','all'=>'ทั้งหมด'] as $key=>$label)<option value="{{ $key }}" @selected($period['period'] === $key)>{{ $label }}</option>@endforeach</select></label>
    <label>ตั้งแต่วันที่<input type="date" name="from" value="{{ $period['from'] }}"></label>
    <label>ถึงวันที่<input type="date" name="to" value="{{ $period['to'] }}"></label>
    <button class="market-button self-end">ดูรายงาน</button>
</form>
<div class="mb-6 flex flex-wrap items-center justify-between gap-3"><p class="text-sm text-slate-400">{{ $period['period'] === 'all' ? 'ข้อมูลทั้งหมด' : $period['from'].' ถึง '.$period['to'] }} · เวลาไทย (UTC+7)</p><a class="market-button" href="{{ route('admin.reports.sales.export',array_filter(['period'=>$period['period'],'from'=>$period['from'],'to'=>$period['to']])) }}">ส่งออก CSV รายการ Wallet</a></div>
<div class="grid gap-4 sm:grid-cols-3">
    @foreach([['ยอดตัด Wallet จากการขาย/สุ่ม',$report['gross']],['หลังหักคืนเงินและเครดิตรางวัล',$report['net']]] as $metric)
    <div class="rounded-2xl border border-slate-700 bg-slate-900 p-5"><p class="text-sm text-slate-400">{{ $metric[0] }}</p><p class="mt-3 text-2xl font-bold text-orange-300">฿{{ number_format($metric[1]/100,2) }}</p></div>
    @endforeach
    <div class="rounded-2xl border border-slate-700 bg-slate-900 p-5"><p class="text-sm text-slate-400">รายการซื้อและสุ่มที่ตัดเงินสำเร็จ</p><p class="mt-3 text-2xl font-bold">{{ number_format($report['paidCount']) }}</p></div>
</div>
<p class="my-5 text-xs leading-6 text-slate-400">ยอดสุทธิข้างต้นไม่ใช่กำไร และไม่ใช่เงินเข้าธนาคาร ใช้เวลาเกิดรายการ Wallet เป็นเกณฑ์ งานบริการนับตอนชำระแม้ยังไม่ส่งมอบ คืนเงินนับวันที่คืนจริง จึงอาจเป็นยอดติดลบในบางช่วง เงินเติม Wallet แยกไว้เพื่อไม่ให้นับซ้ำ</p>
<div class="overflow-x-auto rounded-2xl border border-slate-700 bg-slate-900"><table class="w-full text-left text-sm"><thead><tr class="border-b border-slate-700"><th class="p-4">ประเภท</th><th class="p-4 text-right">จำนวนรายการ</th><th class="p-4 text-right">ยอดบาท</th></tr></thead><tbody>
@foreach($report['totals'] as $key=>$row)<tr class="border-b border-slate-800"><td class="p-4">{{ $row['label'] }}{{ $key === 'topups' ? ' (ไม่รวมยอดขาย)' : '' }}</td><td class="p-4 text-right">{{ number_format($row['count']) }}</td><td class="p-4 text-right">{{ number_format($row['cents']/100,2) }}</td></tr>@endforeach
</tbody></table></div>
@if($report['unclassified'])<p class="mt-4 text-sm text-amber-300">มีรายการ Wallet ประเภทอื่น {{ $report['unclassified'] }} รายการ ไม่รวมในยอดข้างต้น ตรวจได้จาก CSV</p>@endif
<h2 class="mb-2 mt-8 text-xl font-bold">สินค้าขายดี 10 อันดับตามยอดตัด Wallet</h2><p class="mb-4 text-xs text-slate-400">ก่อนหักคืนเงินและเครดิตรางวัล รวมงานบริการที่ชำระแล้วทุกสถานะ · จำนวนชิ้นของกล่องสุ่มหมายถึงจำนวนครั้ง</p>
<div class="overflow-x-auto rounded-2xl border border-slate-700 bg-slate-900"><table class="w-full text-left text-sm"><thead><tr><th class="p-4">สินค้า / แพ็กเกจ</th><th class="p-4">ประเภท</th><th class="p-4">รายการ</th><th class="p-4">ชิ้น/ครั้ง</th><th class="p-4 text-right">ยอดบาท</th></tr></thead><tbody>@forelse($bestSellers as $item)<tr class="border-t border-slate-800"><td class="p-4">{{ $item['title'] }}</td><td class="p-4">{{ $item['type'] }}</td><td class="p-4">{{ $item['orders'] }}</td><td class="p-4">{{ $item['units'] }}</td><td class="p-4 text-right">{{ number_format($item['cents']/100,2) }}</td></tr>@empty<tr><td colspan="5" class="p-8 text-center text-slate-400">ไม่มีรายการขายในช่วงนี้</td></tr>@endforelse</tbody></table></div>
@endsection
