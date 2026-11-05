{{--
    A panel with the poster on the left and the details on the right.
    Slots: "meta" (a line under the title), the default slot (extra lines), "action" (follow button).
--}}
@props(['title', 'href' => null, 'image' => null, 'overview' => null, 'rating' => null])

<li class="group relative flex gap-5 rounded-xl bg-night-900/80 p-3 inset-ring inset-ring-white/5 transition-colors hover:bg-night-800/80 sm:gap-6">
    <x-poster :src="$image" alt="" class="aspect-2/3 w-28 self-start rounded-lg sm:w-32" />

    <div class="flex min-w-0 flex-1 flex-col py-1 pr-1">
        <h3 class="line-clamp-2 font-display text-lg/6 font-semibold tracking-tight text-balance">
            <a href="{{ $href }}" class="before:absolute before:inset-0 before:rounded-xl">{{ $title }}</a>
        </h3>
        @isset($meta)
            <div class="mt-1 text-base/6 text-white/50 sm:text-sm/6">{{ $meta }}</div>
        @endisset
        @if ($overview)
            <p class="mt-3 line-clamp-3 text-base/6 text-pretty text-white/60 sm:text-sm/6">{{ $overview }}</p>
        @endif
        {{ $slot }}
        <div class="mt-auto flex items-center justify-between gap-3 pt-4">
            <x-rating :value="$rating" class="text-sm/6" />
            @isset($action)
                <div class="relative z-10 ml-auto">{{ $action }}</div>
            @endisset
        </div>
    </div>
</li>
