{{-- Discover → People, inside DiscoverPage: a search by name, then suggestions, popular and new. --}}
@php
    $sectionTitles = [
        'suggested' => ['Follow what you follow', 'People with shows and movies in common with you.'],
        'popular' => ['Popular on Hoot', 'The most followed people.'],
        'new' => ['New on Hoot', 'Just joined.'],
    ];
    $meta = fn (string $section, $person) => match ($section) {
        'suggested' => trans_choice(':count in common|:count in common', $person->shared).': '.implode(', ', $person->sharedTitles).($person->shared > count($person->sharedTitles) ? '…' : ''),
        'popular' => trans_choice(':count follower|:count followers', $person->followers_count),
        'new' => 'Joined '.$person->created_at->diffForHumans(),
        default => null,
    };
    $list = 'mt-2 grid grid-cols-1 gap-x-10 xl:grid-cols-2 [&>li]:border-b [&>li]:border-white/5';
@endphp

<div class="mt-8">
    <div class="grid max-w-xl grid-cols-1">
        <input type="search" wire:model.live.debounce.250ms="q" aria-label="Find people by name or username" placeholder="Find people by name or @username" autocomplete="off" spellcheck="false"
            class="col-start-1 row-start-1 rounded-xl bg-white/5 py-2.5 pr-4 pl-11 text-base text-white inset-ring inset-ring-white/10 placeholder:text-white/35 focus:bg-white/8 focus:outline-2 focus:-outline-offset-1 focus:outline-midnight-400 sm:text-sm/6">
        <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="pointer-events-none col-start-1 row-start-1 ml-4 size-4.5 self-center text-white/35"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.45 4.39l3.08 3.08a.75.75 0 1 1-1.06 1.06l-3.08-3.08A7 7 0 0 1 2 9Z" clip-rule="evenodd"/></svg>
    </div>

    <div class="transition-opacity" wire:loading.class="opacity-50" wire:target="q, choose">
        @if ($results !== null)
            @if ($results->isEmpty())
                <x-empty-state class="mt-8">Nobody found for “{{ $filters['q'] }}”.</x-empty-state>
            @else
                <ul role="list" class="mt-6 {{ $list }}">
                    @foreach ($results as $person)
                        <x-person-row :person="$person" wire:key="result-{{ $person->id }}">
                            <x-slot:actions>
                                <livewire:person-actions :key="'discover-result-'.$person->id" :user-id="$person->id" />
                            </x-slot:actions>
                        </x-person-row>
                    @endforeach
                </ul>
            @endif
        @elseif (empty($sections))
            <x-empty-state class="mt-8">Nobody else is on Hoot yet. Invite someone: your profile is at {{ auth()->user()->profileUrl() }}.</x-empty-state>
        @else
            @foreach ($sections as $section => $people)
                <section wire:key="people-{{ $section }}" aria-labelledby="people-{{ $section }}" class="mt-12 first:mt-10">
                    <h2 id="people-{{ $section }}" class="font-display text-xl font-semibold tracking-tight">{{ $sectionTitles[$section][0] }}</h2>
                    <p class="mt-1 text-base/7 text-white/45 sm:text-sm/6">{{ $sectionTitles[$section][1] }}</p>
                    <ul role="list" class="{{ $list }}">
                        @foreach ($people as $person)
                            <x-person-row :person="$person" :meta="$meta($section, $person)" wire:key="{{ $section }}-{{ $person->id }}">
                                <x-slot:actions>
                                    <livewire:person-actions :key="'discover-'.$section.'-'.$person->id" :user-id="$person->id" />
                                </x-slot:actions>
                            </x-person-row>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        @endif
    </div>
</div>
