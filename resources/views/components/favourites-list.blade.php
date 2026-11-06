{{-- Your favourite shows or movies in order, with up/down to reorder and the heart to remove one. --}}
@props(['items', 'type', 'heading'])

@php $kind = $type === 'movie' ? 'movies' : 'shows'; @endphp

<div {{ $attributes }}>
    <h3 class="flex items-baseline justify-between text-sm/6 font-medium text-white/70">
        {{ $heading }}
        <span class="text-xs/5 font-normal text-white/35 tabular-nums">{{ $items->count() }} of {{ \App\Services\Library::MAX_FAVOURITES }}</span>
    </h3>
    @if ($items->isEmpty())
        <p class="mt-2 rounded-lg px-3 py-3 text-sm/6 text-white/40 inset-ring inset-ring-white/5">
            None yet. Tap the heart on a {{ $type }} in Discover or Search.
        </p>
    @else
        <ol role="list" class="mt-2 divide-y divide-white/5">
            @foreach ($items as $item)
                @php $title = $type === 'movie' ? $item->title : $item->name; @endphp
                <li class="flex items-center gap-3 py-2">
                    <span class="w-4 text-right text-xs/5 text-white/35 tabular-nums">{{ $loop->iteration }}</span>
                    <x-poster :src="$type === 'movie' ? $item->posterUrl() : $item->image_url" alt="" class="aspect-2/3 w-8 rounded" />
                    <p class="min-w-0 flex-1 truncate text-base/6 font-medium sm:text-sm/5">{{ $title }}</p>
                    <div class="flex items-center">
                        @foreach (['up' => ! $loop->first, 'down' => ! $loop->last] as $direction => $enabled)
                            <form method="post" action="{{ route('favourites.'.$kind.'.move', $item) }}">
                                @csrf
                                <input type="hidden" name="direction" value="{{ $direction }}">
                                <button type="submit" @disabled(! $enabled) aria-label="Move {{ $title }} {{ $direction }}" title="Move {{ $direction }}"
                                    class="grid size-8 place-items-center rounded-lg text-white/45 hover:bg-white/5 hover:text-white disabled:pointer-events-none disabled:opacity-25">
                                    <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" @class(['size-4', 'rotate-180' => $direction === 'up'])><path fill-rule="evenodd" d="M4.22 6.22a.75.75 0 0 1 1.06 0L8 8.94l2.72-2.72a.75.75 0 1 1 1.06 1.06l-3.25 3.25a.75.75 0 0 1-1.06 0L4.22 7.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg>
                                </button>
                            </form>
                        @endforeach
                        <x-favourite-button :label="$title" :remove-action="route('favourites.'.$kind.'.destroy', $item)" />
                    </div>
                </li>
            @endforeach
        </ol>
    @endif
</div>
