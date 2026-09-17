@extends('layouts.app')

@section('content')
    @php($demoItem = collect(config('demo_catalog', []))->first(fn ($item) => $item['title'] === $account->title))
    <nav class="account-breadcrumb" aria-label="เส้นทางสินค้า"><a href="{{ route('shop.index') }}">หน้าแรก</a><span aria-hidden="true">/</span><a href="{{ route('catalog.category',$account->category) }}">{{ $account->category->name }}</a><span aria-hidden="true">/</span><span>รายละเอียดไอดี</span></nav>
    <div class="account-detail-layout">
        <div class="account-detail-gallery">
            <div class="account-art-heading"><span>{{ $account->category->name }}</span><span>#{{ str_pad((string)$account->id,4,'0',STR_PAD_LEFT) }}</span></div>
            @if(filled($account->images))
                @foreach($account->images as $image)
                <button type="button" class="account-art-button {{ $loop->first ? 'is-cover' : 'is-extra' }}" data-open-dialog="account-art-{{ $loop->index }}" aria-label="ขยายภาพ {{ $loop->iteration }} ของ {{ $account->title }}"><img src="{{ asset('storage/'.$image) }}" alt="{{ $account->title }} ภาพที่ {{ $loop->iteration }}" @if(!$loop->first) loading="lazy" @endif><span>ขยายภาพ ↗</span></button>
                <dialog id="account-art-{{ $loop->index }}" class="vault-dialog account-art-dialog" aria-label="ภาพขยาย {{ $account->title }}"><div class="vault-dialog-heading"><span>ภาพที่ {{ $loop->iteration }}</span><button type="button" data-close-dialog aria-label="ปิดภาพขยาย">×</button></div><img src="{{ asset('storage/'.$image) }}" alt="{{ $account->title }}" loading="lazy"></dialog>
                @endforeach
            @else
                <div class="account-art-placeholder" aria-label="ยังไม่มีภาพสินค้า">✦</div>
            @endif
            <p class="account-art-caption">{{ $demoItem ? 'AI CONCEPT ART · ภาพประกอบสินค้าจำลอง ไม่ใช่ภาพภายในเกม' : 'กดที่ภาพเพื่อดูรายละเอียดขนาดใหญ่' }}</p>
        </div>
        <div class="account-detail-summary">
            <div class="account-detail-kicker"><span>YOUR NEXT ACCOUNT</span><span>{{ $account->status === 'available' ? '● พร้อมซื้อ' : 'จองแล้ว' }}</span></div>
            <h1>{{ $account->title }}</h1>
            @if($demoItem)
            <p class="account-detail-intro">{{ $demoItem['detail'] }}</p>
            <dl class="account-detail-stats"><div><dt>{{ in_array($account->category->name,['Roblox']) ? 'คอลเลกชัน' : 'แรงก์จำลอง' }}</dt><dd>{{ $demoItem['rank'] }}</dd></div><div><dt>สิ่งสะสมจำลอง</dt><dd>{{ $demoItem['collection'] }}</dd></div></dl>
            @endif
            <div class="account-detail-price"><span>ราคาไอดี{{ $demoItem ? 'จำลอง' : '' }}</span><strong>฿{{ number_format($account->price, 2) }}</strong></div>
            <p class="account-detail-payment">ชำระผ่าน Wallet · ตรวจสอบรายการก่อนยืนยัน</p>
            @auth
                <dl class="vault-balance-summary"><div><dt>ยอด Wallet ปัจจุบัน</dt><dd>฿{{ number_format(auth()->user()->balance, 2) }}</dd></div><div><dt>{{ auth()->user()->balance >= $account->price ? 'คงเหลือหลังซื้อ' : 'ยอดที่ต้องเติมเพิ่ม' }}</dt><dd>฿{{ number_format(abs(auth()->user()->balance - $account->price), 2) }}</dd></div></dl>
                @if($account->status === 'available' && auth()->user()->balance >= $account->price)
                <button type="button" class="market-button mt-6 w-full" data-open-dialog="purchase-confirm">ตรวจสอบก่อนซื้อ →</button>
                <dialog id="purchase-confirm" class="vault-dialog"><div class="vault-dialog-heading"><h2>ยืนยันการซื้อไอดี</h2><button type="button" data-close-dialog aria-label="ปิดหน้าต่างยืนยัน">×</button></div><p class="mt-4">{{ $account->title }}</p><p class="mt-3 text-2xl font-bold text-orange-300">฿{{ number_format($account->price, 2) }}</p><p class="mt-3 text-sm text-slate-300">ชำระด้วย Wallet และรับข้อมูลไอดีหลังซื้อสำเร็จ</p><p class="mt-3 text-sm text-cyan-300">ยอดคงเหลือหลังซื้อ ฿{{ number_format(auth()->user()->balance - $account->price, 2) }}</p>
                <form method="POST" action="{{ route('accounts.buy', $account) }}" class="mt-6" data-purchase-form>
                    @csrf
                    <button class="market-button w-full">ยืนยันซื้อด้วย Wallet</button>
                </form>
                </dialog>
                @elseif($account->status === 'available')
                <div class="mt-6 rounded-xl border border-amber-400/30 bg-amber-400/10 p-4">
                    <p class="text-sm text-amber-200">ยอด Wallet ยังไม่พอสำหรับรายการนี้</p>
                    <a href="{{ route('wallet.index') }}" class="market-button mt-3 flex w-full">เติมเงินก่อนซื้อ →</a>
                </div>
                @else
                <p class="mt-6 rounded-xl border border-amber-400/30 bg-amber-400/10 p-4 text-sm text-amber-200">ไอดีนี้ถูกสั่งซื้อไปแล้ว ลองดูไอดีอื่นในหมวดเดียวกัน</p>
                @endif
            @else
                <a href="{{ route('login') }}" class="market-button mt-6 flex w-full">เข้าสู่ระบบเพื่อซื้อ</a>
            @endauth
        </div>
    </div>
    <div class="account-info-grid">
        <section class="account-info-panel"><p class="account-section-label">ACCOUNT DETAILS</p><h2>รายละเอียดไอดี</h2><p class="account-description">{{ $account->description ?: 'สอบถามรายละเอียดเพิ่มเติมกับร้านก่อนสั่งซื้อ' }}</p><a class="account-support-link" href="{{ route('contact') }}">สอบถามเกี่ยวกับไอดีนี้ ↗</a></section>
        <section class="account-info-panel"><p class="account-section-label">HOW IT WORKS</p><h2>{{ $demoItem ? 'ทดลองซื้อและรับข้อมูลจำลอง' : 'ซื้อแล้วรับไอดีอย่างไร?' }}</h2><ol class="account-delivery"><li><span>01</span><div><strong>ตรวจสอบข้อมูลและยอด Wallet</strong><p>เข้าสู่ระบบ แล้วตรวจรายละเอียดไอดีและยอดที่ต้องใช้</p></div></li><li><span>02</span><div><strong>ยืนยันรายการซื้อ</strong><p>ตรวจราคาและยอดคงเหลือในหน้าต่างยืนยันก่อนทำรายการ</p></div></li><li><span>03</span><div><strong>ดูข้อมูลหลังซื้อสำเร็จ</strong><p>{{ $demoItem ? 'ระบบแสดงข้อมูลล็อกอินสมมติสำหรับสาธิต ไม่มีบัญชีเกมจริงส่งมอบ' : 'ดูข้อมูล Username และ Password ในหน้ารายการซื้อที่สำเร็จ' }}</p></div></li></ol></section>
    </div>
@endsection 
