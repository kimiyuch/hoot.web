@php
    $heart = 'm9.653 16.915-.005-.003-.019-.01a20.759 20.759 0 0 1-1.162-.682 22.045 22.045 0 0 1-2.582-1.9C4.045 12.733 2 10.352 2 7.5a4.5 4.5 0 0 1 8-2.828A4.5 4.5 0 0 1 18 7.5c0 2.852-2.044 5.233-3.885 6.82a22.049 22.049 0 0 1-3.744 2.582l-.019.01-.005.003h-.002a.739.739 0 0 1-.69.001l-.002-.001Z';
    $heartTitle = $favourite ? "Remove {$label} from favourites" : "Add {$label} to favourites";
    $starPath = 'M8.7 1.95a.78.78 0 0 0-1.4 0L5.84 4.91l-3.27.48a.78.78 0 0 0-.43 1.33l2.36 2.31-.56 3.25a.78.78 0 0 0 1.13.82L8 11.57l2.93 1.53a.78.78 0 0 0 1.13-.82l-.56-3.25 2.36-2.31a.78.78 0 0 0-.43-1.33l-3.27-.48L8.7 1.95Z';
    $touch = '<span class="absolute top-1/2 left-1/2 size-[max(100%,3rem)] -translate-1/2 pointer-fine:hidden" aria-hidden="true"></span>';
@endphp

<div class="flex items-center gap-1">
    <button type="button" wire:click="toggleFavourite" wire:loading.attr="disabled" wire:target="toggleFavourite"
        title="{{ $heartTitle }}" aria-label="{{ $heartTitle }}" aria-pressed="{{ $favourite ? 'true' : 'false' }}"
        @class(['relative grid size-8 place-items-center rounded-lg hover:bg-white/5 disabled:opacity-50', 'text-calendar-400' => $favourite, 'text-white/40 hover:text-white' => ! $favourite])>
        <svg viewBox="0 0 20 20" aria-hidden="true" class="size-4.5" @if ($favourite) fill="currentColor" @else fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" @endif><path d="{{ $heart }}"/></svg>
        {!! $touch !!}
    </button>

    @if ($withStatus)
        {{-- Watch status and rating: a small menu that opens upwards (Alpine, which comes with Livewire). --}}
        <div class="relative" x-data="{ open: false, hover: 0 }" x-on:click.outside="open = false" x-on:keydown.escape.window="open = false">
            <button type="button" x-on:click="open = ! open" x-bind:aria-expanded="open" aria-haspopup="true"
                title="{{ $status || $rating ? 'Change your status or rating' : 'Set a status or rating' }}"
                aria-label="{{ collect([$status ? \App\Services\Library::STATUSES[$status] : null, $rating ? 'rated '.\App\Services\Library::stars($rating) : null])->filter()->join(', ') ?: 'Status and rating' }} for {{ $label }}"
                @class(['relative flex h-8 items-center gap-1.5 rounded-lg px-2 text-sm/6 hover:bg-white/5', 'text-white' => $status || $rating, 'text-white/40 hover:text-white' => ! $status && ! $rating])>
                @if ($status || $rating)
                    @if ($status)
                        <span class="font-medium">{{ \App\Services\Library::STATUSES[$status] }}</span>
                    @endif
                    @if ($rating)
                        <span class="text-star tabular-nums">{{ \App\Services\Library::stars($rating) }}</span>
                    @endif
                @else
                    <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="size-4.5"><path d="M10 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z"/><path fill-rule="evenodd" d="M.664 10.59a1.651 1.651 0 0 1 0-1.186A10.004 10.004 0 0 1 10 3c4.257 0 7.893 2.66 9.336 6.41.147.381.146.804 0 1.186A10.004 10.004 0 0 1 10 17c-4.257 0-7.893-2.66-9.336-6.41ZM14 10a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z" clip-rule="evenodd"/></svg>
                @endif
                {!! $touch !!}
            </button>

            <div x-show="open" x-transition.opacity.duration.100ms style="display: none"
                class="absolute right-0 bottom-full z-30 mb-2 w-56 rounded-xl bg-night-800 p-1 text-left shadow-2xl outline-1 outline-white/10">
                <p class="px-3 pt-1.5 pb-1 text-xs/5 font-medium text-white/40">Status</p>
                @foreach ($statuses as $key => $statusLabel)
                    <button type="button" wire:click="setStatus('{{ $status === $key ? '' : $key }}')" x-on:click="open = false"
                        @class(['flex w-full items-center justify-between rounded-lg px-3 py-1.5 text-sm/6 hover:bg-white/5', 'font-medium text-white' => $status === $key, 'text-white/70' => $status !== $key])>
                        {{ $statusLabel }}
                        @if ($status === $key)
                            <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" class="size-4 text-midnight-300"><path fill-rule="evenodd" d="M12.4 4.2a.75.75 0 0 1 .15 1.05l-5 6.5a.75.75 0 0 1-1.13.08l-3-3a.75.75 0 1 1 1.06-1.06l2.4 2.4 4.47-5.81a.75.75 0 0 1 1.05-.16Z" clip-rule="evenodd"/></svg>
                        @endif
                    </button>
                @endforeach

                <div class="mt-1 border-t border-white/8 px-3 pt-2 pb-1.5">
                    <div class="flex items-center justify-between">
                        <p class="text-xs/5 font-medium text-white/40">Your rating</p>
                        @if ($rating)
                            <button type="button" wire:click="rate(null)" x-on:click="open = false" class="text-xs/5 text-white/45 hover:text-white">Clear</button>
                        @endif
                    </div>
                    {{-- Five stars, each with a left (½) and a right (whole) half to click. --}}
                    <div class="mt-1 flex" x-on:mouseleave="hover = 0" role="group" aria-label="Rate {{ $label }}">
                        @for ($star = 1; $star <= 5; $star++)
                            @php [$half, $whole] = [$star * 2 - 1, $star * 2]; @endphp
                            <span class="relative size-8 text-white/20">
                                <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" class="absolute inset-1"><path d="{{ $starPath }}"/></svg>
                                <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" class="absolute inset-1 text-star"
                                    x-bind:style="(hover || {{ $rating ?? 0 }}) >= {{ $whole }} ? '' : ((hover || {{ $rating ?? 0 }}) >= {{ $half }} ? 'clip-path: inset(0 50% 0 0)' : 'clip-path: inset(0 100% 0 0)')"
                                    @style(['clip-path: inset(0 100% 0 0)' => ($rating ?? 0) < $half, 'clip-path: inset(0 50% 0 0)' => ($rating ?? 0) === $half])><path d="{{ $starPath }}"/></svg>
                                <button type="button" wire:click="rate({{ $half }})" x-on:click="open = false" x-on:mouseenter="hover = {{ $half }}" aria-label="{{ \App\Services\Library::stars($half) ?: '½' }}"
                                    class="absolute inset-y-0 left-0 w-1/2"></button>
                                <button type="button" wire:click="rate({{ $whole }})" x-on:click="open = false" x-on:mouseenter="hover = {{ $whole }}" aria-label="{{ \App\Services\Library::stars($whole) }}"
                                    class="absolute inset-y-0 right-0 w-1/2"></button>
                            </span>
                        @endfor
                    </div>
                </div>
            </div>
        </div>
    @endif

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
