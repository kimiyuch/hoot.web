{{-- The Discover filters: a sidebar on large screens and a drawer on small ones. $id keeps the ids unique. --}}
@props(['filters', 'serviceCounts' => null, 'logos' => [], 'id' => 'filters'])

@php
    $any = $filters['when'] === 'any';
    $chevron = '<svg viewBox="0 0 8 5" width="8" height="5" fill="none" aria-hidden="true" class="pointer-events-none col-start-2 row-start-1 place-self-center text-white/50"><path d="M.5.5 4 4 7.5.5" stroke="currentcolor"/></svg>';
    $selects = [
        'sort' => ['Sort by', \App\Services\Browse::SORTS, $filters['sort']],
        'from' => ['Released since', collect(range(now()->year, 1970))->mapWithKeys(fn ($y) => [$y => $y])->all(), $filters['from']],
        'min_rating' => ['Rating', \App\Services\Browse::MIN_RATINGS, $filters['min_rating']],
        'region' => ['Streaming in', config('possum.regions'), $filters['region']],
    ];
    $whens = [
        'soon' => 'Premiering soon',
        'new' => 'Just premiered',
        'any' => 'Any time',
    ];
    // On premieres, only the services that have something; on "any time", all of them.
    $services = $any ? array_keys(\App\Services\StreamingServices::all()) : $serviceCounts?->keys()->all() ?? [];
    // The big ones first; the rest (and never the selected one) behind "Show more".
    $featured = array_values(array_intersect(config('possum.featured_services'), $services));
    $more = array_values(array_diff($services, $featured, [$filters['service']]));
    if ($filters['service'] && ! in_array($filters['service'], $featured, true)) {
        $featured[] = $filters['service'];
    }
    $linkClass = fn (bool $active) => $active ? 'bg-white/8 text-white' : 'text-white/60 hover:bg-white/5 hover:text-white';
    $label = 'text-base/6 font-medium text-white/80 sm:text-sm/6';
@endphp

<div {{ $attributes->class('flex flex-col gap-8') }}>
    <nav aria-labelledby="{{ $id }}-what">
        <h3 id="{{ $id }}-what" class="{{ $label }}">What</h3>
        <ul role="list" class="mt-2 flex flex-col gap-0.5">
            @foreach (['tv' => 'Shows', 'movie' => 'Movies'] as $type => $typeLabel)
                <li>
                    <a href="{{ request()->fullUrlWithQuery(['type' => $type, 'service' => null, 'page' => null]) }}" class="flex items-center rounded-lg px-3 py-1.5 text-base/6 sm:text-sm/6 {{ $linkClass($filters['type'] === $type) }}" @if ($filters['type'] === $type) aria-current="page" @endif>
                        {{ $typeLabel }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    <nav aria-labelledby="{{ $id }}-when">
        <h3 id="{{ $id }}-when" class="{{ $label }}">When</h3>
        <ul role="list" class="mt-2 flex flex-col gap-0.5">
            @foreach ($whens as $when => $whenLabel)
                <li>
                    <a href="{{ request()->fullUrlWithQuery(['when' => $when, 'service' => null, 'page' => null]) }}" class="flex items-center rounded-lg px-3 py-1.5 text-base/6 sm:text-sm/6 {{ $linkClass($filters['when'] === $when) }}" @if ($filters['when'] === $when) aria-current="page" @endif>
                        {{ $whenLabel }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    @if ($any)
        <form method="get" action="{{ route('discover') }}" class="flex flex-col gap-5">
            <input type="hidden" name="when" value="any">
            <input type="hidden" name="type" value="{{ $filters['type'] }}">
            @if ($filters['service'])
                <input type="hidden" name="service" value="{{ $filters['service'] }}">
            @endif

            @foreach ($selects as $name => [$selectLabel, $options, $value])
                <div>
                    <label for="{{ $id }}-{{ $name }}" class="{{ $label }}">{{ $selectLabel }}</label>
                    <div class="mt-2 grid grid-cols-[1fr_--spacing(8)]">
                        <select id="{{ $id }}-{{ $name }}" name="{{ $name }}" data-autosubmit
                            class="col-span-full row-start-1 appearance-none rounded-lg bg-white/5 py-2 pr-8 pl-3 text-base/6 text-white inset-ring inset-ring-white/10 hover:bg-white/8 focus:outline-2 focus:-outline-offset-1 focus:outline-midnight-400 sm:py-1.5 sm:text-sm/6">
                            @foreach ($options as $optionValue => $optionLabel)
                                <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
                            @endforeach
                        </select>
                        {!! $chevron !!}
                    </div>
                </div>
            @endforeach

            <noscript><button type="submit" class="rounded-lg bg-white px-3 py-1.5 text-sm/6 font-semibold text-night-950">Apply</button></noscript>
        </form>
    @endif

    @if ($services)
        <nav aria-labelledby="{{ $id }}-services">
            <h3 id="{{ $id }}-services" class="{{ $label }}">Service</h3>
            <ul role="list" class="mt-2 flex flex-col gap-0.5">
                <li>
                    <a href="{{ request()->fullUrlWithQuery(['service' => null, 'page' => null]) }}" class="flex items-center gap-3 rounded-lg px-2 py-1.5 text-base/6 sm:text-sm/6 {{ $linkClass($filters['service'] === null) }}">
                        <span class="flex size-6 items-center justify-center rounded-md bg-white/8 text-white/60">
                            <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" class="size-3.5"><path d="M2 3.5A1.5 1.5 0 0 1 3.5 2h2A1.5 1.5 0 0 1 7 3.5v2A1.5 1.5 0 0 1 5.5 7h-2A1.5 1.5 0 0 1 2 5.5v-2ZM9 3.5A1.5 1.5 0 0 1 10.5 2h2A1.5 1.5 0 0 1 14 3.5v2A1.5 1.5 0 0 1 12.5 7h-2A1.5 1.5 0 0 1 9 5.5v-2ZM2 10.5A1.5 1.5 0 0 1 3.5 9h2A1.5 1.5 0 0 1 7 10.5v2A1.5 1.5 0 0 1 5.5 14h-2A1.5 1.5 0 0 1 2 12.5v-2ZM9 10.5A1.5 1.5 0 0 1 10.5 9h2a1.5 1.5 0 0 1 1.5 1.5v2a1.5 1.5 0 0 1-1.5 1.5h-2A1.5 1.5 0 0 1 9 12.5v-2Z"/></svg>
                        </span>
                        All services
                    </a>
                </li>
                @foreach ($featured as $key)
                    <li><x-service-link :service="$key" :active="$filters['service'] === $key" :count="$any ? null : $serviceCounts[$key]" :logos="$logos" /></li>
                @endforeach
            </ul>

            @if ($more)
                <details class="group mt-1">
                    <summary class="flex cursor-pointer list-none items-center gap-1.5 rounded-lg px-2 py-1.5 text-base/6 font-medium text-white/50 hover:bg-white/5 hover:text-white sm:text-sm/6 [&::-webkit-details-marker]:hidden">
                        <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" class="size-4 transition-transform group-open:rotate-180"><path fill-rule="evenodd" d="M4.22 6.22a.75.75 0 0 1 1.06 0L8 8.94l2.72-2.72a.75.75 0 1 1 1.06 1.06l-3.25 3.25a.75.75 0 0 1-1.06 0L4.22 7.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg>
                        <span class="group-open:hidden">Show {{ count($more) }} more</span>
                        <span class="hidden group-open:inline">Show less</span>
                    </summary>
                    <ul role="list" class="scrollbar-thin mt-1 flex max-h-48 flex-col gap-0.5 overflow-y-auto overscroll-contain rounded-lg bg-white/3 p-1 inset-ring inset-ring-white/5">
                        @foreach ($more as $key)
                            <li><x-service-link :service="$key" :active="false" :count="$any ? null : $serviceCounts[$key]" :logos="$logos" /></li>
                        @endforeach
                    </ul>
                </details>
            @endif
        </nav>
    @endif
</div>
