<section class="mt-6 space-y-4" aria-label="บทสนทนา">
    <h2 class="text-xl font-bold">บทสนทนา</h2>
    <article class="rounded-xl bg-slate-950 p-5">
        <p class="text-sm text-slate-400">ข้อความแรก · {{ $message->created_at->copy()->timezone('Asia/Bangkok')->format('d/m/Y H:i') }}</p>
        <p class="mt-3 whitespace-pre-wrap break-words text-sm leading-7">{{ $message->message }}</p>
    </article>
    @foreach($replies as $reply)
        <article class="rounded-xl border border-slate-700 p-5">
            <p class="text-sm text-orange-300">{{ $reply->from_staff ? 'ทีมงานร้าน' : 'ลูกค้า' }} · {{ $reply->created_at->copy()->timezone('Asia/Bangkok')->format('d/m/Y H:i') }}</p>
            <p class="mt-3 whitespace-pre-wrap break-words text-sm leading-7">{{ $reply->body }}</p>
        </article>
    @endforeach
    @if(!empty($trackingUrl))
        <nav class="flex gap-5" aria-label="หน้าบทสนทนา">
            @if($replies->currentPage() > 1)<a href="{{ Illuminate\Support\Facades\URL::signedRoute('contact.guest.show',['message'=>$message->id,'page'=>$replies->currentPage()-1]) }}">← ก่อนหน้า</a>@endif
            @if($replies->hasMorePages())<a href="{{ Illuminate\Support\Facades\URL::signedRoute('contact.guest.show',['message'=>$message->id,'page'=>$replies->currentPage()+1]) }}">ถัดไป →</a>@endif
        </nav>
    @else
        {{ $replies->links() }}
    @endif
</section>
<form method="POST" action="{{ $replyUrl }}" class="market-form mt-6 space-y-3">
    @csrf
    <label>ตอบกลับ<textarea name="body" rows="5" required maxlength="3000">{{ old('body') }}</textarea></label>
    @if($message->resolved_at)<p class="text-sm text-slate-400">การส่งข้อความจะเปิดเรื่องนี้อีกครั้ง</p>@endif
    <button class="market-button">ส่งข้อความตอบกลับ</button>
</form>
