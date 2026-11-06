{{-- A still of the For you page for the landing page, as it is now: the header, Up next, the timeline by day and the
     sidebar (Just released, From people you follow, Following). Made-up shows and people; posters are plum gradients,
     service logos are initials. Keep it in step with for-you.blade.php when that changes. --}}
@php
    $posters = ['from-midnight-500 to-night-700', 'from-calendar-500/70 to-night-700', 'from-next/60 to-night-700', 'from-star/60 to-night-700', 'from-midnight-300/70 to-night-800', 'from-sky-500/50 to-night-700'];
    $services = [
        'Apple TV' => 'bg-white/90 text-night-950',
        'Netflix' => 'bg-calendar-500 text-white',
        'HBO Max' => 'bg-indigo-600 text-white',
        'Prime Video' => 'bg-sky-600 text-white',
    ];
    // [title, detail, time, service (or a list of them for a movie), rating, poster]
    $days = [
        ['Today', 'Fri 6 Nov', [
            ['Harbour Lights', ['S02E05', 'The Fog Bell'], '21:00', 'Apple TV', 8.1, 0],
            ['Paper Moons', ['In cinemas', 'first in AU'], null, null, null, 3],
        ]],
        ['Tomorrow', 'Sat 7 Nov', [
            ['The Long Way North', ['S01E01–S01E08', 'all 8 episodes'], '09:00', 'Netflix', 7.6, 2],
        ]],
        ['Monday', 'Mon 9 Nov', [
            ['Gumtree Lane', ['S04E10', 'Owls in the Roof'], '03:00', 'HBO Max', 8.4, 4],
            ['Salt & Ember', ['Digital release', 'first in US'], null, ['Prime Video', 'Apple TV'], null, 1],
        ]],
    ];
    $star = 'M8.7 1.95a.78.78 0 0 0-1.4 0L5.84 4.91l-3.27.48a.78.78 0 0 0-.43 1.33l2.36 2.31-.56 3.25a.78.78 0 0 0 1.13.82L8 11.57l2.93 1.53a.78.78 0 0 0 1.13-.82l-.56-3.25 2.36-2.31a.78.78 0 0 0-.43-1.33l-3.27-.48L8.7 1.95Z';
    $arrow = 'M4.22 11.78a.75.75 0 0 1 0-1.06L9.44 5.5H5.75a.75.75 0 0 1 0-1.5h5.5a.75.75 0 0 1 .75.75v5.5a.75.75 0 0 1-1.5 0V6.56l-5.22 5.22a.75.75 0 0 1-1.06 0Z';
    $chevron = 'M4.22 6.22a.75.75 0 0 1 1.06 0L8 8.94l2.72-2.72a.75.75 0 1 1 1.06 1.06l-3.25 3.25a.75.75 0 0 1-1.06 0L4.22 7.28a.75.75 0 0 1 0-1.06Z';
    $logo = fn (string $name, string $size = 'size-4 text-[9px]') => '<span class="grid '.$size.' shrink-0 place-items-center rounded font-bold '.$services[$name].'">'.e(mb_substr($name, 0, 1)).'</span>';
    $section = 'font-display text-[15px] font-semibold tracking-tight';
@endphp
<div {{ $attributes->class('overflow-hidden rounded-2xl bg-night-950/90 shadow-2xl shadow-black/40 inset-ring inset-ring-white/10') }} aria-hidden="true">
    {{-- Header: owl and wordmark, the two pages, search and the avatar. --}}
    <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-3 border-b border-white/5 px-4 py-3 sm:px-6">
        <div class="flex items-center gap-2 font-display font-semibold tracking-tight">
            <img src="{{ asset('images/hoot-head.svg') }}" alt="" class="size-6">
            <span class="max-sm:hidden">Hoot</span>
        </div>
        <div class="flex items-center gap-0.5 rounded-full bg-white/5 p-0.5 text-xs/5 font-medium inset-ring inset-ring-white/10 sm:text-sm/6">
            <span class="rounded-full bg-white/12 px-3 py-0.5">For you</span>
            <span class="px-3 py-0.5 text-white/55">Discover</span>
        </div>
        <div class="flex items-center justify-self-end gap-3 text-sm/6 text-white/55">
            <span class="flex items-center gap-2 max-md:hidden">
                <svg viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.45 4.39l2.83 2.83a.75.75 0 1 1-1.06 1.06l-2.83-2.83A7 7 0 0 1 2 9Z" clip-rule="evenodd"/></svg>
                Search
                <span class="rounded-full px-2 text-xs/5 text-white/45 inset-ring inset-ring-white/10">⌘K</span>
            </span>
            <span class="grid size-7 place-items-center rounded-full bg-teal-700 font-display text-xs font-semibold text-white outline-1 -outline-offset-1 outline-white/15">P</span>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-10 p-4 sm:p-6 lg:grid-cols-[minmax(0,1fr)_17rem]">
        <div class="min-w-0">
            <p class="font-display text-2xl font-semibold tracking-tight">Upcoming</p>
            <p class="text-xs/5 text-white/40">Times in Europe/Zurich</p>

            {{-- Up next: the poster on its own blurred colours. --}}
            <div class="relative isolate mt-6 overflow-hidden rounded-xl bg-night-900 inset-ring inset-ring-white/5">
                <div class="absolute inset-0 -z-10 bg-linear-to-br from-midnight-500/45 via-calendar-700/15 to-transparent blur-2xl"></div>
                <div class="flex items-center gap-5 p-4 sm:gap-6">
                    <div class="aspect-2/3 w-16 shrink-0 rounded-lg bg-linear-to-b {{ $posters[0] }} outline-1 -outline-offset-1 outline-white/10 sm:w-24"></div>
                    <div class="min-w-0">
                        <p class="text-sm/6 font-medium text-next">Up next · in 3 hours</p>
                        <p class="mt-0.5 font-display text-xl font-semibold tracking-tight sm:text-2xl">Harbour Lights</p>
                        <p class="mt-1 text-sm/6 text-white/65"><span class="text-white">S02E05</span> · The Fog Bell</p>
                        <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm/6 text-white/55">
                            <span class="text-white/80 tabular-nums">Friday 6 Nov, 21:00</span>
                            <span class="inline-flex items-center gap-1.5">{!! $logo('Apple TV') !!} Apple TV <svg viewBox="0 0 16 16" fill="currentColor" class="size-3.5 opacity-60"><path fill-rule="evenodd" d="{{ $arrow }}" clip-rule="evenodd"/></svg></span>
                            <span class="inline-flex items-center gap-1 text-star tabular-nums"><svg viewBox="0 0 16 16" fill="currentColor" class="size-3.5"><path d="{{ $star }}"/></svg>8.1</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- The timeline by day. --}}
            <div class="mt-8 flex flex-col gap-6">
                @foreach ($days as [$label, $date, $rows])
                    <div>
                        <p class="flex items-baseline gap-2 border-b border-white/8 pb-2 text-sm/6">
                            <span class="font-semibold">{{ $label }}</span>
                            <span class="text-white/40">{{ $date }}</span>
                        </p>
                        <ul role="list" class="mt-1">
                            @foreach ($rows as [$title, [$code, $detail], $time, $service, $rating, $poster])
                                <li class="flex items-center gap-4 py-2">
                                    <div class="aspect-2/3 w-10 shrink-0 rounded-md bg-linear-to-b {{ $posters[$poster] }} outline-1 -outline-offset-1 outline-white/10"></div>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm/6 font-medium">{{ $title }}</p>
                                        <p class="truncate text-sm/6 text-white/50"><span class="text-white/75 tabular-nums">{{ $code }}</span> · {{ $detail }}</p>
                                    </div>
                                    <div class="flex shrink-0 flex-col items-end text-right text-sm/6">
                                        @if ($time)
                                            <span class="font-medium tabular-nums">{{ $time }}</span>
                                        @endif
                                        @if (is_array($service))
                                            <span class="flex gap-1 max-sm:hidden">
                                                @foreach ($service as $name)
                                                    {!! $logo($name, 'size-5 text-[10px] rounded-md') !!}
                                                @endforeach
                                            </span>
                                        @elseif ($service)
                                            <span class="inline-flex items-center gap-1.5 text-white/45 max-sm:hidden">{!! $logo($service) !!} {{ $service }} <svg viewBox="0 0 16 16" fill="currentColor" class="size-3.5 opacity-60"><path fill-rule="evenodd" d="{{ $arrow }}" clip-rule="evenodd"/></svg></span>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Sidebar, from wide screens on. --}}
        <div class="flex flex-col gap-8 max-lg:hidden">
            <div>
                <p class="{{ $section }}">Just released</p>
                <p class="text-xs/5 text-white/40">In the last 7 days</p>
                <ul role="list" class="mt-2 divide-y divide-white/5">
                    @foreach ([['Gumtree Lane', 'S04E09 · yesterday', 4], ['Harbour Lights', 'S02E04 · Fri 30 Oct', 0]] as [$title, $detail, $poster])
                        <li class="flex items-center gap-3 py-2">
                            <div class="aspect-2/3 w-8 shrink-0 rounded bg-linear-to-b {{ $posters[$poster] }}"></div>
                            <div class="min-w-0">
                                <p class="truncate text-sm/5 font-medium">{{ $title }}</p>
                                <p class="truncate text-xs/5 text-white/45">{{ $detail }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="border-t border-white/5 pt-6">
                <p class="{{ $section }}">From people you follow</p>
                <ul role="list" class="mt-1 divide-y divide-white/5">
                    @foreach ([
                        ['R', 'bg-amber-700', 'Rue', 'started watching', 'The Long Way North', null, '2h ago', false, 2],
                        ['M', 'bg-indigo-600', 'Max', 'finished', 'Gumtree Lane', '★★★★½', '1d ago', true, 4],
                    ] as [$initial, $colour, $name, $did, $title, $stars, $ago, $following, $poster])
                        <li class="flex items-start gap-2.5 py-2.5">
                            <span class="grid size-7 shrink-0 place-items-center rounded-full {{ $colour }} font-display text-xs font-semibold text-white outline-1 -outline-offset-1 outline-white/15">{{ $initial }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs/5 text-white/60"><span class="font-semibold text-white">{{ $name }}</span> {{ $did }} <span class="font-medium text-white">{{ $title }}</span>@if ($stars) <span class="whitespace-nowrap text-star">· {{ $stars }}</span>@endif</p>
                                <div class="mt-1 flex items-center gap-2">
                                    <span class="text-xs/5 text-white/40">{{ $ago }}</span>
                                    @if ($following)
                                        <span class="flex items-center gap-1 text-xs/5 text-midnight-300"><svg viewBox="0 0 16 16" fill="currentColor" class="size-3"><path fill-rule="evenodd" d="M12.4 4.2a.75.75 0 0 1 .15 1.05l-5 6.5a.75.75 0 0 1-1.13.08l-3-3a.75.75 0 1 1 1.06-1.06l2.4 2.4 4.47-5.81a.75.75 0 0 1 1.05-.16Z" clip-rule="evenodd"/></svg>Following</span>
                                    @else
                                        <span class="flex items-center gap-0.5 rounded-md bg-white/10 py-px pr-1.5 pl-1 text-[11px]/4 font-medium"><svg viewBox="0 0 16 16" fill="currentColor" class="size-3"><path d="M8.75 3.75a.75.75 0 0 0-1.5 0v3.5h-3.5a.75.75 0 0 0 0 1.5h3.5v3.5a.75.75 0 0 0 1.5 0v-3.5h3.5a.75.75 0 0 0 0-1.5h-3.5v-3.5Z"/></svg>Follow</span>
                                    @endif
                                </div>
                            </div>
                            <div class="aspect-2/3 w-6 shrink-0 rounded-sm bg-linear-to-b {{ $posters[$poster] }}"></div>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="border-t border-white/5 pt-6">
                <p class="{{ $section }}">Following</p>
                <p class="text-xs/5 text-white/40 tabular-nums">6 shows · 2 movies</p>
                <ul role="list" class="mt-2 divide-y divide-white/5">
                    @foreach ([['Harbour Lights', 'S02E05 · Fri 6 Nov', 8.1, 0], ['The Long Way North', 'S01E01 · Sat 7 Nov', 7.6, 2], ['Gumtree Lane', 'S04E10 · Mon 9 Nov', 8.4, 4]] as [$title, $detail, $rating, $poster])
                        <li class="flex items-center gap-3 py-2">
                            <div class="aspect-2/3 w-8 shrink-0 rounded bg-linear-to-b {{ $posters[$poster] }}"></div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm/5 font-medium">{{ $title }}</p>
                                <p class="truncate text-xs/5 text-white/45"><span class="tabular-nums">{{ $detail }}</span> · <span class="text-star tabular-nums">★ {{ $rating }}</span></p>
                            </div>
                            <svg viewBox="0 0 16 16" fill="currentColor" class="size-4 shrink-0 text-white/35"><path fill-rule="evenodd" d="{{ $chevron }}" clip-rule="evenodd"/></svg>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
