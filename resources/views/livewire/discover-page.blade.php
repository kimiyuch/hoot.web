@php
    $isTv = $filters['type'] === 'tv';
    $any = $filters['when'] === 'any';
    $serviceName = $filters['service'] ? \App\Services\StreamingServices::name($filters['service']) : null;

    [$heading, $intro] = $filters['type'] === 'people' ? ['People', 'Find people who watch what you watch, and follow them to see what they follow.'] : match ($filters['when']) {
        'soon' => $isTv
            ? ['Shows premiering soon', 'New shows and new seasons in the next '.\App\Services\Premieres::DAYS_AHEAD.' days'.($serviceName ? ' on '.$serviceName : ', on every service').', most popular first.']
            : ['Movies coming out', 'Popular movies with a cinema or digital release still to come'.($serviceName ? ', on '.$serviceName : '').'.'],
        'new' => $isTv
            ? ['Shows that just premiered', 'New shows and new seasons from the last '.\App\Services\Premieres::DAYS_BACK.' days'.($serviceName ? ' on '.$serviceName : ', on every service').', most popular first.']
            : ['Movies just out', 'Popular movies released in cinemas or digitally in the last '.\App\Services\Premieres::DAYS_BACK.' days'.($serviceName ? ', on '.$serviceName : '').'.'],
        default => [$isTv ? 'All shows' : 'All movies',
            \App\Services\Browse::SORTS[$filters['sort']].' since '.$filters['from'].($serviceName ? ' on '.$serviceName.($filters['region'] === \App\Services\Browse::ALL_REGIONS ? ' in any region' : ' in '.config('hoot.regions')[$filters['region']]) : ', on every service').'.'],
    };

    $activeFilters = collect([
        $filters['when'] !== 'soon',
        $filters['service'],
        $any && $filters['sort'] !== 'popular',
        $any && $filters['from'] !== (int) (config('hoot.browse_from_year') ?? now()->year),
        $any && $filters['min_rating'],
        $any && $filters['region'] !== config('hoot.region'),
    ])->filter();
@endphp

<div>
    <div class="grid grid-cols-1 gap-x-12 lg:grid-cols-[15rem_minmax(0,1fr)] xl:gap-x-16">
        {{-- Sidebar --}}
        <aside class="max-lg:hidden">
            <x-discover-filters :discover="$this" :filters="$filters" :service-counts="$serviceCounts" :logos="$logos" id="sidebar" />
        </aside>

        <div class="min-w-0">
            <div class="flex items-start justify-between gap-6">
                <div>
                    <h1 class="font-display text-3xl font-semibold tracking-tight text-balance">{{ $heading }}</h1>
                    <p class="mt-2 max-w-[60ch] text-base/7 text-pretty text-white/50 sm:text-sm/6">{{ $intro }}</p>
                    @if (($hidden ?? []) && $filters['type'] !== 'people')
                        <p class="mt-1 text-base/7 text-white/35 sm:text-sm/6">Hiding {{ \App\Services\Hidden::describe($hidden) }}. <a href="{{ route('settings') }}#hidden" class="text-white/50 underline decoration-white/20 underline-offset-2 hover:text-white">Change</a></p>
                    @endif
                </div>
                <button type="button" data-dialog-open="filter-drawer" class="flex shrink-0 items-center gap-2 rounded-lg bg-white/8 py-2 pr-3 pl-2.5 text-base/6 font-medium hover:bg-white/12 lg:hidden">
                    <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="size-5"><path d="M10 3.75a2 2 0 1 0-4 0 2 2 0 0 0 4 0ZM17.25 4.5a.75.75 0 0 0 0-1.5h-5.5a.75.75 0 0 0 0 1.5h5.5ZM5 3.75a.75.75 0 0 1-.75.75h-1.5a.75.75 0 0 1 0-1.5h1.5a.75.75 0 0 1 .75.75ZM4.25 17a.75.75 0 0 0 0-1.5h-1.5a.75.75 0 0 0 0 1.5h1.5ZM17.25 17a.75.75 0 0 0 0-1.5h-5.5a.75.75 0 0 0 0 1.5h5.5ZM9 10a.75.75 0 0 1-.75.75h-5.5a.75.75 0 0 1 0-1.5h5.5A.75.75 0 0 1 9 10ZM17.25 10.75a.75.75 0 0 0 0-1.5h-1.5a.75.75 0 0 0 0 1.5h1.5ZM14 10a2 2 0 1 0-4 0 2 2 0 0 0 4 0ZM10 16.25a2 2 0 1 0-4 0 2 2 0 0 0 4 0Z"/></svg>
                    Filters
                    @if ($activeFilters->isNotEmpty())
                        <span class="rounded-full bg-midnight-500 px-1.5 text-xs/5 tabular-nums">{{ $activeFilters->count() }}</span>
                    @endif
                </button>
            </div>

            @if ($filters['type'] === 'people')
                @include('livewire.partials.discover-people')
            @elseif ((! $isTv || $any) && ! $moviesEnabled)
                <x-empty-state class="mt-8">This needs a TMDB key. Add TMDB_TOKEN to .env.</x-empty-state>
            @elseif ($error)
                <x-empty-state class="mt-8">{{ $error }}</x-empty-state>
            @elseif ($items->isEmpty())
                <x-empty-state class="mt-8">
                    @if ($any || $refreshedAt)
                        Nothing matches these filters.
                    @else
                        No premieres loaded yet. They’re fetched with every refresh (php artisan hoot:refresh).
                    @endif
                </x-empty-state>
            @else
                <x-media-grid class="mt-8 transition-opacity 2xl:grid-cols-2 min-[100rem]:grid-cols-3" wire:loading.class="opacity-50" wire:target="choose, goToPage">
                    @foreach ($items as $item)
                        @if ($any)
                            {{-- The TMDB catalog --}}
                            <x-media-card :title="$item['title']" :href="'https://www.themoviedb.org/'.($isTv ? 'tv' : 'movie').'/'.$item['tmdb_id']"
                                :image="$item['poster_path'] ? 'https://image.tmdb.org/t/p/w342'.$item['poster_path'] : null"
                                :overview="$item['overview'] ?? null" :rating="$item['rating']" :cast="$item['cast'] ?? []">
                                <x-slot:meta>{{ $item['year'] }}</x-slot:meta>
                                <x-slot:action>
                                    @if ($isTv)
                                        <livewire:library-actions :key="'show-tmdb_id-'.$item['tmdb_id']" type="show" :tmdb-id="$item['tmdb_id']" :label="$item['title']" :following="$followedShows->has($item['tmdb_id'])" :favourite="$favouriteShows->get($item['tmdb_id'])" />
                                    @else
                                        <livewire:library-actions :key="'movie-tmdb_id-'.$item['tmdb_id']" type="movie" :tmdb-id="$item['tmdb_id']" :label="$item['title']" :following="$followedMovies->has($item['tmdb_id'])" :favourite="$favouriteMovies->get($item['tmdb_id'])" />
                                    @endif
                                </x-slot:action>
                            </x-media-card>
                        @elseif ($isTv)
                            {{-- Show premieres --}}
                            @php $at = \Carbon\CarbonImmutable::parse($item['airs_at'])->setTimezone($timezone); @endphp
                            <x-media-card :title="$item['name']" :href="$item['url']" :image="$item['image_url']" :overview="$item['overview'] ?? null" :rating="$item['rating'] ?? null" :cast="$item['cast'] ?? []">
                                <x-slot:meta>
                                    <span class="flex min-w-0 flex-wrap items-center gap-x-1.5">
                                        <span @class(['text-calendar-400' => $item['season'] === 1])>{{ $item['season'] === 1 ? 'New show' : 'Season '.$item['season'] }}</span>
                                        <span aria-hidden="true">·</span>
                                        <time datetime="{{ $at->toIso8601String() }}">{{ $at->format('D j M') }}</time>
                                        @if ($item['channel'])
                                            <span aria-hidden="true">·</span>
                                            <x-service :service="$item['service'] ?? null" :channel="$item['channel']" :logos="$logos" :href="\App\Services\StreamingServices::link($item['service'] ?? null, $item['official_url'] ?? null, $item['name'])" />
                                        @endif
                                    </span>
                                </x-slot:meta>
                                <x-slot:action>
                                    <livewire:library-actions :key="'show-tvmaze_id-'.$item['tvmaze_id']" type="show" :tvmaze-id="$item['tvmaze_id']" :label="$item['name']" :following="$followedShows->has($item['tvmaze_id'])" :favourite="$favouriteShows->get($item['tvmaze_id'])" />
                                </x-slot:action>
                            </x-media-card>
                        @else
                            {{-- Movie premieres --}}
                            @php
                                $cinema = $item['cinema_date'] ? \Carbon\CarbonImmutable::parse($item['cinema_date']) : null;
                                $digital = $item['digital_date'] ? \Carbon\CarbonImmutable::parse($item['digital_date']) : null;
                            @endphp
                            <x-media-card :title="$item['title']" :href="'https://www.themoviedb.org/movie/'.$item['tmdb_id']"
                                :image="$item['poster_path'] ? 'https://image.tmdb.org/t/p/w342'.$item['poster_path'] : null"
                                :overview="$item['overview'] ?? null" :rating="$item['rating'] ?? null" :cast="$item['cast'] ?? []">
                                <x-slot:meta>
                                    {{ collect([$cinema ? 'Cinema '.$cinema->format('j M') : null, $digital ? 'Digital '.$digital->format('j M') : null])->filter()->join(' · ') ?: $item['year'] }}
                                </x-slot:meta>
                                @if (! empty($item['services']))
                                    <div class="mt-3 flex gap-1.5">
                                        @foreach (array_slice($item['services'], 0, 5) as $key)
                                            <x-service :service="$key" :logos="$logos" logo-only :href="\App\Services\StreamingServices::link($key, null, $item['title'])" />
                                        @endforeach
                                    </div>
                                @endif
                                <x-slot:action>
                                    <livewire:library-actions :key="'movie-tmdb_id-'.$item['tmdb_id']" type="movie" :tmdb-id="$item['tmdb_id']" :label="$item['title']" :following="$followedMovies->has($item['tmdb_id'])" :favourite="$favouriteMovies->get($item['tmdb_id'])" />
                                </x-slot:action>
                            </x-media-card>
                        @endif
                    @endforeach
                </x-media-grid>

                @if ($totalPages > 1)
                    <nav class="mt-16 flex items-center justify-between border-t border-white/10 pt-6" aria-label="Pages">
                        <div class="flex-1">
                            @if ($filters['page'] > 1)
                                <a href="{{ $this->url(['page' => $filters['page'] - 1]) }}" wire:click.prevent="goToPage({{ $filters['page'] - 1 }})" class="text-sm/6 font-medium text-white/60 hover:text-white"><span aria-hidden="true">←</span> Previous</a>
                            @endif
                        </div>
                        <p class="text-sm/6 text-white/40 tabular-nums max-sm:hidden">{{ 'Page '.$filters['page'].(($pagesCounted ?? true) ? ' of '.$totalPages : '') }}</p>
                        <div class="flex flex-1 justify-end">
                            @if ($filters['page'] < $totalPages)
                                <a href="{{ $this->url(['page' => $filters['page'] + 1]) }}" wire:click.prevent="goToPage({{ $filters['page'] + 1 }})" class="text-sm/6 font-medium text-white/60 hover:text-white">Next <span aria-hidden="true">→</span></a>
                            @endif
                        </div>
                    </nav>
                @endif
            @endif

            @if ($refreshedAt && $items->isNotEmpty())
                <p class="mt-16 text-sm/6 text-white/35">Premieres updated {{ \Carbon\CarbonImmutable::parse($refreshedAt)->diffForHumans() }}.</p>
            @endif
        </div>
    </div>

    {{-- Filters drawer for small screens --}}
    <dialog id="filter-drawer" wire:ignore.self aria-labelledby="filter-drawer-title"
        class="m-0 ml-auto h-dvh max-h-dvh w-full max-w-sm bg-night-900 p-0 text-white inset-ring inset-ring-white/10 backdrop:bg-black/75 lg:hidden">
        <div class="flex h-full flex-col">
            <div class="flex items-center justify-between border-b border-white/5 px-6 py-4">
                <h2 id="filter-drawer-title" class="font-display text-lg font-semibold">Filters</h2>
                <button type="button" data-dialog-close class="relative -mr-2 rounded-lg p-2 text-white/60 hover:bg-white/5 hover:text-white" aria-label="Close">
                    <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="size-5"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 0 0-1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/></svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto px-6 py-6">
                <x-discover-filters :discover="$this" :filters="$filters" :service-counts="$serviceCounts" :logos="$logos" id="drawer" />
            </div>
        </div>
    </dialog>
</div>
