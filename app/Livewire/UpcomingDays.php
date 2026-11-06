<?php

namespace App\Livewire;

use App\Services\StreamingServices;
use App\Services\Timeline;
use App\Timezone;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;

/**
 * For you's timeline by day: this month first (or the first month with anything in it), then a month more
 * with every "Load more". "Show earlier" goes back a month at a time, to catch up on what's already out.
 */
class UpcomingDays extends Component
{
    /** Show days up to the end of this month (Y-m); empty means the first month with releases. */
    public string $until = '';

    /** Show past days from the start of this month (Y-m); empty means none, the timeline starts today. */
    public string $since = '';

    public function loadMore(Timeline $timeline): void
    {
        $days = $this->days($timeline);
        $cutoff = $this->cutoff($days);
        $later = $days->keys()->first(fn (string $day) => $day > $cutoff);

        if ($later) {
            $this->until = substr($later, 0, 7);
        }
    }

    public function loadEarlier(Timeline $timeline): void
    {
        $earlier = $this->earlierMonth($this->pastDays($timeline));

        if ($earlier) {
            $this->since = $earlier->format('Y-m');
        }
    }

    public function render(Timeline $timeline, StreamingServices $services): View
    {
        $timezone = Timezone::for(request());
        $days = $this->days($timeline);
        $cutoff = $this->cutoff($days);
        $later = $days->filter(fn ($releases, string $day) => $day > $cutoff);
        $past = $this->pastDays($timeline);
        $start = $this->start();

        return view('livewire.upcoming-days', [
            'past' => $start ? $past->filter(fn ($releases, string $day) => $day >= $start) : collect(),
            'days' => $days->filter(fn ($releases, string $day) => $day <= $cutoff),
            'earlierMonth' => $this->earlierMonth($past),
            'nextMonth' => $later->isEmpty() ? null : CarbonImmutable::parse($later->keys()->first(), $timezone),
            'laterCount' => $later->flatten(1)->count(),
            'today' => CarbonImmutable::now($timezone)->startOfDay(),
            'timezone' => $timezone,
            'logos' => $services->logos(),
        ]);
    }

    /** Every day from today on with releases, Y-m-d => releases. */
    private function days(Timeline $timeline): Collection
    {
        return once(fn () => collect($timeline->sections(auth()->user(), Timezone::for(request())))
            ->except('Just released')
            ->flatMap(fn (array $days) => $days));
    }

    /** Every day before today with releases, as far back as the history goes, oldest first: Y-m-d => releases. */
    private function pastDays(Timeline $timeline): Collection
    {
        return once(function () use ($timeline) {
            $timezone = Timezone::for(request());
            $today = CarbonImmutable::now($timezone)->startOfDay();

            return $timeline->releases(auth()->user(), $today->subDays(config('hoot.days_history')), $today->subSecond(), $timezone)
                ->groupBy(fn (array $release) => $release['at']->toDateString());
        });
    }

    /** The first past day shown (Y-m-d), or null when the timeline starts today. */
    private function start(): ?string
    {
        return preg_match('/^\d{4}-\d{2}$/', $this->since) ? $this->since.'-01' : null;
    }

    /**
     * The month "Show earlier" would go back to: at first last month (with the rest of this one), after that
     * the latest month with releases before what's shown.
     */
    private function earlierMonth(Collection $past): ?CarbonImmutable
    {
        $start = $this->start();
        $day = $past->keys()->last(fn (string $day) => $start === null || $day < $start);

        if (! $day) {
            return null;
        }

        $month = CarbonImmutable::parse($day, Timezone::for(request()))->startOfMonth();
        $lastMonth = CarbonImmutable::now(Timezone::for(request()))->startOfMonth()->subMonth();

        return $start === null && $month > $lastMonth ? $lastMonth : $month;
    }

    /** The last day shown: the end of the chosen month, or of this month, or of the first month with anything. */
    private function cutoff($days): string
    {
        $timezone = Timezone::for(request());
        $month = preg_match('/^\d{4}-\d{2}$/', $this->until) ? $this->until : CarbonImmutable::now($timezone)->format('Y-m');
        $cutoff = CarbonImmutable::parse($month.'-01', $timezone)->endOfMonth()->toDateString();

        // Nothing this month: show the first month that has something.
        if ($this->until === '' && $days->isNotEmpty() && $days->keys()->first() > $cutoff) {
            $cutoff = CarbonImmutable::parse($days->keys()->first(), $timezone)->endOfMonth()->toDateString();
        }

        return $cutoff;
    }
}
