<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Shows on TVmaze and movies on TMDB, each cached for a day, and people on Hoot, for the search page and the lightbox.
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
            $result['shows'] = Cache::remember('search:tv:v2:'.$key, now()->addDay(), fn () => $this->tvmaze->search($query));
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

    /**
     * Leaves out what someone hides (Settings → Hide from Discover and Search), counting how many went.
     *
     * @return array{shows: array, movies: array, errors: list<string>, hidden: int}
     */
    public static function hide(array $results, array $hidden): array
    {
        $shows = array_values(array_filter($results['shows'], fn (array $show) => ! Hidden::show($show, $hidden)));
        $movies = array_values(array_filter($results['movies'], fn (array $movie) => ! Hidden::tmdb($movie, $hidden)));

        return ['shows' => $shows, 'movies' => $movies, 'errors' => $results['errors'],
            'hidden' => count($results['shows']) - count($shows) + count($results['movies']) - count($movies)];
    }

    /**
     * People whose username starts with the term or whose name contains it, leaving out anyone the viewer
     * blocked or was blocked by. A leading @ is ignored.
     *
     * @return Collection<int, User>
     */
    public function people(User $viewer, string $query, int $limit = 6): Collection
    {
        $term = ltrim(trim($query), '@');

        if ($term === '') {
            return new Collection;
        }

        // ! as the LIKE escape character: it means the same in SQLite and MySQL, unlike a backslash.
        $like = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($term));

        return User::visibleTo($viewer)
            ->where(fn ($query) => $query
                ->whereRaw("username like ? escape '!'", [$like.'%'])
                ->orWhereRaw("lower(name) like ? escape '!'", ['%'.$like.'%']))
            // Exact handles first, then handles that start with it, then names.
            ->orderByRaw("case when username = ? then 0 when username like ? escape '!' then 1 else 2 end", [$like, $like.'%'])
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }
}
