<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * TMDB: movies, and when they're released in every country.
 *
 * @see https://developer.themoviedb.org/docs
 */
class Tmdb
{
    private const URL = 'https://api.themoviedb.org/3';

    // Release types in /movie/{id}/release_dates.
    public const PREMIERE = 1;

    public const THEATRICAL_LIMITED = 2;

    public const THEATRICAL = 3;

    public const DIGITAL = 4;

    public function configured(): bool
    {
        return filled(config('hoot.tmdb_token'));
    }

    /**
     * @return list<array{tmdb_id: int, title: string, year: ?string, poster_path: ?string, popularity: float, rating: ?float}>
     */
    public function searchMovies(string $query): array
    {
        $results = $this->http()->get('/search/movie', ['query' => $query, 'include_adult' => 'false'])->throw()->json('results') ?? [];

        return array_map(fn (array $movie) => self::summary($movie), $results);
    }

    /**
     * A movie with its release dates and streaming services in every country.
     */
    public function movie(int $id): array
    {
        return $this->http()->get("/movie/{$id}", ['append_to_response' => 'release_dates,watch/providers,credits'])->throw()->json();
    }

    /**
     * The first actors of several movies or shows, asked for in parallel and cached for a week per title.
     * A title that can't be loaded is left out (and asked again next time).
     *
     * @param  'movie'|'tv'  $type
     * @param  list<int>  $ids
     * @return array<int, list<string>>
     */
    public function casts(string $type, array $ids): array
    {
        $casts = [];
        $missing = [];
        foreach (array_unique($ids) as $id) {
            $cached = Cache::get("cast:tmdb:{$type}:{$id}");
            $cached === null ? $missing[] = $id : $casts[$id] = $cached;
        }

        if ($missing === []) {
            return $casts;
        }

        // Shows: aggregate_credits has the cast over all seasons, credits only the latest season's.
        $path = $type === 'tv' ? 'aggregate_credits' : 'credits';
        $responses = Http::pool(fn (Pool $pool) => array_map(
            fn (int $id) => $this->configure($pool->as((string) $id))->get("/{$type}/{$id}/{$path}"),
            $missing,
        ));

        foreach ($missing as $id) {
            $response = $responses[(string) $id] ?? null;
            if ($response instanceof Response && $response->successful()) {
                $casts[$id] = self::cast($response->json() ?? []);
                Cache::put("cast:tmdb:{$type}:{$id}", $casts[$id], now()->addWeek());
            }
        }

        return $casts;
    }

    /**
     * The first actors from TMDB credits (a movie's credits, or a show's aggregate_credits), in billing order.
     *
     * @return list<string>
     */
    public static function cast(array $credits, int $limit = 3): array
    {
        $cast = collect($credits['cast'] ?? [])->sortBy('order')->pluck('name')->all();

        return TvMaze::names($cast, $limit);
    }

    /**
     * A show's ids elsewhere (tvdb_id, imdb_id), to find it on TVmaze.
     */
    public function tvExternalIds(int $id): array
    {
        return $this->http()->get("/tv/{$id}/external_ids")->throw()->json();
    }

    /**
     * @return list<array>
     */
    public function discoverMovies(array $filters): array
    {
        return $this->discover('movie', $filters)['results'];
    }

    /**
     * @param  'movie'|'tv'  $type
     * @return array{results: list<array>, total_pages: int, total_results: int}
     */
    public function discover(string $type, array $filters): array
    {
        $response = $this->http()->get("/discover/{$type}", $filters + ['include_adult' => 'false'])->throw();

        return ['results' => $response->json('results') ?? [], 'total_pages' => (int) $response->json('total_pages', 1), 'total_results' => (int) $response->json('total_results', 0)];
    }

    /**
     * All streaming providers TMDB knows, with their logos.
     *
     * @param  'movie'|'tv'  $type
     */
    public function providers(string $type): array
    {
        return $this->http()->get("/watch/providers/{$type}")->throw()->json('results') ?? [];
    }

    /**
     * The fields Hoot shows of a movie (or, with $tv, a show) from a TMDB search or discover result.
     */
    public static function summary(array $item, bool $tv = false): array
    {
        $date = $tv ? ($item['first_air_date'] ?? null) : ($item['release_date'] ?? null);

        return [
            'tmdb_id' => $item['id'],
            'title' => $tv ? $item['name'] : $item['title'],
            'year' => filled($date) ? substr($date, 0, 4) : null,
            'poster_path' => $item['poster_path'] ?? null,
            'popularity' => (float) ($item['popularity'] ?? 0),
            'rating' => self::rating($item),
            'overview' => filled($item['overview'] ?? null) ? $item['overview'] : null,
            // For Hidden: what a title is, in TMDB's own terms (origin_country only comes with shows).
            'original_language' => $item['original_language'] ?? null,
            'genre_ids' => $item['genre_ids'] ?? [],
            'origin_country' => $item['origin_country'] ?? [],
        ];
    }

    /** TMDB's average vote, unless too few people voted for it to mean anything. */
    public static function rating(array $item): ?float
    {
        return ($item['vote_count'] ?? 0) >= 10 ? round((float) $item['vote_average'], 1) : null;
    }

    /**
     * The earliest cinema and digital release anywhere in the world, from a movie's release_dates.
     * Cinema is the earliest wide theatrical release, or a limited one if there's no wide release.
     *
     * @return array{cinema: ?array{date: string, country: string}, digital: ?array{date: string, country: string}}
     */
    public static function earliestReleases(array $releaseDates): array
    {
        $earliest = [];

        foreach ($releaseDates['results'] ?? [] as $country) {
            foreach ($country['release_dates'] ?? [] as $release) {
                if (blank($release['release_date'] ?? null)) {
                    continue;
                }
                $date = substr($release['release_date'], 0, 10);
                $type = $release['type'];

                if (! isset($earliest[$type]) || $date < $earliest[$type]['date']) {
                    $earliest[$type] = ['date' => $date, 'country' => $country['iso_3166_1']];
                }
            }
        }

        return [
            'cinema' => $earliest[self::THEATRICAL] ?? $earliest[self::THEATRICAL_LIMITED] ?? null,
            'digital' => $earliest[self::DIGITAL] ?? null,
        ];
    }

    private function http(): PendingRequest
    {
        return $this->configure(Http::baseUrl(self::URL))
            ->retry(3, 2000, fn ($exception) => $exception->response?->status() === 429, throw: false);
    }

    /** The base URL, timeout and key, for a plain request or one in a pool. */
    private function configure(PendingRequest $http): PendingRequest
    {
        $token = config('hoot.tmdb_token');

        if (blank($token)) {
            throw new RuntimeException('There is no TMDB key. Add TMDB_TOKEN to .env.');
        }

        $http = $http->baseUrl(self::URL)->acceptJson()->timeout(15);

        // The short "API Key" goes in the query string; the long "API Read Access Token" is a bearer token.
        return strlen($token) > 40
            ? $http->withToken($token)
            : $http->withQueryParameters(['api_key' => $token]);
    }
}
