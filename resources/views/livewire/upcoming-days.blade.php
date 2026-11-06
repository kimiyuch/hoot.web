@php
    $dayLabel = function (\Carbon\CarbonImmutable $date) use ($today) {
        $diff = (int) $today->diffInDays($date->startOfDay());
        return match (true) {
            $diff === 0 => 'Today',
            $diff === 1 => 'Tomorrow',
            $diff === -1 => 'Yesterday',
            $diff > 0 && $diff < 7 => $date->format('l'),
            default => $date->format('D j M'),
        };
    };
    $monthLabel = fn (\Carbon\CarbonImmutable $month) => $month->format($month->year === $today->year ? 'F' : 'F Y');
@endphp

<div>
    {{-- Going back: a month at a time, for catching up on what's already out. --}}
    @if ($earlierMonth)
        <p class="mt-12 text-sm/6 text-white/40">
            Missed something?
            <button type="button" wire:click="loadEarlier" wire:loading.attr="disabled"
                class="cursor-pointer font-medium text-white/60 underline decoration-white/20 underline-offset-4 hover:text-white hover:decoration-white/50 disabled:opacity-50">
                <span wire:loading.remove wire:target="loadEarlier">Show {{ $earlierMonth->isSameMonth($today->subMonth()) ? 'last month' : $monthLabel($earlierMonth) }}</span>
                <span wire:loading wire:target="loadEarlier">Loading…</span>
            </button>
        </p>
    @endif

    @if ($past->isNotEmpty())
        <div class="mt-10 flex flex-col gap-10">
            @foreach ($past as $day => $releases)
                @php $date = \Carbon\CarbonImmutable::parse($day, $timezone); @endphp
                <section wire:key="day-{{ $day }}" aria-label="{{ $date->format('l j F') }}">
                    <h2 class="flex items-baseline gap-2 border-b border-white/8 pb-2 text-sm/6">
                        <span class="font-semibold text-white/60">{{ $dayLabel($date) }}</span>
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
    @endif

    @if ($days->isEmpty())
        <x-empty-state @class(['mt-8' => ! $earlierMonth && $past->isEmpty(), 'mt-10' => $earlierMonth || $past->isNotEmpty()])>Nothing comes out in the next {{ config('hoot.days_ahead') }} days for what you follow.</x-empty-state>
    @else
        <div @class(['flex flex-col gap-10', 'mt-12' => ! $earlierMonth && $past->isEmpty(), 'mt-10' => $earlierMonth || $past->isNotEmpty()])>
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
                    <span wire:loading.remove wire:target="loadMore">Load {{ $monthLabel($nextMonth) }}</span>
                    <span wire:loading wire:target="loadMore">Loading…</span>
                </button>
                <p class="text-sm/6 text-white/40">{{ trans_choice(':count more release later|:count more releases later', $laterCount) }}</p>
            </div>
        @endif
    @endif
</div>
