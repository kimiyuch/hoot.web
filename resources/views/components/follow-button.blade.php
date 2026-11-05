{{-- Follow (POST) or, with an unfollow action, unfollow (DELETE). A small pill for the cards. --}}
@props(['action' => null, 'fields' => [], 'following' => false, 'unfollowAction' => null, 'label' => null])

@php
    $touch = '<span class="absolute top-1/2 left-1/2 size-[max(100%,3rem)] -translate-1/2 pointer-fine:hidden" aria-hidden="true"></span>';
@endphp

@if ($following && ! $unfollowAction)
    <span {{ $attributes->class('flex items-center gap-1.5 py-1 text-sm/6 text-midnight-300') }}>
        <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" class="size-4"><path fill-rule="evenodd" d="M12.4 4.2a.75.75 0 0 1 .15 1.05l-5 6.5a.75.75 0 0 1-1.13.08l-3-3a.75.75 0 1 1 1.06-1.06l2.4 2.4 4.47-5.81a.75.75 0 0 1 1.05-.16Z" clip-rule="evenodd"/></svg>
        Following
    </span>
@else
    <form method="post" action="{{ $following ? $unfollowAction : $action }}" {{ $attributes }}>
        @csrf
        @if ($following)
            @method('delete')
            <button type="submit" class="relative rounded-lg px-2.5 py-1 text-sm/6 font-medium text-white/50 hover:bg-white/5 hover:text-white" title="Unfollow {{ $label }}">
                Unfollow{!! $touch !!}
            </button>
        @else
            @foreach ($fields as $name => $value)
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endforeach
            <button type="submit" class="relative flex items-center gap-1 rounded-lg bg-white/10 py-1 pr-2.5 pl-1.5 text-sm/6 font-medium text-white hover:bg-white/15" title="Follow {{ $label }}">
                <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" class="size-4"><path d="M8.75 3.75a.75.75 0 0 0-1.5 0v3.5h-3.5a.75.75 0 0 0 0 1.5h3.5v3.5a.75.75 0 0 0 1.5 0v-3.5h3.5a.75.75 0 0 0 0-1.5h-3.5v-3.5Z"/></svg>
                Follow{!! $touch !!}
            </button>
        @endif
    </form>
@endif
