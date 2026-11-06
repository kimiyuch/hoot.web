<x-layouts.app :title="$user->name.' (@'.$user->username.')'">
    @php
        // Their rating, if they gave one, under the title.
        $stars = fn ($item) => $item->pivot?->rating ? \App\Services\Library::stars($item->pivot->rating) : null;
        $showTile = fn ($show) => ['title' => $show->name, 'image' => $show->image_url, 'href' => $show->url, 'meta' => $stars($show)];
        $movieTile = fn ($movie) => ['title' => $movie->title, 'image' => $movie->posterUrl(), 'href' => $movie->url(), 'meta' => $stars($movie)];
        $anyTile = fn ($item) => $item instanceof \App\Models\Movie ? $movieTile($item) : $showTile($item);
        $watchingTiles = $watching->map($showTile);
        $watchedTiles = $watched->map($anyTile);
        $plannedTiles = $planned->map($anyTile);
        $favourites = [
            'Favourite shows' => $favouriteShows->map($showTile),
            'Favourite movies' => $favouriteMovies->map($movieTile),
        ];
        $following = [
            'Shows' => $shows->map($showTile),
            'Movies' => $movies->map($movieTile),
        ];
        $counts = collect([
            trans_choice(':count show|:count shows', $shows->count()),
            trans_choice(':count movie|:count movies', $movies->count()),
        ])->join(' · ').' followed';
    @endphp

    <div class="mx-auto max-w-6xl">
        <header class="flex flex-col gap-6 sm:flex-row sm:items-center">
            <x-avatar :user="$user" size="xl" />
            <div class="min-w-0 flex-1">
                <h1 class="font-display text-3xl font-semibold tracking-tight text-balance">{{ $user->name }}</h1>
                <p class="mt-1 flex flex-wrap items-center gap-x-2 text-base/6 text-white/50">
                    <span>{{ '@'.$user->username }}</span>
                    @if ($user->is_private)
                        <span class="flex items-center gap-1 rounded-full bg-white/8 px-2 text-xs/5 text-white/60">
                            <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" class="size-3"><path fill-rule="evenodd" d="M8 1a3.5 3.5 0 0 0-3.5 3.5V7A1.5 1.5 0 0 0 3 8.5v5A1.5 1.5 0 0 0 4.5 15h7a1.5 1.5 0 0 0 1.5-1.5v-5A1.5 1.5 0 0 0 11.5 7V4.5A3.5 3.5 0 0 0 8 1Zm2 6V4.5a2 2 0 1 0-4 0V7h4Z" clip-rule="evenodd"/></svg>
                            Private
                        </span>
                    @endif
                    @if ($followsYou)
                        <span class="rounded-full bg-white/8 px-2 text-xs/5 text-white/60">Follows you</span>
                    @endif
                    @if ($visible)
                        <span aria-hidden="true">·</span>
                        <span>{{ $counts }}</span>
                    @endif
                </p>
                @if ($user->bio && ! $blockedByYou)
                    <p class="mt-3 max-w-[60ch] text-base/7 text-pretty text-white/75">{{ $user->bio }}</p>
                @endif
                @unless ($blockedByYou)
                    <x-people-counts :user="$user" :followers="$followerCount" :following="$followingCount" :links="$visible" class="mt-3" />
                @endunless
            </div>
            @if ($own)
                <a href="{{ route('settings') }}#profile" class="self-start rounded-lg bg-white/8 px-3.5 py-2 text-base/6 font-medium hover:bg-white/12 sm:self-center sm:text-sm/6">Edit profile</a>
            @else
                <div class="self-start sm:self-center">
                    <livewire:person-actions :user-id="$user->id" context="profile" />
                </div>
            @endif
        </header>

        @if ($blockedByYou)
            <x-empty-state class="mt-16">You blocked {{ '@'.$user->username }}. You don’t see each other on Hoot, and neither of you can follow the other.</x-empty-state>
        @elseif (! $visible)
            <x-empty-state class="mt-16">{{ $user->name }}’s profile is private. Following private accounts comes later.</x-empty-state>
        @else
            {{-- Favourites: the first four big, the rest smaller. --}}
            @foreach ($favourites as $heading => $tiles)
                @continue($tiles->isEmpty() && ! $own)
                <section class="mt-16" aria-label="{{ $heading }}">
                    <h2 class="font-display text-xl font-semibold tracking-tight">{{ $heading }}</h2>
                    @if ($tiles->isEmpty())
                        <x-empty-state class="mt-4">None yet. Tap the heart on a {{ str_contains($heading, 'shows') ? 'show' : 'movie' }} in Discover or Search to put it here.</x-empty-state>
                    @else
                        <ol role="list" class="mt-6 grid grid-cols-2 gap-x-4 gap-y-6 sm:grid-cols-4 sm:gap-x-6">
                            @foreach ($tiles->take(4) as $tile)
                                <x-poster-tile big :title="$tile['title']" :image="$tile['image']" :href="$tile['href']" :meta="$tile['meta']" />
                            @endforeach
                        </ol>
                        @if ($tiles->count() > 4)
                            <ol role="list" start="5" class="mt-8 grid grid-cols-3 gap-x-4 gap-y-6 sm:grid-cols-6">
                                @foreach ($tiles->skip(4) as $tile)
                                    <x-poster-tile :title="$tile['title']" :image="$tile['image']" :href="$tile['href']" :meta="$tile['meta']" />
                                @endforeach
                            </ol>
                        @endif
                    @endif
                </section>
            @endforeach

            {{-- Where they are: watching now, and what they finished lately. --}}
            @foreach (['Watching now' => $watchingTiles, 'Watched lately' => $watchedTiles, 'Want to watch' => $plannedTiles] as $heading => $tiles)
                @continue($tiles->isEmpty())
                <section class="mt-16" aria-label="{{ $heading }}">
                    <h2 class="font-display text-xl font-semibold tracking-tight">{{ $heading }}</h2>
                    <ul role="list" class="mt-6 grid grid-cols-3 gap-x-4 gap-y-6 sm:grid-cols-5 lg:grid-cols-8">
                        @foreach ($tiles as $tile)
                            <x-poster-tile :title="$tile['title']" :image="$tile['image']" :href="$tile['href']" :meta="$tile['meta']" />
                        @endforeach
                    </ul>
                </section>
            @endforeach

            {{-- What they follow. --}}
            <section class="mt-16 border-t border-white/5 pt-12" aria-labelledby="following">
                <h2 id="following" class="font-display text-xl font-semibold tracking-tight">Following</h2>
                @if ($shows->isEmpty() && $movies->isEmpty())
                    <x-empty-state class="mt-4">{{ $own ? 'You don’t follow anything yet.' : $user->name.' doesn’t follow anything yet.' }}</x-empty-state>
                @endif
                @foreach ($following as $heading => $tiles)
                    @continue($tiles->isEmpty())
                    <h3 class="mt-8 text-sm/6 font-medium text-white/60">{{ $heading }} <span class="text-white/35 tabular-nums">{{ $tiles->count() }}</span></h3>
                    <ul role="list" class="mt-4 grid grid-cols-3 gap-x-4 gap-y-6 sm:grid-cols-5 lg:grid-cols-8">
                        @foreach ($tiles as $tile)
                            <x-poster-tile :title="$tile['title']" :image="$tile['image']" :href="$tile['href']" :meta="$tile['meta']" />
                        @endforeach
                    </ul>
                @endforeach
            </section>
        @endif
    </div>
</x-layouts.app>
