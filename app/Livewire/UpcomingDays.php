<?php

namespace App\Livewire;

use App\Services\StreamingServices;
use App\Services\Timeline;
use App\Timezone;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * For you's timeline by day: this month first (or the first month with anything in it), then a month more
 * with every "Load more".
 */
class UpcomingDays extends Component
{
    /** Show days up to the end of this month (Y-m); empty means the first month with releases. */
    public string $until = '';

    public function loadMore(Timeline $timeline): void
    {
        $days = $this->days($timeline);
        $cutoff = $this->cutoff($days);
        $later = $days->keys()->first(fn (string $day) => $day > $cutoff);

        if ($later) {
            $this->until = substr($later, 0, 7);
        }
    }

    public function render(Timeline $timeline, StreamingServices $services): View
    {
        $timezone = Timezone::for(request());
        $days = $this->days($timeline);
        $cutoff = $this->cutoff($days);
        $later = $days->filter(fn ($releases, string $day) => $day > $cutoff);

        return view('livewire.upcoming-days', [
            'days' => $days->filter(fn ($releases, string $day) => $day <= $cutoff),
            'nextMonth' => $later->isEmpty() ? null : CarbonImmutable::parse($later->keys()->first(), $timezone),
            'laterCount' => $later->flatten(1)->count(),
            'today' => CarbonImmutable::now($timezone)->startOfDay(),
            'timezone' => $timezone,
            'logos' => $services->logos(),
        ]);
    }

    /** Every day from today on with releases, Y-m-d => releases. */
    private function days(Timeline $timeline)
    {
        return once(fn () => collect($timeline->sections(auth()->user(), Timezone::for(request())))
            ->except('Just released')
            ->flatMap(fn (array $days) => $days));
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
