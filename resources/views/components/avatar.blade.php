{{-- A person's initial on a colour picked from their id, so it stays the same everywhere. No uploads yet. --}}
@props(['user', 'size' => 'md'])

@php
    $colours = ['bg-midnight-600', 'bg-calendar-700', 'bg-teal-700', 'bg-amber-700', 'bg-sky-700', 'bg-indigo-600', 'bg-rose-700', 'bg-emerald-700'];
    $sizes = [
        'xs' => 'size-5 text-[0.625rem]',
        'sm' => 'size-8 text-sm',
        'md' => 'size-9 text-sm',
        'xl' => 'size-20 text-3xl sm:size-24 sm:text-4xl',
    ];
@endphp

<span {{ $attributes->class([
    'flex shrink-0 items-center justify-center rounded-full font-display font-semibold text-white outline-1 -outline-offset-1 outline-white/15 select-none',
    $colours[$user->id % count($colours)],
    $sizes[$size],
]) }} aria-hidden="true">{{ $user->initial() }}</span>
