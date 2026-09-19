@if($attachments->isNotEmpty())
<div class="mt-3 flex flex-wrap gap-3">
    @foreach($attachments as $attachment)
        <a href="{{ $attachment->viewUrl() }}" target="_blank" rel="noopener noreferrer" class="block text-sm text-orange-300">
            <img src="{{ $attachment->viewUrl() }}" alt="รูปแนบ {{ $loop->iteration }}" loading="lazy" class="h-32 w-32 rounded-lg object-contain">
            เปิดรูป {{ $loop->iteration }} ↗
        </a>
    @endforeach
</div>
@endif
