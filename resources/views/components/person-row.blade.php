{{-- A person in a list: avatar, name, handle and bio (and why they're listed), linking to their profile, with actions on the right. --}}
@props(['person', 'meta' => null])

<li {{ $attributes->class('relative flex items-center gap-4 py-4') }}>
    <x-avatar :user="$person" />
    <div class="min-w-0 flex-1">
        <p class="truncate text-base/6 font-medium sm:text-sm/5">
            <a href="{{ $person->profileUrl() }}" class="before:absolute before:inset-0">{{ $person->name }}</a>
        </p>
        <p class="truncate text-sm/5 text-white/45">{{ '@'.$person->username }}@if ($person->bio) <span aria-hidden="true">·</span> {{ $person->bio }}@endif</p>
        @if ($meta)
            <p class="mt-0.5 truncate text-sm/5 text-white/60">{{ $meta }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="relative shrink-0">{{ $actions }}</div>
    @endisset
</li>
