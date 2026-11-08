<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;
use Throwable;

/**
 * New shows, season premieres and upcoming movies across all services, most popular first.
 * Built by the scheduled refresh, because TVmaze's full schedule is too big to load on a page view. Kept as a file in
 * storage/app/private, not in the cache: deploys clear the cache (optimize:clear), and rebuilding takes over a minute.
 */
class Premieres
{
    private const FILE = 'premieres.json';

    public const DAYS_AHEAD = 30;

    public const DAYS_BACK = 30;

    // TVmaze's full schedule only looks ahead. For the past, the worldwide streaming schedule and these countries'
    // TV channels are loaded day by day.
    public const PAST_COUNTRIES = ['US', 'GB'];

    public function __construct(private TvMaze $tvmaze, private Tmdb $tmdb) {}

    /**
     * @return array{shows: list<array>, movies: list<array>, refreshed_at: ?string}
     */
    public function get(): array
    {
        $saved = json_decode(Storage::disk('local')->get(self::FILE) ?? '', true);

        return is_array($saved) ? $saved : ['shows' => [], 'movies' => [], 'refreshed_at' => null];
    }

    public function refresh(): array
    {
        $previous = $this->get();

        $premieres = [
            'shows' => $this->attempt(fn () => $this->shows(), $previous['shows']),
            'movies' => $this->tmdb->configured() ? $this->attempt(fn () => $this->movies(), $previous['movies']) : [],
            'refreshed_at' => now()->toIso8601String(),
        ];

        Storage::disk('local')->put(self::FILE, json_encode($premieres, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return $premieres;
    }

    /**
     * Episode 1 of a season, from a month ago until a month from now, most popular show first.
     * Up to $limit that are still to come and $limit that have started.
     */
    public function shows(int $limit = 100): array
    {
        $from = now()->subDays(self::DAYS_BACK)->startOfDay();
        $until = now()->addDays(self::DAYS_AHEAD);

        return collect($this->tvmaze->fullSchedule())
            ->concat($this->pastSchedule())
            ->filter(fn (array $episode) => ($episode['number'] ?? null) === 1
                && ($episode['type'] ?? 'regular') === 'regular'
                && filled($episode['airstamp'] ?? null)
                && Carbon::parse($episode['airstamp'])->between($from, $until))
            ->map(function (array $episode) {
                $show = $episode['_embedded']['show'];

                return TvMaze::summary($show) + [
                    'season' => $episode['season'],
                    'airs_at' => Carbon::parse($episode['airstamp'])->utc()->toIso8601String(),
                    'weight' => $show['weight'] ?? 0,
                    'genres' => $show['genres'] ?? [],
                    'country' => $show['network']['country']['code'] ?? $show['webChannel']['country']['code'] ?? null,
                    'service' => StreamingServices::forChannel($show['webChannel']['name'] ?? $show['network']['name'] ?? null),
                ];
            })
            ->unique(fn (array $show) => $show['tvmaze_id'].'-'.$show['season'])
            ->sortBy([['weight', 'desc'], ['airs_at', 'asc']])
            // The most popular of what's coming up, and of what started recently, $limit each.
            ->groupBy(fn (array $show) => $show['airs_at'] > now()->utc()->toIso8601String() ? 'upcoming' : 'recent')
            ->flatMap(fn ($group) => $group->take($limit))
            ->map(fn (array $show) => $show + ['cast' => $this->showCast($show['tvmaze_id'])])
            ->values()
            ->all();
    }

    /**
     * A show's first actors from TVmaze, cached for a week, so a refresh only asks for the new premieres.
     * Nothing (and not cached) if TVmaze can't be reached.
     *
     * @return list<string>
     */
    private function showCast(int $id): array
    {
        $key = "cast:tvmaze:{$id}";
        if (($cached = Cache::get($key)) !== null) {
            return $cached;
        }

        try {
            $cast = $this->tvmaze->cast($id);
            Cache::put($key, $cast, now()->addWeek());
        } catch (Throwable $exception) {
            report($exception);
            $cast = [];
        }

        // Stay well under TVmaze's 20 calls per 10 seconds.
        Sleep::for(600)->milliseconds();

        return $cast;
    }

    /**
     * What aired in the last month, day by day. A day that can't be loaded is skipped.
     */
    private function pastSchedule(): array
    {
        $episodes = [];

        for ($day = 1; $day <= self::DAYS_BACK; $day++) {
            $date = now()->subDays($day)->toDateString();

            foreach ([null, ...self::PAST_COUNTRIES] as $country) {
                try {
                    // Only the first episodes; a day can have hundreds of others.
                    $firsts = array_filter($this->tvmaze->scheduleOn($date, $country), fn (array $episode) => ($episode['number'] ?? null) === 1);
                    array_push($episodes, ...$firsts);
                } catch (Throwable $exception) {
                    report($exception);
                }

                // Stay well under TVmaze's 20 calls per 10 seconds.
                Sleep::for(600)->milliseconds();
            }
        }

        return $episodes;
    }

    /**
     * Popular movies out in cinemas or digitally in the last month or the next three months, with their earliest dates.
     */
    public function movies(int $limit = 60): array
    {
        $from = now()->subDays(self::DAYS_BACK)->toDateString();
        $until = now()->addMonths(3)->toDateString();

        $found = collect([1, 2, 3])->flatMap(fn (int $page) => $this->tmdb->discoverMovies([
            'release_date.gte' => $from,
            'release_date.lte' => $until,
            'with_release_type' => Tmdb::THEATRICAL.'|'.Tmdb::DIGITAL,
            'sort_by' => 'popularity.desc',
            'page' => $page,
        ]))->unique('id')->take($limit);

        return $found->map(function (array $movie) {
            $details = $this->tmdb->movie($movie['id']);
            $releases = Tmdb::earliestReleases($details['release_dates'] ?? []);

            return Tmdb::summary($movie) + [
                'cinema_date' => $releases['cinema']['date'] ?? null,
                'digital_date' => $releases['digital']['date'] ?? null,
                'services' => StreamingServices::fromWatchProviders($details['watch/providers'] ?? []),
                'cast' => Tmdb::cast($details['credits'] ?? []),
            ];
        })
            // Out everywhere for more than a month? Then it isn't new any more.
            ->filter(fn (array $movie) => max($movie['cinema_date'] ?? '', $movie['digital_date'] ?? '') >= now()->subDays(self::DAYS_BACK)->toDateString())
            ->values()
            ->all();
    }

    private function attempt(callable $build, array $fallback): array
    {
        try {
            return $build();
        } catch (Throwable $exception) {
            report($exception);

            return $fallback;
        }
    }
}
