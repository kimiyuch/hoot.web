<x-layouts.app :title="$query !== '' ? 'Search: '.$query : 'Search'">
    <form action="{{ route('search') }}" method="get" role="search" class="grid max-w-2xl grid-cols-1">
        <input type="search" name="q" aria-label="Find a show, movie or person" value="{{ $query }}" placeholder="Find a show, movie or person" @if ($query === '') autofocus @endif
            class="col-start-1 row-start-1 rounded-xl bg-white/5 py-3 pr-4 pl-12 text-lg text-white inset-ring inset-ring-white/10 placeholder:text-white/35 focus:bg-white/8 focus:outline-2 focus:-outline-offset-1 focus:outline-midnight-400">
        <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="pointer-events-none col-start-1 row-start-1 ml-4 size-5 self-center text-white/35"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.45 4.39l3.08 3.08a.75.75 0 1 1-1.06 1.06l-3.08-3.08A7 7 0 0 1 2 9Z" clip-rule="evenodd"/></svg>
    </form>

    @foreach ($searchErrors as $error)
        <p class="mt-6 text-base/7 text-calendar-400 sm:text-sm/6">{{ $error }}</p>
    @endforeach

    @if ($query !== '' && ($onlyPeople || $people->isNotEmpty()))
        <section class="mt-12" aria-labelledby="results-people">
            <h2 id="results-people" class="font-display text-xl font-semibold tracking-tight">People</h2>
            @if ($people->isEmpty())
                <x-empty-state class="mt-4">Nobody found.</x-empty-state>
            @else
                <ul role="list" class="mt-2 grid grid-cols-1 gap-x-10 divide-white/5 sm:grid-cols-2 [&>li]:border-b [&>li]:border-white/5">
                    @foreach ($people as $person)
                        <x-person-row :person="$person">
                            <x-slot:actions>
                                <livewire:person-actions :key="'person-'.$person->id" :user-id="$person->id" />
                            </x-slot:actions>
                        </x-person-row>
                    @endforeach
                </ul>
            @endif
        </section>
    @endif

    @if ($query !== '' && ! $onlyPeople)
        <section class="mt-12" aria-labelledby="results-shows">
            <h2 id="results-shows" class="font-display text-xl font-semibold tracking-tight">Shows</h2>
            @if (empty($shows))
                <x-empty-state class="mt-4">No shows found.</x-empty-state>
            @else
                <x-media-grid class="mt-6">
                    @foreach ($shows as $show)
                        <x-media-card :title="$show['name']" :href="$show['url']" :image="$show['image_url']" :overview="$show['overview'] ?? null" :rating="$show['rating']" :friends="$circle['show'][$show['tvmaze_id']] ?? []">
                            <x-slot:meta>{{ collect([$show['year'], $show['channel'], $show['status'] === 'Ended' ? 'Ended' : null])->filter()->join(' · ') }}</x-slot:meta>
                            <x-slot:action>
                                <livewire:library-actions :key="'show-tvmaze_id-'.$show['tvmaze_id']" type="show" :tvmaze-id="$show['tvmaze_id']" :label="$show['name']" :following="$followedShows->has($show['tvmaze_id'])" :favourite="$favouriteShows->get($show['tvmaze_id'])" />
                            </x-slot:action>
                        </x-media-card>
                    @endforeach
                </x-media-grid>
            @endif
        </section>

        <section class="mt-16" aria-labelledby="results-movies">
            <h2 id="results-movies" class="font-display text-xl font-semibold tracking-tight">Movies</h2>
            @if (! $moviesEnabled)
                <x-empty-state class="mt-4">Movies need a TMDB key. Add TMDB_TOKEN to .env.</x-empty-state>
            @elseif (empty($movies))
                <x-empty-state class="mt-4">No movies found.</x-empty-state>
            @else
                <x-media-grid class="mt-6">
                    @foreach ($movies as $movie)
                        <x-media-card :title="$movie['title']" :href="'https://www.themoviedb.org/movie/'.$movie['tmdb_id']"
                            :image="$movie['poster_path'] ? 'https://image.tmdb.org/t/p/w342'.$movie['poster_path'] : null"
                            :overview="$movie['overview'] ?? null" :rating="$movie['rating']" :friends="$circle['movie'][$movie['tmdb_id']] ?? []">
                            <x-slot:meta>{{ $movie['year'] }}</x-slot:meta>
                            <x-slot:action>
                                <livewire:library-actions :key="'movie-tmdb_id-'.$movie['tmdb_id']" type="movie" :tmdb-id="$movie['tmdb_id']" :label="$movie['title']" :following="$followedMovies->has($movie['tmdb_id'])" :favourite="$favouriteMovies->get($movie['tmdb_id'])" />
                            </x-slot:action>
                        </x-media-card>
                    @endforeach
                </x-media-grid>
            @endif
        </section>
    @endif
</x-layouts.app>
