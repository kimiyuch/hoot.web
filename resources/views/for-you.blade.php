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
            <img src="{{ asset('images/hoot-head.svg') }}" alt="" class="size-24">
            <h1 class="mt-8 font-display text-3xl font-semibold tracking-tight text-balance">Nothing followed yet</h1>
            <p class="mt-4 max-w-[48ch] text-lg text-pretty text-white/55">Follow shows and movies, and Hoot keeps watch: every new episode and release, on every service, in your timezone.</p>
            <div class="mt-10 flex items-center gap-6">
                <a href="{{ route('discover') }}" class="rounded-lg bg-white px-3.5 py-2 text-sm/6 font-semibold text-night-950 hover:bg-midnight-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-midnight-400">See what’s premiering</a>
                <a href="{{ route('discover', ['when' => 'any']) }}" class="text-sm/6 font-semibold text-white/70 hover:text-white">Browse everything <span aria-hidden="true">→</span></a>
            </div>
        </div>
    @else
        {{-- The heading sits in the left column; the sidebar is pushed down to line up with the teaser. --}}
        <div class="grid grid-cols-1 gap-x-16 gap-y-16 lg:grid-cols-[minmax(0,48rem)_minmax(20rem,28rem)] lg:justify-between">
            <div class="min-w-0">
                <h1 class="font-display text-3xl font-semibold tracking-tight">Upcoming</h1>
                <p class="mt-1 text-sm/6 text-white/40">Times in {{ str_replace('_', ' ', $timezone) }}</p>

                {{-- Up next --}}
                @if ($next)
                    @php
                        $isEpisodes = $next['kind'] === 'episodes';
                        $item = $isEpisodes ? $next['show'] : $next['movie'];
                        $heroTitle = $isEpisodes ? $item->name : $item->title;
                        $heroPoster = $isEpisodes ? $item->image_url : $item->posterUrl();
                        $heroBackdrop = $isEpisodes ? ($item->image_large_url ?? $item->image_url) : ($item->backdropUrl() ?? $item->posterUrl());
                    @endphp
                    <section aria-labelledby="up-next" class="relative isolate mt-10 overflow-hidden rounded-2xl bg-night-900 inset-ring inset-ring-white/5">
                        @if ($heroBackdrop)
                            <img src="{{ $heroBackdrop }}" alt="" class="absolute inset-0 -z-10 size-full scale-125 object-cover opacity-30 blur-3xl saturate-150">
                        @endif
                        <div class="flex items-center gap-5 p-5 sm:gap-8 sm:p-4">
                            <x-poster :src="$heroPoster" alt="" class="aspect-2/3 w-20 rounded-lg sm:w-32" />
                            <div class="min-w-0">
                                <p id="up-next" class="text-sm/6 font-medium text-next">
                                    Up next ·
                                    @if ($next['timed'])
                                        {{ $next['at']->diffForHumans(['parts' => 2, 'join' => true]) }}
                                    @else
                                        {{ $next['at']->isToday() ? 'today' : $next['at']->diffForHumans(['parts' => 1]) }}
                                    @endif
                                </p>
                                <h2 class="mt-1 max-w-[24ch] font-display text-2xl font-semibold tracking-tight text-balance sm:text-3xl">
                                    <a href="{{ $isEpisodes ? $item->url : $item->url() }}" target="_blank" rel="noopener" class="hover:text-midnight-200">{{ $heroTitle }}</a>
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
                    <x-empty-state class="mt-8">Nothing comes out in the next {{ config('hoot.days_ahead') }} days for what you follow.</x-empty-state>
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

            {{-- Sidebar. On wide screens it starts level with the teaser (or the timeline when there's no teaser), not the heading:
                 h1 (36px) + timezone line (4px + 24px) + the teaser's mt-10 (40px) = 104px; the timeline's mt-12 makes it 112px. --}}
            <aside @class(['flex flex-col gap-12', 'lg:pt-26' => $next, 'lg:pt-28' => ! $next])>
                @if ($recent->isNotEmpty())
                    <section aria-labelledby="just-released">
                        <h2 id="just-released" class="font-display text-lg font-semibold tracking-tight">Just released</h2>
                        <p class="text-sm/6 text-white/40">In the last {{ config('hoot.days_back') }} days</p>
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
                                            <a href="{{ $isEpisodes ? $item->url : $item->url() }}" target="_blank" rel="noopener" class="before:absolute before:inset-0 group-hover:text-midnight-200">{{ $isEpisodes ? $item->name : $item->title }}</a>
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

                {{-- What you follow. Each one opens (a <details>) to its rating, Unfollow, description and dates. --}}
                <section aria-labelledby="following" class="border-t border-white/5 pt-8 first:border-t-0 first:pt-0">
                    <div class="flex items-baseline justify-between gap-4">
                        <div>
                            <h2 id="following" class="font-display text-lg font-semibold tracking-tight">Following</h2>
                            <p class="text-sm/6 text-white/40 tabular-nums">{{ $shows->count() }} {{ Str::plural('show', $shows->count()) }} · {{ $movies->count() }} {{ Str::plural('movie', $movies->count()) }}</p>
                        </div>
                    </div>
                    <ul role="list" class="mt-4 divide-y divide-white/5">
                        @foreach ($shows as $show)
                            @php
                                $now = now();
                                // Newest season first; episodes in order within a season.
                                $seasons = $show->episodes->groupBy('season')->sortKeysDesc();
                            @endphp
                            {{-- Each show opens to all its episodes. --}}
                            <li>
                                <details class="group/show">
                                    <summary class="flex cursor-pointer list-none items-center gap-3 py-2.5 [&::-webkit-details-marker]:hidden">
                                        <x-poster :src="$show->image_url" alt="" class="aspect-2/3 w-9 rounded" />
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-base/6 font-medium group-hover/show:text-midnight-200 sm:text-sm/5">{{ $show->name }}</p>
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
                                        <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" class="size-4 shrink-0 text-white/35 transition-transform group-open/show:rotate-180"><path fill-rule="evenodd" d="M4.22 6.22a.75.75 0 0 1 1.06 0L8 8.94l2.72-2.72a.75.75 0 1 1 1.06 1.06l-3.25 3.25a.75.75 0 0 1-1.06 0L4.22 7.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg>
                                    </summary>

                                    {{-- Rating and description, then the episodes. --}}
                                    <div class="pb-4">
                                        <div class="flex items-center gap-3">
                                            <x-rating :value="$show->rating" class="text-sm/6" />
                                            <x-favourite-button :action="route('favourites.shows.store')" :fields="['tvmaze_id' => $show->tvmaze_id]" :label="$show->name"
                                                :remove-action="$show->pivot->favourite_position ? route('favourites.shows.destroy', $show) : null" class="ml-auto" />
                                            <x-follow-button following :unfollow-action="route('shows.unfollow', $show)" :label="$show->name" />
                                        </div>
                                        @if ($show->overview)
                                            <p class="mt-1 text-sm/6 text-pretty text-white/60">{{ $show->overview }}</p>
                                        @endif
                                        @if ($seasons->isEmpty())
                                            <p class="mt-3 text-sm/6 text-white/40">No episodes listed yet.</p>
                                        @else
                                            <div class="scrollbar-thin mt-3 max-h-72 overflow-y-auto overscroll-contain">
                                                @foreach ($seasons as $season => $episodes)
                                                    <p class="pt-2 pb-1 text-xs/5 font-medium text-white/40 first:pt-0">{{ $season ? 'Season '.$season : 'Specials' }}</p>
                                                    <ul role="list" class="divide-y divide-white/5">
                                                        @foreach ($episodes as $episode)
                                                            @php
                                                                $at = $episode->airs_at?->setTimezone($timezone);
                                                                $upcoming = $at && $at->isAfter($now);
                                                                $isNext = $show->nextEpisode?->is($episode);
                                                            @endphp
                                                            <li @class(['flex items-baseline gap-2 py-2 pr-1 text-sm/5', 'text-white/45' => ! $upcoming, 'text-white/80' => $upcoming && ! $isNext, 'text-next' => $isNext])>
                                                                <span class="shrink-0 tabular-nums">{{ $episode->code() }}</span>
                                                                <span @class(['min-w-0 flex-1 truncate', 'italic opacity-60' => blank($episode->name)])>{{ filled($episode->name) ? $episode->name : '[No title yet]' }}</span>
                                                                @if ($at)
                                                                    <span class="shrink-0 text-xs/5 tabular-nums">{{ $at->format($at->year === $now->year ? 'j M' : 'j M Y') }}</span>
                                                                @endif
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @endforeach
                                            </div>
                                        @endif
                                        <a href="{{ $show->url }}" target="_blank" rel="noopener" class="mt-3 inline-flex items-center gap-1 text-xs/5 font-medium text-white/50 hover:text-white">
                                            On TVmaze <span aria-hidden="true">↗</span>
                                        </a>
                                    </div>
                                </details>
                            </li>
                        @endforeach
                        @foreach ($movies as $movie)
                            <li>
                                <details class="group/show">
                                    <summary class="flex cursor-pointer list-none items-center gap-3 py-2.5 [&::-webkit-details-marker]:hidden">
                                        <x-poster :src="$movie->posterUrl()" alt="" class="aspect-2/3 w-9 rounded" />
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-base/6 font-medium group-hover/show:text-midnight-200 sm:text-sm/5">{{ $movie->title }}</p>
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
                                        <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" class="size-4 shrink-0 text-white/35 transition-transform group-open/show:rotate-180"><path fill-rule="evenodd" d="M4.22 6.22a.75.75 0 0 1 1.06 0L8 8.94l2.72-2.72a.75.75 0 1 1 1.06 1.06l-3.25 3.25a.75.75 0 0 1-1.06 0L4.22 7.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg>
                                    </summary>

                                    <div class="pb-4">
                                        <div class="flex items-center gap-3">
                                            <x-rating :value="$movie->rating" class="text-sm/6" />
                                            <x-favourite-button :action="route('favourites.movies.store')" :fields="['tmdb_id' => $movie->tmdb_id]" :label="$movie->title"
                                                :remove-action="$movie->pivot->favourite_position ? route('favourites.movies.destroy', $movie) : null" class="ml-auto" />
                                            <x-follow-button following :unfollow-action="route('movies.unfollow', $movie)" :label="$movie->title" />
                                        </div>
                                        @if ($movie->overview)
                                            <p class="mt-1 text-sm/6 text-pretty text-white/60">{{ $movie->overview }}</p>
                                        @endif
                                        <dl class="mt-3 divide-y divide-white/5 text-sm/5">
                                            <div class="flex justify-between gap-4 py-2"><dt class="text-white/45">In cinemas</dt><dd class="text-white/80 tabular-nums">{{ $movie->cinema_date?->format('j M Y') ?? 'Not announced' }}</dd></div>
                                            <div class="flex justify-between gap-4 py-2"><dt class="text-white/45">Digital</dt><dd class="text-white/80 tabular-nums">{{ $movie->digital_date?->format('j M Y') ?? 'Not announced' }}</dd></div>
                                        </dl>
                                        <a href="{{ $movie->url() }}" target="_blank" rel="noopener" class="mt-3 inline-flex items-center gap-1 text-xs/5 font-medium text-white/50 hover:text-white">
                                            On TMDB <span aria-hidden="true">↗</span>
                                        </a>
                                    </div>
                                </details>
                            </li>
                        @endforeach
                    </ul>
                </section>

                <section id="calendar-feed" aria-labelledby="calendar-feed-title" class="border-t border-white/5 pt-8">
                    <h2 id="calendar-feed-title" class="font-display text-lg font-semibold tracking-tight">In your calendar</h2>
                    <p class="mt-1 text-base/7 text-pretty text-white/50 sm:text-sm/6">Subscribe once and every episode and release shows up in Apple Calendar or Google Calendar (Other calendars → From URL). It updates by itself. The link is yours alone; you can make a new one in <a href="{{ route('settings') }}#calendar" class="text-white/70 underline decoration-white/20 underline-offset-2 hover:text-white">Settings</a>.</p>
                    <div class="mt-4 flex items-center gap-2">
                        <a href="{{ auth()->user()->calendarSubscribeUrl() }}" class="rounded-lg bg-white px-3 py-1.5 text-sm/6 font-semibold text-night-950 hover:bg-midnight-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-midnight-400">Subscribe</a>
                        <button type="button" data-copy="{{ auth()->user()->calendarUrl() }}" class="rounded-lg bg-white/8 px-3 py-1.5 text-sm/6 font-medium hover:bg-white/12">Copy link</button>
                    </div>
                </section>
            </aside>
        </div>
    @endif
</x-layouts.app>
