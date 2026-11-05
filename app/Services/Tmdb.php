<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
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
        return filled(config('possum.tmdb_token'));
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
        return $this->http()->get("/movie/{$id}", ['append_to_response' => 'release_dates,watch/providers'])->throw()->json();
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
     * @return array{results: list<array>, total_pages: int}
     */
    public function discover(string $type, array $filters): array
    {
        $response = $this->http()->get("/discover/{$type}", $filters + ['include_adult' => 'false'])->throw();

        return ['results' => $response->json('results') ?? [], 'total_pages' => (int) $response->json('total_pages', 1)];
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
     * The fields Possum shows of a movie (or, with $tv, a show) from a TMDB search or discover result.
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
        $token = config('possum.tmdb_token');

        if (blank($token)) {
            throw new RuntimeException('There is no TMDB key. Add TMDB_TOKEN to .env.');
        }

        $http = Http::baseUrl(self::URL)->acceptJson()->timeout(15)
            ->retry(3, 2000, fn ($exception) => $exception->response?->status() === 429, throw: false);

        // The short "API Key" goes in the query string; the long "API Read Access Token" is a bearer token.
        return strlen($token) > 40
            ? $http->withToken($token)
            : $http->withQueryParameters(['api_key' => $token]);
    }
}
