@if(!request()->routeIs('admin.*') && $storeSettings->floating_contact_enabled && count($storeSettings->contactLinks()))
<details id="floating-store-contact" class="fixed bottom-5 right-4 z-40 max-w-[calc(100vw-2rem)] text-sm" style="bottom:max(1.25rem,env(safe-area-inset-bottom))">
    <summary class="ml-auto w-fit cursor-pointer rounded-full bg-orange-600 px-5 py-3 font-semibold text-white shadow-lg">ติดต่อร้าน</summary>
    <div class="absolute bottom-full right-0 mb-3 w-72 max-w-[calc(100vw-2rem)] rounded-2xl border border-slate-700 bg-slate-900 p-4 shadow-xl">
        <h2 class="mb-3 break-words font-bold">{{ $storeSettings->name }}</h2>
        @foreach($storeSettings->contactLinks() as $channel)
            <a href="{{ $channel['url'] }}" target="_blank" rel="noopener noreferrer" class="mb-2 block rounded-lg bg-slate-800 p-3 text-orange-300">ติดต่อทาง {{ $channel['label'] }} ↗</a>
        @endforeach
        @if($storeSettings->opening_hours)<p class="mt-3 whitespace-pre-line break-words text-xs text-slate-400">{{ $storeSettings->opening_hours }}</p>@endif
        <a href="{{ route('contact') }}" class="mt-3 inline-block text-xs text-slate-300 underline">ดูหน้าติดต่อทั้งหมด</a>
    </div>
</details>
@endif
