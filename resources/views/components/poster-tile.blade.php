{{-- A poster with the title under it, linking out to TVmaze or TMDB. For profiles. --}}
@props(['title', 'image' => null, 'href' => null, 'meta' => null, 'big' => false])

<li {{ $attributes->class('group relative min-w-0') }}>
    <x-poster :src="$image" alt="" @class(['aspect-2/3 w-full transition-transform group-hover:-translate-y-0.5', 'rounded-xl' => $big, 'rounded-lg' => ! $big]) />
    <p @class(['mt-2 line-clamp-2 font-medium text-pretty', 'text-base/6' => $big, 'text-sm/5' => ! $big])>
        <a href="{{ $href }}" target="_blank" rel="noopener" class="before:absolute before:inset-0">{{ $title }}</a>
    </p>
    @if ($meta)
        <p class="text-sm/5 text-star tabular-nums">{{ $meta }}</p>
    @endif
</li>
