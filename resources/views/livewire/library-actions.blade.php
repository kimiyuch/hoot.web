@php
    $heart = 'm9.653 16.915-.005-.003-.019-.01a20.759 20.759 0 0 1-1.162-.682 22.045 22.045 0 0 1-2.582-1.9C4.045 12.733 2 10.352 2 7.5a4.5 4.5 0 0 1 8-2.828A4.5 4.5 0 0 1 18 7.5c0 2.852-2.044 5.233-3.885 6.82a22.049 22.049 0 0 1-3.744 2.582l-.019.01-.005.003h-.002a.739.739 0 0 1-.69.001l-.002-.001Z';
    $heartTitle = $favourite ? "Remove {$label} from favourites" : "Add {$label} to favourites";
    $touch = '<span class="absolute top-1/2 left-1/2 size-[max(100%,3rem)] -translate-1/2 pointer-fine:hidden" aria-hidden="true"></span>';
@endphp

<div class="flex items-center gap-1">
    <button type="button" wire:click="toggleFavourite" wire:loading.attr="disabled" wire:target="toggleFavourite"
        title="{{ $heartTitle }}" aria-label="{{ $heartTitle }}" aria-pressed="{{ $favourite ? 'true' : 'false' }}"
        @class(['relative grid size-8 place-items-center rounded-lg hover:bg-white/5 disabled:opacity-50', 'text-calendar-400' => $favourite, 'text-white/40 hover:text-white' => ! $favourite])>
        <svg viewBox="0 0 20 20" aria-hidden="true" class="size-4.5" @if ($favourite) fill="currentColor" @else fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" @endif><path d="{{ $heart }}"/></svg>
        {!! $touch !!}
    </button>

    @if ($withFollow)
        @if ($following)
            <span class="flex items-center gap-1.5 py-1 pl-1 text-sm/6 text-midnight-300">
                <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" class="size-4"><path fill-rule="evenodd" d="M12.4 4.2a.75.75 0 0 1 .15 1.05l-5 6.5a.75.75 0 0 1-1.13.08l-3-3a.75.75 0 1 1 1.06-1.06l2.4 2.4 4.47-5.81a.75.75 0 0 1 1.05-.16Z" clip-rule="evenodd"/></svg>
                Following
            </span>
        @else
            <button type="button" wire:click="follow" wire:loading.attr="disabled" wire:target="follow" title="Follow {{ $label }}"
                class="relative flex items-center gap-1 rounded-lg bg-white/10 py-1 pr-2.5 pl-1.5 text-sm/6 font-medium text-white hover:bg-white/15 disabled:opacity-50">
                <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" class="size-4"><path d="M8.75 3.75a.75.75 0 0 0-1.5 0v3.5h-3.5a.75.75 0 0 0 0 1.5h3.5v3.5a.75.75 0 0 0 1.5 0v-3.5h3.5a.75.75 0 0 0 0-1.5h-3.5v-3.5Z"/></svg>
                Follow{!! $touch !!}
            </button>
        @endif
    @endif
</div>
