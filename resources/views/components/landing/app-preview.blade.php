{{-- A still of the For you page for the landing page: made-up shows, posters as plum gradients. --}}
@php
    $posters = ['from-midnight-500 to-night-700', 'from-calendar-500/70 to-night-700', 'from-next/60 to-night-700', 'from-star/60 to-night-700', 'from-midnight-300/70 to-night-800'];
    $days = [
        ['Today', 'Fri 6 Nov', [
            ['Harbour Lights', 'S02E05 · The Fog Bell', '21:00', 'Apple TV', 0],
            ['Paper Moons', 'In cinemas · first in AU', null, null, 3],
        ]],
        ['Tomorrow', 'Sat 7 Nov', [
            ['The Long Way North', 'S01E01–S01E08 · all 8 episodes', '09:00', 'Netflix', 2],
        ]],
        ['Monday', 'Mon 9 Nov', [
            ['Gumtree Lane', 'S04E10 · Possums in the Roof', '03:00', 'HBO Max', 4],
        ]],
    ];
@endphp
<div {{ $attributes->class('overflow-hidden rounded-2xl bg-night-900/80 p-4 shadow-2xl shadow-black/40 inset-ring inset-ring-white/10 sm:p-6') }} aria-hidden="true">
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-[minmax(0,1fr)_16rem]">
        <div class="min-w-0">
            {{-- Up next --}}
            <div class="relative isolate overflow-hidden rounded-xl bg-night-800 inset-ring inset-ring-white/5">
                <div class="absolute inset-0 -z-10 bg-linear-to-br from-midnight-600/40 via-transparent to-transparent"></div>
                <div class="flex items-center gap-5 p-4">
                    <div class="aspect-2/3 w-16 shrink-0 rounded-lg bg-linear-to-b {{ $posters[0] }} sm:w-20"></div>
                    <div class="min-w-0">
                        <p class="text-sm/6 font-medium text-next">Up next · in 3 hours</p>
                        <p class="mt-1 font-display text-xl font-semibold tracking-tight sm:text-2xl">Harbour Lights</p>
                        <p class="mt-1 text-sm/6 text-white/65"><span class="text-white">S02E05</span> · The Fog Bell</p>
                        <p class="mt-2 text-sm/6 text-white/55"><span class="text-white/80 tabular-nums">Friday 6 Nov, 21:00</span> · Apple TV</p>
                    </div>
                </div>
            </div>

            {{-- Timeline --}}
            <div class="mt-8 flex flex-col gap-6">
                @foreach ($days as [$label, $date, $rows])
                    <div>
                        <p class="flex items-baseline gap-2 border-b border-white/8 pb-2 text-sm/6">
                            <span class="font-semibold">{{ $label }}</span>
                            <span class="text-white/40">{{ $date }}</span>
                        </p>
                        <ul role="list" class="mt-1">
                            @foreach ($rows as [$title, $detail, $time, $service, $poster])
                                <li class="flex items-center gap-4 py-2">
                                    <div class="aspect-2/3 w-9 shrink-0 rounded-md bg-linear-to-b {{ $posters[$poster] }}"></div>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm/6 font-medium">{{ $title }}</p>
                                        <p class="truncate text-sm/6 text-white/50">{{ $detail }}</p>
                                    </div>
                                    <div class="shrink-0 text-right text-sm/6">
                                        <p class="text-white/80 tabular-nums">{{ $time ?? 'All day' }}</p>
                                        @if ($service)
                                            <p class="text-white/45">{{ $service }}</p>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="max-lg:hidden">
            <p class="font-display font-semibold tracking-tight">Just released</p>
            <p class="text-sm/6 text-white/40">In the last 7 days</p>
            <ul role="list" class="mt-3 divide-y divide-white/5">
                @foreach ([['Gumtree Lane', 'S04E09 · Monday', 4], ['Salt & Ember', 'Digital · Tue 3 Nov', 1], ['Harbour Lights', 'S02E04 · Fri 30 Oct', 0]] as [$title, $detail, $poster])
                    <li class="flex items-center gap-3 py-2.5">
                        <div class="aspect-2/3 w-8 shrink-0 rounded bg-linear-to-b {{ $posters[$poster] }}"></div>
                        <div class="min-w-0">
                            <p class="truncate text-sm/5 font-medium">{{ $title }}</p>
                            <p class="truncate text-sm/5 text-white/45">{{ $detail }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
