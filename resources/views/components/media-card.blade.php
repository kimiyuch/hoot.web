{{--
    A panel with the poster on the left and the details on the right, the rating next to the title.
    Slots: "meta" (a line under the title), the default slot (extra lines), "action" (follow button).
    $cast: the first few actors' names, shown as "With …" under the meta line.
    $friends: people you follow who have it in their library (App\Services\Circle), bottom left, across from the buttons.
--}}
@props(['title', 'href' => null, 'image' => null, 'overview' => null, 'rating' => null, 'cast' => [], 'friends' => []])

<li class="group relative flex gap-5 rounded-xl bg-night-900/80 p-3 inset-ring inset-ring-white/5 transition-colors hover:bg-night-800/80 sm:gap-6">
    <x-poster :src="$image" alt="" class="aspect-2/3 w-28 shrink-0 self-start rounded-lg sm:w-32" />

    <div class="flex min-w-0 flex-1 flex-col py-1 pr-1">
        {{-- The title, with the rating from TVmaze or TMDB on the right. --}}
        <div class="flex items-start gap-3">
            <h3 class="line-clamp-2 min-w-0 flex-1 font-display text-lg/6 font-semibold tracking-tight text-balance">
                <a href="{{ $href }}" target="_blank" rel="noopener" class="before:absolute before:inset-0 before:rounded-xl">{{ $title }}</a>
            </h3>
            <x-rating :value="$rating" class="shrink-0 text-sm/6" />
        </div>
        @isset($meta)
            <div class="mt-1 text-base/6 text-white/50 sm:text-sm/6">{{ $meta }}</div>
        @endisset
        @if ($cast)
            <p class="mt-1 line-clamp-1 text-base/6 text-white/50 sm:text-sm/6"><span class="text-white/35">With</span> {{ implode(', ', $cast) }}</p>
        @endif
        @if ($overview)
            <p class="mt-3 line-clamp-3 text-base/6 text-pretty text-white/60 sm:text-sm/6">{{ $overview }}</p>
        @endif
        {{ $slot }}
        {{-- The bottom row: people you follow who have it on the left, the buttons on the right. --}}
        @if ($friends || isset($action))
            <div class="mt-auto flex items-center gap-3 pt-4">
                <x-friends :people="$friends" class="min-w-0 flex-1" />
                @isset($action)
                    <div class="relative z-10 ml-auto min-w-0 shrink-0">{{ $action }}</div>
                @endisset
            </div>
        @endif
    </div>
</li>
