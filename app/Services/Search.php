<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Shows on TVmaze and movies on TMDB, each cached for a day, for the search page and the lightbox.
 */
class Search
{
    public function __construct(private TvMaze $tvmaze, private Tmdb $tmdb) {}

    /** @return array{shows: array, movies: array, errors: list<string>} */
    public function run(string $query): array
    {
        $result = ['shows' => [], 'movies' => [], 'errors' => []];
        $key = mb_strtolower($query);

        if ($query === '') {
            return $result;
        }

        try {
            $result['shows'] = Cache::remember('search:tv:'.$key, now()->addDay(), fn () => $this->tvmaze->search($query));
        } catch (Throwable $exception) {
            report($exception);
            $result['errors'][] = 'TVmaze can’t be reached right now.';
        }

        if ($this->tmdb->configured()) {
            try {
                $result['movies'] = Cache::remember('search:movie:'.$key, now()->addDay(),
                    fn () => collect($this->tmdb->searchMovies($query))->sortByDesc('popularity')->take(18)->values()->all());
            } catch (Throwable $exception) {
                report($exception);
                $result['errors'][] = 'TMDB can’t be reached right now.';
            }
        }

        return $result;
    }
}
