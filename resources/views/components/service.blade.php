{{-- A streaming service (a key from config/possum.php) with its logo, or a plain channel name. --}}
@props(['service' => null, 'channel' => null, 'logos' => [], 'logoOnly' => false])

@php
    $name = $channel ?? \App\Services\StreamingServices::name($service);
    $logo = $service ? ($logos[$service] ?? null) : null;
@endphp

@if ($name)
    @if ($logoOnly && $logo)
        <img src="{{ $logo }}" alt="{{ $name }}" title="{{ $name }}" {{ $attributes->class('size-5 rounded-md outline-1 -outline-offset-1 outline-white/10') }}>
    @else
        <span {{ $attributes->class('inline-flex min-w-0 items-center gap-1.5') }}>
            @if ($logo)
                <img src="{{ $logo }}" alt="" class="size-4 shrink-0 rounded">
            @endif
            <span class="truncate">{{ $name }}</span>
        </span>
    @endif
@endif
