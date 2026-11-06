@php
    $links = ['for-you' => 'For you', 'discover' => 'Discover'];
    $me = auth()->user();
    $requests = $me->followRequests()->whereNull('users.suspended_at')->count();
    $onOwnPages = request()->routeIs('settings') || request()->route('user')?->is($me);
    $kbd = 'rounded-full px-2 font-sans text-xs/5 font-normal text-white/45 inset-ring inset-ring-white/10';
@endphp

{{-- Open and quiet: the owl and wordmark on the left, the pages as a small segmented control in the middle,
     search and your avatar on the right; it opens a small menu (your profile, Settings, Log out), a <details> that app.js closes on an outside click. No surface at the top; a glass strip once you scroll (app.js sets data-scrolled). --}}
<header data-header class="sticky top-0 z-40 transition-colors duration-300 data-scrolled:bg-night-950/75 data-scrolled:backdrop-blur-xl">
    <div class="mx-auto grid h-16 max-w-[96rem] grid-cols-[1fr_auto_1fr] items-center gap-x-3 px-4 sm:px-6 lg:px-10">
        <a href="{{ route('for-you') }}" aria-label="For you" class="flex items-center gap-2.5 justify-self-start font-display text-lg font-semibold tracking-tight">
            <img src="{{ asset('images/hoot-head.svg') }}" alt="" class="size-8">
            <span class="max-md:hidden">Hoot</span>
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
            <details data-menu class="group/menu relative">
                <summary aria-label="Your account{{ $requests ? ', '.trans_choice(':count follow request|:count follow requests', $requests) : '' }}"
                    @class(['relative flex list-none rounded-full transition [&::-webkit-details-marker]:hidden', 'ring-2 ring-white/60 ring-offset-2 ring-offset-night-950' => $onOwnPages, 'opacity-85 group-open/menu:opacity-100 hover:opacity-100' => ! $onOwnPages])>
                    <x-avatar :user="$me" />
                    @if ($requests)
                        <span class="absolute -top-0.5 -right-0.5 size-3 rounded-full bg-calendar-400 ring-2 ring-night-950" aria-hidden="true"></span>
                    @endif
                </summary>
                @php $item = 'flex items-center justify-between gap-4 rounded-md px-2.5 py-1.5 text-base/6 text-white/80 hover:bg-white/8 hover:text-white sm:text-sm/6'; @endphp
                <div class="absolute top-full right-0 z-50 mt-2 w-56 rounded-xl bg-night-900 p-1 shadow-xl shadow-black/40 inset-ring inset-ring-white/10">
                    <div class="px-2.5 pt-1.5 pb-2">
                        <p class="truncate text-sm/5 font-medium text-white">{{ $me->name }}</p>
                        <p class="truncate text-sm/5 text-white/45">{{ '@'.$me->username }}</p>
                    </div>
                    <div class="border-t border-white/8 pt-1">
                        <a href="{{ $me->profileUrl() }}" class="{{ $item }}">Your profile</a>
                        <a href="{{ route('settings') }}" class="{{ $item }}">Settings</a>
                        @if ($requests)
                            <a href="{{ route('settings') }}#requests" class="{{ $item }}">
                                Follow requests
                                <span class="rounded-full bg-calendar-400/15 px-1.5 text-xs/5 font-medium text-calendar-400 tabular-nums">{{ $requests }}</span>
                            </a>
                        @endif
                    </div>
                    <form method="post" action="{{ route('logout') }}" class="mt-1 border-t border-white/8 pt-1">
                        @csrf
                        <button type="submit" class="{{ $item }} w-full text-left">Log out</button>
                    </form>
                </div>
            </details>
        </div>
    </div>
</header>
