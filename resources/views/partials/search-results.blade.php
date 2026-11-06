{{-- Search results inside the search lightbox, fetched as you type. --}}
@php
    $groups = [
        'Shows' => collect($shows)->take(6)->map(fn ($show) => [
            'title' => $show['name'], 'href' => $show['url'], 'image' => $show['image_url'],
            'meta' => collect([$show['year'], $show['channel'], $show['status'] === 'Ended' ? 'Ended' : null])->filter()->join(' · '),
            'type' => 'show', 'fields' => ['tvmaze_id' => $show['tvmaze_id']], 'following' => $followedShows->has($show['tvmaze_id']), 'favourite' => $favouriteShows->get($show['tvmaze_id']),
        ]),
        'Movies' => collect($movies)->take(6)->map(fn ($movie) => [
            'title' => $movie['title'], 'href' => 'https://www.themoviedb.org/movie/'.$movie['tmdb_id'],
            'image' => $movie['poster_path'] ? 'https://image.tmdb.org/t/p/w92'.$movie['poster_path'] : null,
            'meta' => $movie['year'],
            'type' => 'movie', 'fields' => ['tmdb_id' => $movie['tmdb_id']], 'following' => $followedMovies->has($movie['tmdb_id']), 'favourite' => $favouriteMovies->get($movie['tmdb_id']),
        ]),
    ];
@endphp

@foreach ($searchErrors as $error)
    <p class="px-3 py-2 text-sm/6 text-calendar-400">{{ $error }}</p>
@endforeach

@if (collect($groups)->every(fn ($items) => $items->isEmpty()))
    <p class="px-3 py-6 text-center text-sm/6 text-white/45">Nothing found for “{{ $query }}”.</p>
@else
    @foreach ($groups as $label => $items)
        @continue($items->isEmpty())
        <section aria-label="{{ $label }}" class="py-2 first:pt-0">
            <h3 class="px-3 pb-1 text-xs/6 font-medium text-white/40">{{ $label }}</h3>
            <ul role="list">
                @foreach ($items as $item)
                    <li class="group relative flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-white/5">
                        <x-poster :src="$item['image']" alt="" class="aspect-2/3 w-8 rounded" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm/5 font-medium">
                                <a href="{{ $item['href'] }}" target="_blank" rel="noopener" class="before:absolute before:inset-0">{{ $item['title'] }}</a>
                            </p>
                            @if ($item['meta'])
                                <p class="truncate text-sm/5 text-white/45">{{ $item['meta'] }}</p>
                            @endif
                        </div>
                        <x-library-actions :type="$item['type']" :fields="$item['fields']" :label="$item['title']" :following="$item['following']" :favourite="$item['favourite']" class="relative shrink-0" />
                    </li>
                @endforeach
            </ul>
        </section>
    @endforeach

    <a href="{{ route('search', ['q' => $query]) }}" class="mt-1 flex items-center justify-center rounded-lg px-3 py-2 text-sm/6 font-medium text-white/55 hover:bg-white/5 hover:text-white">
        Show all results <span aria-hidden="true" class="ml-1">→</span>
    </a>
@endif
