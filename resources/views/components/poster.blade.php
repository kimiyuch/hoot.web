@props(['src' => null, 'alt' => ''])

<div {{ $attributes->class('shrink-0 overflow-hidden bg-night-800') }}>
    @if ($src)
        <img src="{{ $src }}" alt="{{ $alt }}" loading="lazy" class="size-full object-cover outline-1 -outline-offset-1 outline-white/10">
    @else
        <div class="flex size-full items-center justify-center text-white/15 outline-1 -outline-offset-1 outline-white/10">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-1/3" aria-hidden="true"><rect x="2.5" y="4.5" width="19" height="15" rx="2"/><path d="m10 9.5 4.5 2.5-4.5 2.5v-5Z" fill="currentColor"/></svg>
        </div>
    @endif
</div>
