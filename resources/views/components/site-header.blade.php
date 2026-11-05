@php
    $links = ['home' => 'For you', 'discover' => 'Discover'];
@endphp

{{-- The avatar sits fixed in the top-left corner, outside the pill, and fades out once you scroll (app.js sets data-scrolled). --}}
<a href="{{ route('home') }}" aria-label="Homepage" data-avatar class="fixed top-4 left-4 z-50 transition-opacity duration-300 data-scrolled:pointer-events-none data-scrolled:opacity-0 sm:top-5 sm:left-6 lg:left-10">
    <img src="{{ asset('images/possum-head.svg') }}" alt="" class="size-10">
</a>

{{-- A small floating pill on the right: the two pages and search. --}}
<header class="pointer-events-none sticky top-0 z-40 flex justify-end px-4 pt-3 sm:px-6 sm:pt-4 lg:px-10">
    <div class="pointer-events-auto flex h-12 items-center gap-x-1 rounded-full bg-night-900/75 p-1.5 sm:gap-x-2 shadow-lg shadow-black/25 inset-ring inset-ring-white/10 backdrop-blur-xl">

        <nav class="flex items-center gap-x-1 sm:gap-x-2">
            @foreach ($links as $route => $label)
                <a href="{{ route($route) }}" @class([
                    'rounded-full px-4 py-1.5 text-base/6 font-medium whitespace-nowrap transition-colors sm:text-sm/6',
                    'bg-white/8 text-white' => request()->routeIs($route),
                    'text-white/55 hover:text-white' => ! request()->routeIs($route),
                ]) @if (request()->routeIs($route)) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>

        {{-- A link, so it still works without JS; app.js opens the lightbox instead. --}}
        <a href="{{ route('search') }}" data-search-open aria-keyshortcuts="Meta+K Control+K"
            class="relative flex items-center gap-2 rounded-full py-1.5 pr-1.5 pl-4 text-base/6 font-medium text-white/55 transition-colors hover:text-white max-sm:pr-4 sm:text-sm/6">
            Search
            <kbd data-shortcut class="rounded-full px-2 font-sans text-xs/5 font-normal text-white/45 inset-ring inset-ring-white/10 max-sm:hidden">⌘K</kbd>
        </a>
    </div>
</header>
