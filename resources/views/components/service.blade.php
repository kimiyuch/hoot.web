{{-- A streaming service (a key from config/hoot.php) with its logo, or a plain channel name. With href, a link out (↗). --}}
@props(['service' => null, 'channel' => null, 'logos' => [], 'logoOnly' => false, 'href' => null])

@php
    $name = $channel ?? \App\Services\StreamingServices::name($service);
    $logo = $service ? ($logos[$service] ?? null) : null;
@endphp

@if ($name)
    @if ($logoOnly && $logo)
        @if ($href)
            <a href="{{ $href }}" target="_blank" rel="noopener" title="Watch on {{ $name }}" {{ $attributes->class('relative z-10 rounded-md hover:opacity-80') }}>
                <img src="{{ $logo }}" alt="{{ $name }}" class="size-5 rounded-md outline-1 -outline-offset-1 outline-white/10">
            </a>
        @else
            <img src="{{ $logo }}" alt="{{ $name }}" title="{{ $name }}" {{ $attributes->class('size-5 rounded-md outline-1 -outline-offset-1 outline-white/10') }}>
        @endif
    @else
        @if ($href)
            {{-- relative z-10: above a row's stretched link. --}}
            <a href="{{ $href }}" target="_blank" rel="noopener" title="Watch on {{ $name }}" {{ $attributes->class('group/service relative z-10 inline-flex min-w-0 items-center gap-1.5 hover:text-white') }}>
        @else
            <span {{ $attributes->class('inline-flex min-w-0 items-center gap-1.5') }}>
        @endif
            @if ($logo)
                <img src="{{ $logo }}" alt="" class="size-4 shrink-0 rounded">
            @endif
            <span class="truncate">{{ $name }}</span>
            @if ($href)
                <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" class="size-3.5 shrink-0 opacity-60 group-hover/service:opacity-100"><path fill-rule="evenodd" d="M4.22 11.78a.75.75 0 0 1 0-1.06L9.44 5.5H5.75a.75.75 0 0 1 0-1.5h5.5a.75.75 0 0 1 .75.75v5.5a.75.75 0 0 1-1.5 0V6.56l-5.22 5.22a.75.75 0 0 1-1.06 0Z" clip-rule="evenodd"/></svg>
            @endif
        @if ($href)
            </a>
        @else
            </span>
        @endif
    @endif
@endif
