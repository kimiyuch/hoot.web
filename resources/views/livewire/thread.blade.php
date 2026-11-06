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

        return $item['url']
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
        <p class="mt-2 text-base/7 text-pretty text-white/50 sm:text-sm/6">Nothing yet. When people you follow follow, start, finish or rate something, it shows up here.</p>
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
                    $posters = collect($items)->where('kind', '!=', 'user')->take(3);
                @endphp
                @continue($what === null)
                <li wire:key="activity-{{ $entry->id }}" class="relative flex items-start gap-3 py-3">
                    <a href="{{ $entry->user->profileUrl() }}" class="shrink-0" aria-hidden="true" tabindex="-1"><x-avatar :user="$entry->user" size="sm" /></a>
                    <div class="min-w-0 flex-1">
                        <p class="text-base/6 text-pretty text-white/60 sm:text-sm/5">
                            <a href="{{ $entry->user->profileUrl() }}" class="font-semibold text-white hover:text-midnight-200">{{ $entry->user->name }}</a>
                            {!! $what !!}@if (isset($first['rating']))<span class="text-white/40"> ·</span> <span class="whitespace-nowrap text-star" title="{{ $first['rating'] / 2 }} of 5 stars">{{ \App\Services\Library::stars($first['rating']) }}</span>@endif
                        </p>
                        <p class="mt-0.5 text-sm/5 text-white/40"><time datetime="{{ $entry->updated_at->toIso8601String() }}">{{ $entry->updated_at->shortRelativeDiffForHumans() }}</time></p>
                    </div>
                    @if ($posters->isNotEmpty())
                        <div class="flex shrink-0 -space-x-4">
                            @foreach ($posters as $item)
                                <x-poster :src="$item['poster']" alt="" class="aspect-2/3 w-8 rounded ring-2 ring-night-950" />
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
