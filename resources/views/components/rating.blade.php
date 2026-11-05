@props(['value'])

@if ($value)
    <span {{ $attributes->class('inline-flex items-center gap-1 text-star tabular-nums') }} title="Rated {{ number_format($value, 1) }} out of 10">
        <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" class="size-3.5"><path d="M8.7 1.95a.78.78 0 0 0-1.4 0L5.84 4.91l-3.27.48a.78.78 0 0 0-.43 1.33l2.36 2.31-.56 3.25a.78.78 0 0 0 1.13.82L8 11.57l2.93 1.53a.78.78 0 0 0 1.13-.82l-.56-3.25 2.36-2.31a.78.78 0 0 0-.43-1.33l-3.27-.48L8.7 1.95Z"/></svg>
        <span class="sr-only">Rated</span>{{ number_format($value, 1) }}
    </span>
@endif
