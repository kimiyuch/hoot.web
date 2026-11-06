@php $heart = 'm9.653 16.915-.005-.003-.019-.01a20.759 20.759 0 0 1-1.162-.682 22.045 22.045 0 0 1-2.582-1.9C4.045 12.733 2 10.352 2 7.5a4.5 4.5 0 0 1 8-2.828A4.5 4.5 0 0 1 18 7.5c0 2.852-2.044 5.233-3.885 6.82a22.049 22.049 0 0 1-3.744 2.582l-.019.01-.005.003h-.002a.739.739 0 0 1-.69.001l-.002-.001Z'; @endphp

<div>
    <h3 class="flex items-baseline justify-between text-sm/6 font-medium text-white/70">
        {{ $type === 'movie' ? 'Movies' : 'Shows' }}
        <span class="text-xs/5 font-normal text-white/35 tabular-nums">{{ $items->count() }} of {{ $max }}</span>
    </h3>
    @if ($items->isEmpty())
        <p class="mt-2 rounded-lg px-3 py-3 text-sm/6 text-white/40 inset-ring inset-ring-white/5">
            None yet. Tap the heart on a {{ $type }} in Discover or Search.
        </p>
    @else
        <ol role="list" class="mt-2 divide-y divide-white/5" wire:loading.class="opacity-60">
            @foreach ($items as $item)
                @php $title = $type === 'movie' ? $item->title : $item->name; @endphp
                <li wire:key="favourite-{{ $item->id }}" class="flex items-center gap-3 py-2">
                    <span class="w-4 text-right text-xs/5 text-white/35 tabular-nums">{{ $loop->iteration }}</span>
                    <x-poster :src="$type === 'movie' ? $item->posterUrl() : $item->image_url" alt="" class="aspect-2/3 w-8 rounded" />
                    <p class="min-w-0 flex-1 truncate text-base/6 font-medium sm:text-sm/5">{{ $title }}</p>
                    <div class="flex items-center">
                        @foreach (['up' => ! $loop->first, 'down' => ! $loop->last] as $direction => $enabled)
                            <button type="button" wire:click="move({{ $item->id }}, '{{ $direction }}')" @disabled(! $enabled)
                                aria-label="Move {{ $title }} {{ $direction }}" title="Move {{ $direction }}"
                                class="grid size-8 place-items-center rounded-lg text-white/45 hover:bg-white/5 hover:text-white disabled:pointer-events-none disabled:opacity-25">
                                <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" @class(['size-4', 'rotate-180' => $direction === 'up'])><path fill-rule="evenodd" d="M4.22 6.22a.75.75 0 0 1 1.06 0L8 8.94l2.72-2.72a.75.75 0 1 1 1.06 1.06l-3.25 3.25a.75.75 0 0 1-1.06 0L4.22 7.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg>
                            </button>
                        @endforeach
                        <button type="button" wire:click="remove({{ $item->id }})" aria-label="Remove {{ $title }} from favourites" title="Remove from favourites"
                            class="grid size-8 place-items-center rounded-lg text-calendar-400 hover:bg-white/5">
                            <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="size-4.5"><path d="{{ $heart }}"/></svg>
                        </button>
                    </div>
                </li>
            @endforeach
        </ol>
    @endif
</div>
