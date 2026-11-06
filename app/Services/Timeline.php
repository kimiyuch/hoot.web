<?php

namespace App\Services;

use App\Models\Episode;
use App\Models\Movie;
use App\Models\Show;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Everything that comes out for the shows and movies someone follows, as releases sorted by time.
 *
 * A release is one of:
 *  - kind "episodes": one or more episodes of a show that come out at the same moment (a whole season dropping at once is one release)
 *  - kind "cinema" / "digital": a movie's earliest release in cinemas or digitally, anywhere; these have a date but no time
 */
class Timeline
{
    /**
     * @return Collection<int, array{kind: string, at: CarbonImmutable, timed: bool, key: string, show?: Show, episodes?: Collection<int, Episode>, movie?: Movie}>
     */
    public function releases(User $user, CarbonImmutable $from, CarbonImmutable $until, string $timezone): Collection
    {
        $episodes = Episode::with('show')
            ->whereIn('show_id', $user->shows()->select('shows.id'))
            ->whereBetween('airs_at', [$from->utc(), $until->utc()])
            ->orderBy('airs_at')->orderBy('season')->orderBy('number')
            ->get()
            ->groupBy(fn (Episode $episode) => $episode->show_id.'@'.$episode->airs_at->getTimestamp())
            ->map(fn (Collection $drop) => [
                'kind' => 'episodes',
                'at' => $drop->first()->airs_at->setTimezone($timezone),
                'timed' => true,
                'key' => 'episode-'.$drop->first()->tvmaze_id,
                'show' => $drop->first()->show,
                'episodes' => $drop->values(),
            ]);

        $fromDate = $from->setTimezone($timezone)->toDateString();
        $untilDate = $until->setTimezone($timezone)->toDateString();
        $movies = collect();

        foreach ($user->movies as $movie) {
            foreach (['cinema', 'digital'] as $kind) {
                $date = $movie->{$kind.'_date'};
                if ($date && $date->toDateString() >= $fromDate && $date->toDateString() <= $untilDate) {
                    $movies->push([
                        'kind' => $kind,
                        // A date without a time: midnight where you are.
                        'at' => CarbonImmutable::parse($date->toDateString(), $timezone),
                        'timed' => false,
                        'key' => "movie-{$movie->tmdb_id}-{$kind}",
                        'movie' => $movie,
                    ]);
                }
            }
        }

        return $episodes->values()->concat($movies)
            ->sortBy([fn ($a, $b) => $a['at'] <=> $b['at'], fn ($a, $b) => $a['timed'] <=> $b['timed']])
            ->values();
    }

    /**
     * The releases from a week ago until a few months ahead, in sections ("Just released", "Today", "Tomorrow",
     * "This week", "Later"), and within those by day.
     *
     * @return array<string, array<string, Collection>> section title => [Y-m-d => releases]
     */
    public function sections(User $user, string $timezone): array
    {
        $now = CarbonImmutable::now($timezone);
        $today = $now->startOfDay();

        $releases = $this->releases(
            $user,
            $today->subDays(config('hoot.days_back')),
            $today->addDays(config('hoot.days_ahead'))->endOfDay(),
            $timezone,
        );

        $sections = ['Just released' => [], 'Today' => [], 'Tomorrow' => [], 'This week' => [], 'Later' => []];

        foreach ($releases as $release) {
            $day = $release['at']->toDateString();
            $section = match (true) {
                $release['at'] < $today => 'Just released',
                $day === $today->toDateString() => 'Today',
                $day === $today->addDay()->toDateString() => 'Tomorrow',
                $release['at'] < $today->addDays(7) => 'This week',
                default => 'Later',
            };
            $sections[$section][$day] ??= collect();
            $sections[$section][$day]->push($release);
        }

        // Newest first for what's already out.
        $sections['Just released'] = array_reverse($sections['Just released'], true);

        return array_filter($sections);
    }
}
