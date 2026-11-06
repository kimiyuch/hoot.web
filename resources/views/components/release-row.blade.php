{{-- One release in the Upcoming timeline: episodes of a show, or a movie coming to cinemas or digital. --}}
@props(['release', 'logos' => []])

@php
    $isEpisodes = $release['kind'] === 'episodes';
    // Already out: timed releases once their moment has passed, dated ones (movies) from the next day.
    $out = $release['timed'] ? $release['at']->isPast() : $release['at']->lt(\Carbon\CarbonImmutable::now($release['at']->timezone)->startOfDay());
    if ($isEpisodes) {
        $show = $release['show'];
        $episodes = $release['episodes'];
        [$title, $href, $image] = [$show->name, $show->url, $show->image_url];
    } else {
        $movie = $release['movie'];
        [$title, $href, $image] = [$movie->title, $movie->url(), $movie->posterUrl()];
        $country = $movie->{$release['kind'].'_country'};
    }
@endphp

<li @class(['group relative flex items-center gap-4 py-2', 'opacity-50' => $out])>
    <x-poster :src="$image" alt="" class="aspect-2/3 w-11 rounded-md" />

    <div class="min-w-0 flex-1">
        <p class="truncate text-base/6 font-medium sm:text-sm/6">
            <a href="{{ $href }}" target="_blank" rel="noopener" class="before:absolute before:inset-0 group-hover:text-midnight-200">{{ $title }}</a>
        </p>
        <p class="truncate text-base/6 text-white/50 sm:text-sm/6">
            @if ($isEpisodes)
                <span class="text-white/75 tabular-nums">{{ \App\Services\Calendar::codes($episodes) }}</span>
                @if ($episodes->count() > 1)
                    · all {{ $episodes->count() }} episodes
                @elseif ($episodes->first()->name)
                    · {{ $episodes->first()->name }}
                @endif
            @else
                <span class="text-white/75">{{ $release['kind'] === 'digital' ? 'Digital release' : 'In cinemas' }}</span>@if ($country) · first in {{ $country }}@endif
            @endif
        </p>
    </div>

    <div class="flex shrink-0 flex-col items-end text-right">
        @if ($release['timed'])
            <div class="flex items-center gap-2">
                @if ($isEpisodes && $out && ($search = \App\Services\EpisodeSearch::template()))
                    <x-episode-search-link :template="$search" :show="$show->name" :episodes="$episodes" class="z-10" />
                @endif
                <time datetime="{{ $release['at']->toIso8601String() }}" class="text-base/6 font-medium tabular-nums sm:text-sm/6">{{ $release['at']->format('H:i') }}</time>
            </div>
        @endif
        @if ($isEpisodes && $show->channel)
            <x-service :service="$show->service()" :channel="$show->channel" :logos="$logos" :href="\App\Services\StreamingServices::link($show->service(), $show->official_url, $show->name)" class="max-w-36 text-sm/6 text-white/45 max-sm:hidden" />
        @elseif (! $isEpisodes && ! empty($movie->services))
            <div class="flex gap-1 max-sm:hidden">
                @foreach (array_slice($movie->services, 0, 3) as $service)
                    <x-service :service="$service" :logos="$logos" logo-only :href="\App\Services\StreamingServices::link($service, null, $movie->title)" />
                @endforeach
            </div>
        @endif
    </div>
</li>
