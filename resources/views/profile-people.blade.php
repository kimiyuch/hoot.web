@php
    $titles = ['followers' => 'Followers', 'following' => 'Following'];
@endphp

<x-layouts.app :title="$titles[$list].' · '.$user->name.' (@'.$user->username.')'">
    <div class="mx-auto max-w-2xl">
        <a href="{{ $user->profileUrl() }}" class="group flex items-center gap-3">
            <x-avatar :user="$user" />
            <div class="min-w-0">
                <p class="truncate font-display text-lg/6 font-semibold tracking-tight group-hover:underline">{{ $user->name }}</p>
                <p class="truncate text-sm/5 text-white/45">{{ '@'.$user->username }}</p>
            </div>
        </a>

        <nav aria-label="People" class="mt-8 flex gap-1 border-b border-white/8">
            @foreach ($titles as $key => $title)
                <a href="{{ route('profile.'.$key, $user) }}" @if ($key === $list) aria-current="page" @endif
                    @class(['-mb-px border-b-2 px-3 py-2 text-sm/6 font-medium', 'border-white text-white' => $key === $list, 'border-transparent text-white/50 hover:text-white' => $key !== $list])>
                    {{ $title }} <span class="text-white/35 tabular-nums">{{ $key === 'followers' ? $followerCount : $followingCount }}</span>
                </a>
            @endforeach
        </nav>

        @if (! $visible)
            <x-empty-state class="mt-8">{{ $user->name }}’s profile is private.</x-empty-state>
        @elseif ($people->isEmpty() && $people->onFirstPage())
            <x-empty-state class="mt-8">
                @if ($list === 'followers')
                    {{ $own ? 'Nobody follows you yet.' : 'Nobody follows '.$user->name.' yet.' }}
                @else
                    {{ $own ? 'You don’t follow anyone yet.' : $user->name.' doesn’t follow anyone yet.' }}
                @endif
                @if ($own)
                    <a href="{{ route('discover', ['type' => 'people']) }}" class="font-medium text-white/70 hover:text-white">Find people <span aria-hidden="true">→</span></a>
                @endif
            </x-empty-state>
        @else
            <ul role="list" class="divide-y divide-white/5">
                @foreach ($people as $person)
                    <x-person-row :person="$person">
                        <x-slot:actions>
                            <livewire:person-actions :key="'person-'.$person->id" :user-id="$person->id" :context="$own && $list === 'followers' ? 'followers' : 'list'" />
                        </x-slot:actions>
                    </x-person-row>
                @endforeach
            </ul>

            @if ($people->hasPages())
                <nav aria-label="Pages" class="mt-6 flex justify-between text-sm/6 font-medium">
                    @if ($people->previousPageUrl())
                        <a href="{{ $people->previousPageUrl() }}" class="rounded-lg px-3 py-1.5 text-white/60 hover:bg-white/5 hover:text-white"><span aria-hidden="true">←</span> Previous</a>
                    @else
                        <span></span>
                    @endif
                    @if ($people->nextPageUrl())
                        <a href="{{ $people->nextPageUrl() }}" class="rounded-lg px-3 py-1.5 text-white/60 hover:bg-white/5 hover:text-white">Next <span aria-hidden="true">→</span></a>
                    @endif
                </nav>
            @endif
        @endif
    </div>
</x-layouts.app>
