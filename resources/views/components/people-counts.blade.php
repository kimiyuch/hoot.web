{{-- "12 followers · 3 following" on a profile, linking to the lists when you may see them. --}}
@props(['user', 'followers', 'following', 'links' => true])

@php
    $items = [
        ['count' => $followers, 'label' => trans_choice('follower|followers', $followers), 'href' => route('profile.followers', $user)],
        ['count' => $following, 'label' => 'following', 'href' => route('profile.following', $user)],
    ];
@endphp

<p {{ $attributes->class('flex flex-wrap items-center gap-x-2 text-base/6 text-white/50 sm:text-sm/6') }}>
    @foreach ($items as $item)
        @if (! $loop->first)
            <span aria-hidden="true">·</span>
        @endif
        @if ($links)
            <a href="{{ $item['href'] }}" class="hover:text-white"><span class="font-semibold text-white tabular-nums">{{ $item['count'] }}</span> {{ $item['label'] }}</a>
        @else
            <span><span class="font-semibold text-white tabular-nums">{{ $item['count'] }}</span> {{ $item['label'] }}</span>
        @endif
    @endforeach
</p>
