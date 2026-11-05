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
            $found = $this->tmdb->discover($filters['type'], $query);

            return [
                'results' => array_map(fn (array $item) => Tmdb::summary($item, $tv), $found['results']),
                // TMDB never returns more than 500 pages.
                'total_pages' => min($found['total_pages'], 500),
            ];
        });
    }
}
