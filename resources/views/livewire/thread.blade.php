{{-- What the people you follow did: who, what, when, and the posters of what it's about. --}}
@php
    $viewer = auth()->user();

    // A title as a link: shows and movies to TVmaze / TMDB, people to their profile ("you" for yourself).
    $link = function (array $item) use ($viewer): string {
        if ($item['kind'] === 'user') {
            return $item['id'] === $viewer->id
                ? 'you'
                : '<a href="'.e(route('profile', $item['username'])).'" class="relative font-medium text-white hover:text-midnight-200">'.e($item['title']).'</a>';
        }

        return ($item['url'] ?? null)
            ? '<a href="'.e($item['url']).'" target="_blank" rel="noopener" class="relative font-medium text-white hover:text-midnight-200">'.e($item['title']).'</a>'
            : '<span class="font-medium text-white">'.e($item['title']).'</span>';
    };

    // "A", "A and B", "A, B and C", "A, B and 3 more".
    $list = function (array $items) use ($link): string {
        $names = array_map($link, array_slice($items, 0, count($items) > 3 ? 2 : 3));
        $rest = count($items) - count($names);

        if ($rest > 0) {
            return implode(', ', $names).' and '.$rest.' more';
        }

        return count($names) > 1 ? implode(', ', array_slice($names, 0, -1)).' and '.end($names) : $names[0];
    };

@endphp

<div>
    @if ($entries->isEmpty())
        @unless ($profile)
            <p class="mt-2 text-base/7 text-pretty text-white/50 sm:text-sm/6">Nothing yet. When people you follow follow, start, finish or rate something, it shows up here.</p>
        @endunless
    @else
        <ul role="list" class="mt-2 divide-y divide-white/5">
            @foreach ($entries as $entry)
                @php
                    $items = $entry->items();
                    $first = $items[0];
                    $what = match ($entry->type) {
                        'followed' => 'followed '.$list($items),
                        'favourited' => 'added '.$list($items).' to their favourites',
                        'started' => 'started watching '.$link($first),
                        'finished' => ($first['kind'] === 'movie' ? 'watched ' : 'finished ').$link($first),
                        'rated' => 'rated '.$link($first),
                        'followed_user' => 'followed '.$list($items),
                        default => null,
                    };
                    $titles = collect($items)->where('kind', '!=', 'user')->values();
                    $posters = $titles->take(3);
                @endphp
                @continue($what === null)
                <li wire:key="activity-{{ $entry->id }}" class="relative flex items-start gap-3 py-3">
                    @unless ($profile)
                        <a href="{{ $entry->user->profileUrl() }}" class="shrink-0" aria-hidden="true" tabindex="-1"><x-avatar :user="$entry->user" size="sm" /></a>
                    @endunless
                    <div class="min-w-0 flex-1">
                        <p class="text-base/6 text-pretty text-white/60 sm:text-sm/5">
                            <a href="{{ $entry->user->profileUrl() }}" class="font-semibold text-white hover:text-midnight-200">{{ $entry->user->name }}</a>
                            {!! $what !!}@if (isset($first['rating']))<span class="text-white/40"> ·</span> <span class="whitespace-nowrap text-star" title="{{ $first['rating'] / 2 }} of 5 stars">{{ \App\Services\Library::stars($first['rating']) }}</span>@endif
                        </p>
                        <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1">
                            <time datetime="{{ $entry->updated_at->toIso8601String() }}" class="text-sm/5 text-white/40">{{ $entry->updated_at->shortRelativeDiffForHumans() }}</time>
                            @if ($titles->count() === 1)
                                @php $only = $titles[0]; @endphp
                                <livewire:library-actions :key="'thread-'.$entry->id.'-'.$only['kind'].'-'.$only['id']" :type="$only['kind']" :tvmaze-id="$only['kind'] === 'show' ? $only['id'] : null" :tmdb-id="$only['kind'] === 'movie' ? $only['id'] : null" :label="$only['title']" :following="$followed[$only['kind']]->has($only['id'])" :with-heart="false" :with-status="false" small />
                            @endif
                        </div>
                        @if ($titles->count() > 1)
                            {{-- Several shows or movies: each with its own Follow, folded away until asked for. --}}
                            <details class="group/all mt-1" wire:ignore.self>
                                <summary class="inline-flex cursor-pointer list-none items-center gap-1 rounded-lg py-1 text-sm/6 font-medium text-white/60 hover:text-white [&::-webkit-details-marker]:hidden">
                                    <span class="group-open/all:hidden">Show all {{ $titles->count() }}</span>
                                    <span class="hidden group-open/all:inline">Show fewer</span>
                                    <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" class="size-4 transition-transform group-open/all:rotate-180"><path fill-rule="evenodd" d="M4.22 6.22a.75.75 0 0 1 1.06 0L8 8.94l2.72-2.72a.75.75 0 1 1 1.06 1.06l-3.25 3.25a.75.75 0 0 1-1.06 0L4.22 7.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg>
                                </summary>
                                <ul role="list" class="mt-1 divide-y divide-white/5">
                                    @foreach ($titles as $item)
                                        <li class="flex items-center gap-3 py-2">
                                            <x-poster :src="$item['poster'] ?? null" alt="" class="aspect-2/3 w-7 rounded" />
                                            <p class="min-w-0 flex-1 truncate text-sm/5 font-medium">
                                                @if ($item['url'] ?? null)
                                                    <a href="{{ $item['url'] }}" target="_blank" rel="noopener" class="hover:text-midnight-200">{{ $item['title'] }}</a>
                                                @else
                                                    {{ $item['title'] }}
                                                @endif
                                            </p>
                                            <livewire:library-actions :key="'thread-'.$entry->id.'-'.$item['kind'].'-'.$item['id']" :type="$item['kind']" :tvmaze-id="$item['kind'] === 'show' ? $item['id'] : null" :tmdb-id="$item['kind'] === 'movie' ? $item['id'] : null" :label="$item['title']" :following="$followed[$item['kind']]->has($item['id'])" :with-heart="false" :with-status="false" small />
                                        </li>
                                    @endforeach
                                </ul>
                            </details>
                        @endif
                    </div>
                    @if ($posters->isNotEmpty())
                        <div class="flex shrink-0 -space-x-4">
                            @foreach ($posters as $item)
                                <x-poster :src="$item['poster'] ?? null" alt="" class="aspect-2/3 w-8 rounded ring-2 ring-night-950" />
                            @endforeach
                        </div>
                    @endif
                </li>
            @endforeach
        </ul>

        @if ($more)
            <button type="button" wire:click="loadMore" wire:loading.attr="disabled"
                class="mt-3 rounded-lg bg-white/8 px-3 py-1.5 text-sm/6 font-medium text-white hover:bg-white/12 disabled:opacity-50">
                <span wire:loading.remove wire:target="loadMore">Load more</span>
                <span wire:loading wire:target="loadMore">Loading…</span>
            </button>
        @endif
    @endif
</div>
