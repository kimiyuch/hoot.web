<x-layouts.app title="Notifications">
    <div class="mx-auto max-w-2xl">
        <h1 class="font-display text-3xl font-semibold tracking-tight">Notifications</h1>
        <p class="mt-1 text-sm/6 text-white/40">Who followed you, asked to, or said yes. What’s new is marked with a dot.</p>

        @if ($notifications->isEmpty())
            <x-empty-state class="mt-10">Nothing yet. When someone follows you or answers your request, you’ll see it here.</x-empty-state>
        @else
            <ul role="list" class="mt-8 divide-y divide-white/5">
                @foreach ($notifications as $item)
                    @php
                        $person = $item['person'];
                        $what = match (true) {
                            $item['kind'] === 'followed' && $item['invited'] => 'joined Hoot through your invite and follows you',
                            $item['kind'] === 'followed' => 'started following you',
                            $item['kind'] === 'requested' => 'asked to follow you',
                            $item['kind'] === 'accepted' => 'accepted your follow request',
                            default => null,
                        };
                    @endphp
                    @continue($what === null)
                    <li class="relative flex items-center gap-4 py-4">
                        <span class="relative shrink-0">
                            <x-avatar :user="$person" />
                            @if ($item['new'])
                                <span class="absolute -top-0.5 -right-0.5 size-2.5 rounded-full bg-calendar-400 ring-2 ring-night-950"><span class="sr-only">New</span></span>
                            @endif
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-base/6 text-pretty text-white/60 sm:text-sm/5">
                                <a href="{{ $person->profileUrl() }}" class="font-semibold text-white before:absolute before:inset-0 hover:text-midnight-200">{{ $person->name }}</a>
                                {{ $what }}
                            </p>
                            <p class="mt-0.5 text-sm/5 text-white/40">
                                {{ '@'.$person->username }} <span aria-hidden="true">·</span>
                                <time datetime="{{ $item['at']->toIso8601String() }}">{{ $item['at']->shortRelativeDiffForHumans() }}</time>
                            </p>
                        </div>
                        @if ($item['kind'] === 'requested' && $item['pending'])
                            <div class="relative shrink-0"><livewire:person-actions :key="'notification-'.$item['id']" :user-id="$person->id" context="requests" /></div>
                        @elseif ($item['kind'] === 'followed')
                            <div class="relative shrink-0"><livewire:person-actions :key="'notification-'.$item['id']" :user-id="$person->id" /></div>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-layouts.app>
