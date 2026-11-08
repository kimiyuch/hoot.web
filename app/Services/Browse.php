<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Browsing TMDB's catalogue: movies or shows since a year, on a streaming service in a country, by popularity or rating.
 * Each page of results is cached for six hours.
 */
class Browse
{
    public const SORTS = [
        'popular' => 'Most popular',
        'rating' => 'Best rated',
        'newest' => 'Newest',
    ];

    public const MIN_RATINGS = [0 => 'Any rating', 6 => '6+', 7 => '7+', 8 => '8+'];

    /** The region value that asks every country in hoot.regions at once. */
    public const ALL_REGIONS = 'all';

    /** TMDB's page size, kept for the merged "all regions" pages. */
    private const PER_PAGE = 20;

    /** The "Streaming in" choices: all regions, then the countries. */
    public static function regions(): array
    {
        return [self::ALL_REGIONS => 'All regions'] + config('hoot.regions');
    }

    public function __construct(private Tmdb $tmdb) {}

    /**
     * One page of the catalog. $hidden is what the user hides from Discover (Hidden::for); TMDB can't leave
     * out languages itself, so with anything hidden the page comes from merge(), which filters as it goes.
     * "counted" is false when the page count is only what's known so far (the view then says just "Page 3").
     *
     * @param  array{type: 'movie'|'tv', service: ?string, region: string, from: int, sort: string, min_rating: int, page: int}  $filters
     * @param  list<string>  $hidden
     * @return array{results: list<array>, total_pages: int, counted: bool}
     */
    public function results(array $filters, array $hidden = []): array
    {
        $tv = $filters['type'] === 'tv';
        $date = $tv ? 'first_air_date' : 'primary_release_date';

        $query = [
            "{$date}.gte" => $filters['from'].'-01-01',
            "{$date}.lte" => now()->toDateString(),
            'sort_by' => match ($filters['sort']) {
                'rating' => 'vote_average.desc',
                'newest' => "{$date}.desc",
                default => 'popularity.desc',
            },
            // Without enough votes, "best rated" is full of films three people saw.
            'vote_count.gte' => $filters['sort'] === 'rating' ? 300 : 20,
            'page' => $filters['page'],
        ];

        if ($filters['min_rating'] > 0) {
            $query['vote_average.gte'] = $filters['min_rating'];
        }

        // Anime that TMDB tagged as such stays out of the query already; merge() catches the rest.
        if (in_array('anime', $hidden, true)) {
            $query['without_keywords'] = Hidden::TMDB_ANIME_KEYWORD;
        }

        // The countries to ask: one, all of hoot.regions, or none (no service, no country needed).
        $regions = [null];
        if ($filters['service'] !== null) {
            $query['with_watch_providers'] = implode('|', StreamingServices::all()[$filters['service']]['tmdb']);
            $query['with_watch_monetization_types'] = 'flatrate|free|ads';
            $regions = $filters['region'] === self::ALL_REGIONS ? array_keys(config('hoot.regions')) : [$filters['region']];
        }

        $cacheKey = 'browse:v3:'.md5(json_encode([$filters['type'], $query, $regions, $hidden]));
        $page = Cache::remember($cacheKey, now()->addHours(6), function () use ($filters, $query, $tv, $regions, $hidden) {
            if (count($regions) === 1 && $hidden === []) {
                $found = $this->tmdb->discover($filters['type'], array_filter(['watch_region' => $regions[0]]) + $query);
                $found['counted'] = true;
            } else {
                $found = $this->merge($filters, $query, $tv, $regions, $hidden);
            }

            return [
                'results' => array_map(fn (array $item) => Tmdb::summary($item, $tv), $found['results']),
                // TMDB never returns more than 500 pages.
                'total_pages' => min($found['total_pages'], 500),
                'counted' => $found['counted'],
            ];
        });

        // The cast isn't in TMDB's discover results; it's looked up per title (and cached a week), and a
        // card simply goes without it if that fails.
        try {
            $casts = $this->tmdb->casts($filters['type'], array_column($page['results'], 'tmdb_id'));
        } catch (Throwable $exception) {
            report($exception);
            $casts = [];
        }
        $page['results'] = array_map(fn (array $item) => $item + ['cast' => $casts[$item['tmdb_id']] ?? []], $page['results']);

        return $page;
    }

    /**
     * Pages of 20 cut from TMDB's results, for when TMDB can't do it alone: a service in all regions (TMDB only
     * filters by service within one country) and hidden titles (TMDB can't leave out a language).
     * Each country's results come sorted by the same key (popularity, rating and dates are the same everywhere),
     * so this takes the best next title from any country, skips ones it already has or that are hidden, and
     * fetches (and caches) each country's next TMDB page as needed. Page p asks TMDB for at least p pages per
     * country, more when a lot is hidden. How many titles are left in the end isn't known, so the page count
     * is what's known so far: this page, plus one more while there are titles left.
     *
     * @param  list<?string>  $regions
     * @return array{results: list<array>, total_pages: int, counted: false}
     */
    private function merge(array $filters, array $query, bool $tv, array $regions, array $hidden): array
    {
        $date = $tv ? 'first_air_date' : 'release_date';
        $key = match ($filters['sort']) {
            'rating' => fn (array $item) => (float) ($item['vote_average'] ?? 0),
            'newest' => fn (array $item) => (string) ($item[$date] ?? ''),
            default => fn (array $item) => (float) ($item['popularity'] ?? 0),
        };

        $wanted = $filters['page'] * self::PER_PAGE;
        // At most this many TMDB pages per country, in case nearly everything is hidden.
        $budget = min(3 * $filters['page'] + 5, 500);
        $streams = [];
        foreach ($regions as $region) {
            $streams[(string) $region] = ['items' => [], 'page' => 0, 'pages' => 1];
        }

        // The next title of a country, fetching its next page when it has run out; null when it has no more.
        $head = function (string $region) use (&$streams, $filters, $query, $budget): ?array {
            $stream = &$streams[$region];
            while ($stream['items'] === [] && $stream['page'] < min($stream['pages'], $budget)) {
                $found = $this->regionPage($filters['type'], array_filter(['watch_region' => $region]) + ['page' => ++$stream['page']] + $query);
                $stream['items'] = $found['results'];
                $stream['pages'] = $found['total_pages'];
            }

            return $stream['items'][0] ?? null;
        };

        $merged = [];
        $seen = [];
        while (count($merged) <= $wanted) {
            $best = null;
            foreach (array_keys($streams) as $region) {
                $item = $head((string) $region);
                if ($item !== null && ($best === null || $key($item) > $key($head($best)))) {
                    $best = (string) $region;
                }
            }
            if ($best === null) {
                break;
            }

            $item = array_shift($streams[$best]['items']);
            if (! isset($seen[$item['id']]) && ! Hidden::tmdb($item, $hidden)) {
                $merged[] = $item;
            }
            $seen[$item['id']] = true;
        }

        return [
            'results' => array_slice($merged, $wanted - self::PER_PAGE, self::PER_PAGE),
            'total_pages' => $filters['page'] + (count($merged) > $wanted ? 1 : 0),
            'counted' => false,
        ];
    }

    /** One page of one country, cached on its own so the merged pages can share it. */
    private function regionPage(string $type, array $query): array
    {
        return Cache::remember('browse:v3:region:'.md5(json_encode([$type, $query])), now()->addHours(6), fn () => $this->tmdb->discover($type, $query));
    }
}
