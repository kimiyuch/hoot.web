<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

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
     * @param  array{type: 'movie'|'tv', service: ?string, region: string, from: int, sort: string, min_rating: int, page: int}  $filters
     * @return array{results: list<array>, total_pages: int}
     */
    public function results(array $filters): array
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

        if ($filters['service'] !== null) {
            $query['with_watch_providers'] = implode('|', StreamingServices::all()[$filters['service']]['tmdb']);
            $query['watch_region'] = $filters['region'];
            $query['with_watch_monetization_types'] = 'flatrate|free|ads';
        }

        return Cache::remember('browse:v2:'.md5(json_encode([$filters['type'], $query])), now()->addHours(6), function () use ($filters, $query, $tv) {
            $found = ($query['watch_region'] ?? null) === self::ALL_REGIONS
                ? $this->everyRegion($filters, $query, $tv)
                : $this->tmdb->discover($filters['type'], $query);

            return [
                'results' => array_map(fn (array $item) => Tmdb::summary($item, $tv), $found['results']),
                // TMDB never returns more than 500 pages.
                'total_pages' => min($found['total_pages'], 500),
            ];
        });
    }

    /**
     * TMDB only filters by service within one country, so "all regions" merges the countries in hoot.regions.
     * Each country's results come sorted by the same key (popularity, rating and dates are the same everywhere),
     * so this takes the best next title from any country, skips ones it already has, and cuts pages of 20 from
     * the merged list, fetching (and caching) each country's next TMDB page as needed. Page p asks TMDB for at
     * most p pages per country. How many titles the union holds isn't known, so the page count is the biggest
     * country's, plus one more while the merge still has titles left.
     *
     * @return array{results: list<array>, total_pages: int}
     */
    private function everyRegion(array $filters, array $query, bool $tv): array
    {
        $date = $tv ? 'first_air_date' : 'release_date';
        $key = match ($filters['sort']) {
            'rating' => fn (array $item) => (float) ($item['vote_average'] ?? 0),
            'newest' => fn (array $item) => (string) ($item[$date] ?? ''),
            default => fn (array $item) => (float) ($item['popularity'] ?? 0),
        };

        $wanted = $filters['page'] * self::PER_PAGE;
        $streams = [];
        $largest = 0;
        foreach (array_keys(config('hoot.regions')) as $region) {
            $streams[$region] = ['items' => [], 'page' => 0, 'pages' => 1];
        }

        // The next title of a country, fetching its next page when it has run out; null when it has no more.
        $head = function (string $region) use (&$streams, &$largest, $filters, $query): ?array {
            $stream = &$streams[$region];
            while ($stream['items'] === [] && $stream['page'] < min($stream['pages'], 500)) {
                $found = $this->regionPage($filters['type'], ['watch_region' => $region, 'page' => ++$stream['page']] + $query);
                $stream['items'] = $found['results'];
                $stream['pages'] = $found['total_pages'];
                $largest = max($largest, $found['total_results']);
            }

            return $stream['items'][0] ?? null;
        };

        $merged = [];
        $seen = [];
        while (count($merged) <= $wanted) {
            $best = null;
            foreach (array_keys($streams) as $region) {
                $item = $head($region);
                if ($item !== null && ($best === null || $key($item) > $key($head($best)))) {
                    $best = $region;
                }
            }
            if ($best === null) {
                break;
            }

            $item = array_shift($streams[$best]['items']);
            if (! isset($seen[$item['id']])) {
                $seen[$item['id']] = true;
                $merged[] = $item;
            }
        }

        $more = count($merged) > $wanted;

        return [
            'results' => array_slice($merged, $wanted - self::PER_PAGE, self::PER_PAGE),
            'total_pages' => max((int) ceil($largest / self::PER_PAGE), $filters['page'] + ($more ? 1 : 0)),
        ];
    }

    /** One page of one country, cached on its own so the merged pages can share it. */
    private function regionPage(string $type, array $query): array
    {
        return Cache::remember('browse:v2:region:'.md5(json_encode([$type, $query])), now()->addHours(6), fn () => $this->tmdb->discover($type, $query));
    }
}
