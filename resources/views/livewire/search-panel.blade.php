<div>
    <form action="{{ route('search') }}" method="get" role="search" class="grid grid-cols-1 border-b border-white/8">
        <input type="search" name="q" data-search-input wire:model.live.debounce.250ms="query" autocomplete="off" aria-label="Find a show or movie" placeholder="Find a show or movie"
            class="col-start-1 row-start-1 bg-transparent py-4 pr-16 pl-12 text-base text-white placeholder:text-white/35 focus:outline-none [&::-webkit-search-cancel-button]:hidden">
        <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="pointer-events-none col-start-1 row-start-1 ml-4 size-5 self-center text-white/35"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.45 4.39l3.08 3.08a.75.75 0 1 1-1.06 1.06l-3.08-3.08A7 7 0 0 1 2 9Z" clip-rule="evenodd"/></svg>
        <button type="button" data-dialog-close class="col-start-1 row-start-1 mr-3 self-center justify-self-end rounded-md px-1.5 py-0.5 text-xs/5 font-medium text-white/45 inset-ring inset-ring-white/10 hover:text-white">Esc</button>
    </form>

    <div aria-live="polite" wire:loading.attr="aria-busy" wire:target="query" class="scrollbar-thin max-h-[60vh] overflow-y-auto overscroll-contain p-2">
        @foreach ($searchErrors as $error)
            <p class="px-3 py-2 text-sm/6 text-calendar-400">{{ $error }}</p>
        @endforeach

        @if ($groups === null)
            <p class="px-3 py-6 text-center text-sm/6 text-white/40">Search shows on TVmaze and movies on TMDB.</p>
        @elseif (empty($groups))
            <p class="px-3 py-6 text-center text-sm/6 text-white/45">Nothing found for “{{ $term }}”.</p>
        @else
            @foreach ($groups as $label => $items)
                <section wire:key="group-{{ $label }}" aria-label="{{ $label }}" class="py-2 first:pt-0">
                    <h3 class="px-3 pb-1 text-xs/6 font-medium text-white/40">{{ $label }}</h3>
                    <ul role="list">
                        @foreach ($items as $item)
                            <li wire:key="row-{{ $item['key'] }}" class="group relative flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-white/5">
                                <x-poster :src="$item['image']" alt="" class="aspect-2/3 w-8 rounded" />
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm/5 font-medium">
                                        <a href="{{ $item['href'] }}" target="_blank" rel="noopener" class="before:absolute before:inset-0">{{ $item['title'] }}</a>
                                    </p>
                                    @if ($item['meta'])
                                        <p class="truncate text-sm/5 text-white/45">{{ $item['meta'] }}</p>
                                    @endif
                                </div>
                                <div class="relative shrink-0">
                                    <livewire:library-actions :key="'search-'.$item['key']" :type="$item['type']" :tvmaze-id="$item['tvmaze_id']" :tmdb-id="$item['tmdb_id']"
                                        :label="$item['title']" :following="$item['following']" :favourite="$item['favourite']" />
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach

            <a href="{{ route('search', ['q' => $term]) }}" class="mt-1 flex items-center justify-center rounded-lg px-3 py-2 text-sm/6 font-medium text-white/55 hover:bg-white/5 hover:text-white">
                Show all results <span aria-hidden="true" class="ml-1">→</span>
            </a>
        @endif
    </div>
</div>
