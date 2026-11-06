@php
    $dayLabel = function (\Carbon\CarbonImmutable $date) use ($today) {
        $diff = (int) $today->diffInDays($date->startOfDay());
        return match (true) {
            $diff === 0 => 'Today',
            $diff === 1 => 'Tomorrow',
            $diff < 7 => $date->format('l'),
            default => $date->format('D j M'),
        };
    };
@endphp

<div>
    @if ($days->isEmpty())
        <x-empty-state class="mt-8">Nothing comes out in the next {{ config('hoot.days_ahead') }} days for what you follow.</x-empty-state>
    @else
        <div class="mt-12 flex flex-col gap-10">
            @foreach ($days as $day => $releases)
                @php $date = \Carbon\CarbonImmutable::parse($day, $timezone); @endphp
                <section wire:key="day-{{ $day }}" aria-label="{{ $date->format('l j F') }}">
                    {{-- A small label above the line, then that day's releases. --}}
                    <h2 class="flex items-baseline gap-2 border-b border-white/8 pb-2 text-sm/6">
                        <span class="font-semibold text-white">{{ $dayLabel($date) }}</span>
                        @if ($dayLabel($date) !== $date->format('D j M'))
                            <span class="text-white/40">{{ $date->format('D j M') }}</span>
                        @endif
                    </h2>
                    <ul role="list" class="mt-2">
                        @foreach ($releases as $release)
                            <x-release-row :release="$release" :logos="$logos" />
                        @endforeach
                    </ul>
                </section>
            @endforeach
        </div>

        @if ($nextMonth)
            <div class="mt-10 flex items-center gap-4">
                <button type="button" wire:click="loadMore" wire:loading.attr="disabled"
                    class="rounded-lg bg-white/8 px-3.5 py-2 text-base/6 font-medium text-white hover:bg-white/12 disabled:opacity-50 sm:text-sm/6">
                    <span wire:loading.remove wire:target="loadMore">Load {{ $nextMonth->format($nextMonth->year === $today->year ? 'F' : 'F Y') }}</span>
                    <span wire:loading wire:target="loadMore">Loading…</span>
                </button>
                <p class="text-sm/6 text-white/40">{{ trans_choice(':count more release later|:count more releases later', $laterCount) }}</p>
            </div>
        @endif
    @endif
</div>
