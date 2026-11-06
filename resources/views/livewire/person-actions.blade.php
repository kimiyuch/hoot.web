@php
    $handle = '@'.$person->username;
    $quiet = 'rounded-lg bg-white/8 px-3 py-1.5 text-sm/6 font-medium text-white hover:bg-white/12 disabled:opacity-50';
    $touch = '<span class="absolute top-1/2 left-1/2 size-[max(100%,3rem)] -translate-1/2 pointer-fine:hidden" aria-hidden="true"></span>';
@endphp

<div class="flex items-center gap-2">
    @if ($self)
        {{-- Nothing to do with yourself. --}}
    @elseif ($context === 'blocked' || $blocked)
        @if ($blocked)
            <button type="button" wire:click="unblock" wire:loading.attr="disabled" class="relative {{ $quiet }}" aria-label="Unblock {{ $handle }}">Unblock{!! $touch !!}</button>
        @else
            <span class="py-1.5 text-sm/6 text-white/45">Unblocked</span>
        @endif
    @else
        @if ($context === 'followers')
            @if ($removed)
                <span class="py-1.5 text-sm/6 text-white/45">Removed</span>
            @else
                <button type="button" wire:click="removeFollower" wire:loading.attr="disabled" wire:confirm="Remove {{ $handle }} from your followers? They won’t be told, and they can follow you again."
                    class="relative rounded-lg px-3 py-1.5 text-sm/6 font-medium text-white/55 hover:bg-white/5 hover:text-white disabled:opacity-50" aria-label="Remove {{ $handle }} from your followers">Remove{!! $touch !!}</button>
            @endif
        @endif

        @if ($following)
            <button type="button" wire:click="unfollow" wire:loading.attr="disabled" aria-label="Unfollow {{ $handle }}" title="Unfollow {{ $handle }}"
                class="group/follow relative flex items-center gap-1.5 {{ $quiet }}">
                <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" class="size-4 text-midnight-300 group-hover/follow:hidden"><path fill-rule="evenodd" d="M12.4 4.2a.75.75 0 0 1 .15 1.05l-5 6.5a.75.75 0 0 1-1.13.08l-3-3a.75.75 0 1 1 1.06-1.06l2.4 2.4 4.47-5.81a.75.75 0 0 1 1.05-.16Z" clip-rule="evenodd"/></svg>
                <span class="group-hover/follow:hidden">Following</span>
                <span class="hidden group-hover/follow:inline">Unfollow</span>{!! $touch !!}
            </button>
        @elseif ($canFollow)
            <button type="button" wire:click="follow" wire:loading.attr="disabled" aria-label="Follow {{ $handle }}"
                class="relative flex items-center gap-1 rounded-lg bg-white py-1.5 pr-3 pl-2 text-sm/6 font-semibold text-night-950 hover:bg-midnight-100 disabled:opacity-50">
                <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" class="size-4"><path d="M8.75 3.75a.75.75 0 0 0-1.5 0v3.5h-3.5a.75.75 0 0 0 0 1.5h3.5v3.5a.75.75 0 0 0 1.5 0v-3.5h3.5a.75.75 0 0 0 0-1.5h-3.5v-3.5Z"/></svg>
                Follow{!! $touch !!}
            </button>
        @endif

        @if ($context === 'profile')
            <details class="relative" wire:ignore.self>
                <summary aria-label="More for {{ $handle }}" title="More"
                    class="grid size-9 cursor-pointer list-none place-items-center rounded-lg text-white/55 hover:bg-white/5 hover:text-white [&::-webkit-details-marker]:hidden">
                    <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" class="size-4"><path d="M2 8a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Zm4.5 0a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0ZM12.5 6.5a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3Z"/></svg>
                </summary>
                <div class="absolute right-0 z-10 mt-1 w-56 rounded-xl bg-night-900 p-1 shadow-xl outline-1 outline-white/10">
                    <button type="button" wire:click="block" wire:confirm="Block {{ $handle }}? You stop following each other, and neither of you sees the other on Hoot. They aren’t told."
                        class="w-full rounded-lg px-3 py-2 text-left text-sm/6 text-calendar-400 hover:bg-white/5">Block {{ $handle }}</button>
                </div>
            </details>
        @endif
    @endif
</div>
