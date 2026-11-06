{{--
    A panel with the poster (and the rating under it) on the left and the details on the right.
    Slots: "meta" (a line under the title), the default slot (extra lines), "action" (follow button).
--}}
@props(['title', 'href' => null, 'image' => null, 'overview' => null, 'rating' => null])

<li class="group relative flex gap-5 rounded-xl bg-night-900/80 p-3 inset-ring inset-ring-white/5 transition-colors hover:bg-night-800/80 sm:gap-6">
    {{-- The poster, with the rating from TVmaze or TMDB under it. --}}
    <div class="flex w-28 shrink-0 flex-col gap-2 self-start sm:w-32">
        <x-poster :src="$image" alt="" class="aspect-2/3 w-full rounded-lg" />
        <x-rating :value="$rating" class="pl-0.5 text-sm/6" />
    </div>

    <div class="flex min-w-0 flex-1 flex-col py-1 pr-1">
        <h3 class="line-clamp-2 font-display text-lg/6 font-semibold tracking-tight text-balance">
            <a href="{{ $href }}" target="_blank" rel="noopener" class="before:absolute before:inset-0 before:rounded-xl">{{ $title }}</a>
        </h3>
        @isset($meta)
            <div class="mt-1 text-base/6 text-white/50 sm:text-sm/6">{{ $meta }}</div>
        @endisset
        @if ($overview)
            <p class="mt-3 line-clamp-3 text-base/6 text-pretty text-white/60 sm:text-sm/6">{{ $overview }}</p>
        @endif
        {{ $slot }}
        @isset($action)
            <div class="mt-auto flex justify-end pt-4">
                <div class="relative z-10 min-w-0">{{ $action }}</div>
            </div>
        @endisset
    </div>
</li>
