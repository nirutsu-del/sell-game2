<nav aria-label="นโยบายร้าน" class="mx-auto flex max-w-6xl flex-wrap justify-center gap-x-6 gap-y-3 px-4 py-5 text-sm text-slate-400">
    @foreach(\App\Models\StorePolicy::PAGES as $slug => $title)
    <a href="{{ route('policies.show', $slug) }}" class="hover:text-white">{{ $title }}</a>
    @endforeach
</nav>
