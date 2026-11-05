@php
    $links = ['home' => 'For you', 'discover' => 'Discover'];
    $searchIcon = '<svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="size-5 sm:size-4"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.45 4.39l3.08 3.08a.75.75 0 1 1-1.06 1.06l-3.08-3.08A7 7 0 0 1 2 9Z" clip-rule="evenodd"/></svg>';
@endphp

<header class="sticky top-0 z-40 border-b border-white/5 bg-night-950/85 backdrop-blur-xl">
    <div class="mx-auto flex h-16 max-w-[96rem] items-center gap-x-4 px-4 sm:gap-x-10 sm:px-6 lg:px-10">
        <a href="{{ route('home') }}" aria-label="Homepage" class="flex shrink-0 items-center gap-2.5">
            <img src="{{ asset('images/possum-head.svg') }}" alt="" class="size-9">
            <span class="font-display text-lg font-semibold tracking-tight max-sm:hidden">Possum</span>
        </a>

        {{-- Two links fit on every screen, so there's no menu to open. --}}
        <nav class="flex items-center gap-x-1">
            @foreach ($links as $route => $label)
                <a href="{{ route($route) }}" @class([
                    'rounded-lg px-3 py-1.5 text-base/6 font-medium whitespace-nowrap transition-colors sm:text-sm/6',
                    'bg-white/8 text-white' => request()->routeIs($route),
                    'text-white/55 hover:text-white' => ! request()->routeIs($route),
                ]) @if (request()->routeIs($route)) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>

        <form action="{{ route('search') }}" method="get" role="search" class="ml-auto grid w-72 grid-cols-1 max-md:hidden xl:w-80">
            <input type="search" name="q" aria-label="Find a show or movie" value="{{ request()->routeIs('search') ? request('q') : '' }}" placeholder="Find a show or movie"
                class="col-start-1 row-start-1 rounded-lg bg-white/5 py-1.5 pr-3 pl-9 text-sm/6 text-white inset-ring inset-ring-white/10 placeholder:text-white/35 focus:bg-white/8 focus:outline-2 focus:-outline-offset-1 focus:outline-midnight-400">
            <span class="pointer-events-none col-start-1 row-start-1 ml-3 self-center text-white/35">{!! $searchIcon !!}</span>
        </form>

        <a href="{{ route('search') }}" @class([
            'relative -mr-2 ml-auto rounded-lg p-2 hover:bg-white/5 hover:text-white md:hidden',
            'bg-white/8 text-white' => request()->routeIs('search'),
            'text-white/70' => ! request()->routeIs('search'),
        ]) aria-label="Search">
            {!! $searchIcon !!}
        </a>
    </div>
</header>
