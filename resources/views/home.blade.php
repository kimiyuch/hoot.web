<x-layouts.app title="For you">
    @php
        $today = \Carbon\CarbonImmutable::now($timezone)->startOfDay();
        $days = collect($sections)->flatMap(fn ($days) => $days);
        $dayLabel = function (\Carbon\CarbonImmutable $date) use ($today) {
            $diff = (int) $today->diffInDays($date->startOfDay());
            return match (true) {
                $diff === 0 => 'Today',
                $diff === 1 => 'Tomorrow',
                $diff < 7 => $date->format('l'),
                default => $date->format('D j M'),
            };
        };
    @endphp

    @if ($shows->isEmpty() && $movies->isEmpty())
        <div class="mx-auto flex max-w-xl flex-col items-center py-16 text-center">
            <img src="{{ asset('images/possum-head.svg') }}" alt="" class="size-24">
            <h1 class="mt-8 font-display text-3xl font-semibold tracking-tight text-balance">Nothing followed yet</h1>
            <p class="mt-4 max-w-[48ch] text-lg text-pretty text-white/55">Follow shows and movies, and Possum keeps watch: every new episode and release, on every service, in your timezone.</p>
            <div class="mt-10 flex items-center gap-6">
                <a href="{{ route('discover') }}" class="rounded-lg bg-white px-3.5 py-2 text-sm/6 font-semibold text-night-950 hover:bg-midnight-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-midnight-400">See what’s premiering</a>
                <a href="{{ route('discover', ['when' => 'any']) }}" class="text-sm/6 font-semibold text-white/70 hover:text-white">Browse everything <span aria-hidden="true">→</span></a>
            </div>
        </div>
    @else
        <div>
            <h1 class="font-display text-3xl font-semibold tracking-tight">Upcoming</h1>
            <p class="mt-1 text-sm/6 text-white/40">Times in {{ str_replace('_', ' ', $timezone) }}</p>
        </div>

        <div class="mt-10 grid grid-cols-1 gap-x-16 gap-y-16 lg:grid-cols-[minmax(0,48rem)_minmax(20rem,28rem)] lg:justify-between">
            <div class="min-w-0">
                {{-- Up next --}}
                @if ($next)
                    @php
                        $isEpisodes = $next['kind'] === 'episodes';
                        $item = $isEpisodes ? $next['show'] : $next['movie'];
                        $heroTitle = $isEpisodes ? $item->name : $item->title;
                        $heroPoster = $isEpisodes ? $item->image_url : $item->posterUrl();
                        $heroBackdrop = $isEpisodes ? ($item->image_large_url ?? $item->image_url) : ($item->backdropUrl() ?? $item->posterUrl());
                    @endphp
                    <section aria-labelledby="up-next" class="relative isolate overflow-hidden rounded-2xl bg-night-900 inset-ring inset-ring-white/5">
                        @if ($heroBackdrop)
                            <img src="{{ $heroBackdrop }}" alt="" class="absolute inset-0 -z-10 size-full scale-125 object-cover opacity-30 blur-3xl saturate-150">
                        @endif
                        <div class="flex items-center gap-5 p-5 sm:gap-8 sm:p-4">
                            <x-poster :src="$heroPoster" alt="" class="aspect-2/3 w-20 rounded-lg sm:w-32" />
                            <div class="min-w-0">
                                <p id="up-next" class="text-sm/6 font-medium text-calendar-400">
                                    Up next ·
                                    @if ($next['timed'])
                                        {{ $next['at']->diffForHumans(['parts' => 2, 'join' => true]) }}
                                    @else
                                        {{ $next['at']->isToday() ? 'today' : $next['at']->diffForHumans(['parts' => 1]) }}
                                    @endif
                                </p>
                                <h2 class="mt-1 max-w-[24ch] font-display text-2xl font-semibold tracking-tight text-balance sm:text-3xl">
                                    <a href="{{ $isEpisodes ? $item->url : $item->url() }}" class="hover:text-midnight-200">{{ $heroTitle }}</a>
                                </h2>
                                <p class="mt-2 max-w-[56ch] text-base/7 text-pretty text-white/65">
                                    @if ($isEpisodes)
                                        <span class="text-white">{{ \App\Services\Calendar::codes($next['episodes']) }}</span>
                                        @if ($next['episodes']->count() > 1)
                                            · all {{ $next['episodes']->count() }} episodes at once
                                        @elseif ($next['episodes']->first()->name)
                                            · {{ $next['episodes']->first()->name }}
                                        @endif
                                    @else
                                        {{ $next['kind'] === 'digital' ? 'Digital release' : 'In cinemas' }}
                                    @endif
                                </p>
                                <div class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm/6 text-white/55">
                                    <span class="text-white/80 tabular-nums">{{ $next['at']->format($next['timed'] ? 'l j M, H:i' : 'l j M') }}</span>
                                    @if ($isEpisodes)
                                        <x-service :service="$item->service()" :channel="$item->channel" :logos="$logos" />
                                    @endif
                                    <x-rating :value="$item->rating" />
                                </div>
                            </div>
                        </div>
                    </section>
                @endif

                {{-- Timeline --}}
                @if ($days->isEmpty())
                    <x-empty-state class="mt-8">Nothing comes out in the next {{ config('possum.days_ahead') }} days for what you follow.</x-empty-state>
                @else
                    <div class="mt-12 flex flex-col gap-10">
                        @foreach ($days as $day => $releases)
                            @php $date = \Carbon\CarbonImmutable::parse($day, $timezone); @endphp
                            <section aria-label="{{ $date->format('l j F') }}">
                                {{-- A small label above the line, then that day's releases. --}}
                                <h2 class="flex items-baseline gap-2 border-b border-white/8 pb-2 text-sm/6">
                                    <span class="font-semibold text-white">{{ $dayLabel($date) }}</span>
                                    @if ($dayLabel($date) !== $date->format('D j M'))
                                        <span class="text-white/40">{{ $date->format('D j M') }}</span>
                                    @endif
                                </h2>
                                <ul role="list" class="mt-2">
                                    @foreach ($releases as $release)
                                        <x-release-row :release="$release" :logos="$logos" />
                                    @endforeach
                                </ul>
                            </section>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Sidebar --}}
            <aside class="flex flex-col gap-12">
                @if ($recent->isNotEmpty())
                    <section aria-labelledby="just-released">
                        <h2 id="just-released" class="font-display text-lg font-semibold tracking-tight">Just released</h2>
                        <p class="text-sm/6 text-white/40">In the last {{ config('possum.days_back') }} days</p>
                        <ul role="list" class="mt-4 divide-y divide-white/5">
                            @foreach ($recent as $release)
                                @php
                                    $isEpisodes = $release['kind'] === 'episodes';
                                    $item = $isEpisodes ? $release['show'] : $release['movie'];
                                @endphp
                                <li class="group relative flex items-center gap-3 py-2.5">
                                    <x-poster :src="$isEpisodes ? $item->image_url : $item->posterUrl()" alt="" class="aspect-2/3 w-9 rounded" />
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-base/6 font-medium sm:text-sm/5">
                                            <a href="{{ $isEpisodes ? $item->url : $item->url() }}" class="before:absolute before:inset-0 group-hover:text-midnight-200">{{ $isEpisodes ? $item->name : $item->title }}</a>
                                        </p>
                                        <p class="truncate text-sm/5 text-white/45">
                                            {{ $isEpisodes ? \App\Services\Calendar::codes($release['episodes']) : ($release['kind'] === 'digital' ? 'Digital' : 'In cinemas') }}
                                            · {{ $release['at']->isYesterday() ? 'yesterday' : $release['at']->format('D j M') }}
                                        </p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                {{-- What you follow. "Manage" shows the Unfollow buttons (a checkbox, so no JS needed). --}}
                <section aria-labelledby="following" class="group/following border-t border-white/5 pt-8 first:border-t-0 first:pt-0">
                    <div class="flex items-baseline justify-between gap-4">
                        <div>
                            <h2 id="following" class="font-display text-lg font-semibold tracking-tight">Following</h2>
                            <p class="text-sm/6 text-white/40 tabular-nums">{{ $shows->count() }} {{ Str::plural('show', $shows->count()) }} · {{ $movies->count() }} {{ Str::plural('movie', $movies->count()) }}</p>
                        </div>
                        <label class="relative cursor-pointer rounded-lg px-2.5 py-1 text-sm/6 font-medium text-white/55 select-none hover:bg-white/5 hover:text-white has-focus-visible:outline-2 has-focus-visible:outline-midnight-400">
                            <input type="checkbox" class="sr-only">
                            <span class="group-has-checked/following:hidden">Manage</span>
                            <span class="hidden text-white group-has-checked/following:inline">Done</span>
                            <span class="absolute top-1/2 left-1/2 size-[max(100%,3rem)] -translate-1/2 pointer-fine:hidden" aria-hidden="true"></span>
                        </label>
                    </div>
                    <ul role="list" class="mt-4 divide-y divide-white/5">
                        @foreach ($shows as $show)
                            <li class="group relative flex items-center gap-3 py-2.5">
                                <x-poster :src="$show->image_url" alt="" class="aspect-2/3 w-9 rounded" />
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-base/6 font-medium sm:text-sm/5">
                                        <a href="{{ $show->url }}" class="before:absolute before:inset-0 group-hover:text-midnight-200">{{ $show->name }}</a>
                                    </p>
                                    <p class="truncate text-sm/5 text-white/45">
                                        @if ($show->nextEpisode)
                                            <span class="tabular-nums">{{ $show->nextEpisode->code() }}</span> · {{ $show->nextEpisode->airs_at->setTimezone($timezone)->format('D j M') }}
                                        @else
                                            {{ $show->status === 'Ended' ? 'Ended' : 'No date yet' }}
                                        @endif
                                        @if ($show->channel)
                                            · {{ $show->channel }}
                                        @endif
                                    </p>
                                </div>
                                <x-follow-button following :unfollow-action="route('shows.unfollow', $show)" :label="$show->name" class="relative hidden shrink-0 group-has-checked/following:block" />
                            </li>
                        @endforeach
                        @foreach ($movies as $movie)
                            @php $date = $movie->digital_date ?? $movie->cinema_date; @endphp
                            <li class="group relative flex items-center gap-3 py-2.5">
                                <x-poster :src="$movie->posterUrl()" alt="" class="aspect-2/3 w-9 rounded" />
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-base/6 font-medium sm:text-sm/5">
                                        <a href="{{ $movie->url() }}" class="before:absolute before:inset-0 group-hover:text-midnight-200">{{ $movie->title }}</a>
                                    </p>
                                    <p class="truncate text-sm/5 text-white/45">
                                        @if ($movie->digital_date)
                                            Digital {{ $movie->digital_date->format('j M Y') }}
                                        @elseif ($movie->cinema_date)
                                            In cinemas {{ $movie->cinema_date->format('j M Y') }}
                                        @else
                                            No date yet
                                        @endif
                                    </p>
                                </div>
                                <x-follow-button following :unfollow-action="route('movies.unfollow', $movie)" :label="$movie->title" class="relative hidden shrink-0 group-has-checked/following:block" />
                            </li>
                        @endforeach
                    </ul>
                </section>

                <section id="calendar-feed" aria-labelledby="calendar-feed-title" class="border-t border-white/5 pt-8">
                    <h2 id="calendar-feed-title" class="font-display text-lg font-semibold tracking-tight">In your calendar</h2>
                    <p class="mt-1 text-base/7 text-pretty text-white/50 sm:text-sm/6">Subscribe once and every episode and release shows up in Apple Calendar or Google Calendar (Other calendars → From URL). It updates by itself.</p>
                    <div class="mt-4 flex items-center gap-2">
                        <a href="{{ preg_replace('#^https?://#', 'webcal://', route('calendar')) }}" class="rounded-lg bg-white px-3 py-1.5 text-sm/6 font-semibold text-night-950 hover:bg-midnight-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-midnight-400">Subscribe</a>
                        <button type="button" data-copy="{{ route('calendar') }}" class="rounded-lg bg-white/8 px-3 py-1.5 text-sm/6 font-medium hover:bg-white/12">Copy link</button>
                    </div>
                </section>
            </aside>
        </div>
    @endif
</x-layouts.app>
