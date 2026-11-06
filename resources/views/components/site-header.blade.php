@php
    $links = ['home' => 'For you', 'discover' => 'Discover'];
    $initial = Str::upper(Str::substr(auth()->user()->name, 0, 1));
    $onSettings = request()->routeIs('settings');
    $avatar = 'flex size-9 shrink-0 items-center justify-center rounded-full font-display text-sm font-semibold outline-1 -outline-offset-1 outline-white/10 transition-colors '
        .($onSettings ? 'bg-white/20 text-white' : 'bg-white/8 text-white/70 hover:bg-white/12 hover:text-white');
    $kbd = 'rounded-full px-2 font-sans text-xs/5 font-normal text-white/45 inset-ring inset-ring-white/10';
@endphp

{{-- Open and quiet: the possum and wordmark on the left, the pages as a small segmented control in the middle,
     search and your initial (Settings) on the right. No surface at the top; a glass strip once you scroll (app.js sets data-scrolled). --}}
<header data-header class="sticky top-0 z-40 transition-colors duration-300 data-scrolled:bg-night-950/75 data-scrolled:backdrop-blur-xl">
    <div class="mx-auto grid h-16 max-w-[96rem] grid-cols-[1fr_auto_1fr] items-center gap-x-3 px-4 sm:px-6 lg:px-10">
        <a href="{{ route('home') }}" aria-label="Homepage" class="flex items-center gap-2.5 justify-self-start font-display text-lg font-semibold tracking-tight">
            <img src="{{ asset('images/possum-head.svg') }}" alt="" class="size-8">
            <span class="max-md:hidden">Possum</span>
        </a>
        <nav class="flex items-center gap-x-0.5 rounded-full bg-white/5 p-1 inset-ring inset-ring-white/10">
            @foreach ($links as $route => $label)
                <a href="{{ route($route) }}" @class(['rounded-full px-3.5 py-1 text-base/6 font-medium whitespace-nowrap transition-colors sm:text-sm/6 max-sm:px-3', 'bg-white/12 text-white' => request()->routeIs($route), 'text-white/55 hover:text-white' => ! request()->routeIs($route)]) @if (request()->routeIs($route)) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>
        <div class="flex items-center gap-x-1 justify-self-end sm:gap-x-3">
            <a href="{{ route('search') }}" data-search-open aria-keyshortcuts="Meta+K Control+K" aria-label="Search" class="flex items-center gap-2 rounded-full p-2 text-base/6 font-medium text-white/55 transition-colors hover:text-white sm:px-3 sm:text-sm/6">
                <x-heroicon-m-magnifying-glass class="size-4 shrink-0 fill-current" />
                <span class="max-lg:hidden">Search</span>
                <kbd data-shortcut class="{{ $kbd }} max-lg:hidden">⌘K</kbd>
            </a>
            <a href="{{ route('settings') }}" aria-label="Settings" class="{{ $avatar }}" @if ($onSettings) aria-current="page" @endif>{{ $initial }}</a>
        </div>
    </div>
</header>
