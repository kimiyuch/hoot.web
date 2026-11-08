{{--
    "People you follow" on a card: their avatars and names, linked to their profiles, with what they make of it
    (App\Services\Circle). One person: "Kimi · Watching · ★★★★". More: "Kimi, Pip and 2 more", the rest in the tooltip.
--}}
@props(['people' => []])

@if ($people)
    @php
        $people = collect($people);
        $shown = $people->take($people->count() > 3 ? 2 : 3);
        $rest = $people->count() - $shown->count();
        $name = fn ($entry) => '<a href="'.e($entry['user']->profileUrl()).'" class="text-white/65 hover:text-white">'.e($entry['user']->name).'</a>';
        $names = $shown->map($name)->all();
        $text = match (true) {
            $rest > 0 => implode(', ', $names).' and '.$rest.' more',
            count($names) > 1 => implode(', ', array_slice($names, 0, -1)).' and '.end($names),
            default => $names[0].' <span class="text-white/35">· '.e($people[0]['label']).'</span>',
        };
        $tooltip = $people->map(fn ($entry) => $entry['user']->name.': '.$entry['label'])->join("\n");
    @endphp
    <div {{ $attributes->class('relative z-10 flex min-w-0 items-center gap-2') }} title="{{ $tooltip }}">
        <span class="flex shrink-0 -space-x-1.5">
            @foreach ($people->take(3) as $entry)
                <x-avatar :user="$entry['user']" size="xs" class="ring-2 ring-night-900" />
            @endforeach
        </span>
        <p class="min-w-0 truncate text-sm/6 text-white/40"><span class="sr-only">People you follow: </span>{!! $text !!}</p>
    </div>
@endif
