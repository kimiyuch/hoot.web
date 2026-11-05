<?php

namespace App\Services;

use App\Models\Movie;
use App\Models\Show;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Sleep;
use Throwable;

/**
 * Following and unfollowing, and keeping the followed shows and movies up to date.
 */
class Library
{
    public function __construct(private TvMaze $tvmaze, private Tmdb $tmdb) {}

    public function followShow(int $tvmazeId): Show
    {
        return $this->syncShow($this->tvmaze->show($tvmazeId));
    }

    /**
     * Follows a show found on TMDB (Browse), through TVmaze, where the episodes come from.
     * Null if TVmaze doesn't have the show.
     */
    public function followTmdbShow(int $tmdbId): ?Show
    {
        $ids = $this->tmdb->tvExternalIds($tmdbId);
        $show = $this->tvmaze->lookup($ids['tvdb_id'] ?? null, $ids['imdb_id'] ?? null);

        if (! $show) {
            return null;
        }

        return tap($this->followShow($show['id']))->update(['tmdb_id' => $tmdbId]);
    }

    public function followMovie(int $tmdbId): Movie
    {
        return $this->syncMovie($this->tmdb->movie($tmdbId));
    }

    /**
     * Refreshes the shows TVmaze says have changed (and any not refreshed in a week) and all movies.
     *
     * @return array{shows: int, movies: int, failed: list<string>}
     */
    public function refresh(bool $all = false): array
    {
        $result = ['shows' => 0, 'movies' => 0, 'failed' => []];
        $changed = $all ? [] : $this->tvmaze->updates();

        foreach (Show::all() as $show) {
            $stale = $all
                || $show->refreshed_at === null
                || $show->refreshed_at->lt(now()->subWeek())
                || ($changed[$show->tvmaze_id] ?? 0) >= $show->refreshed_at->getTimestamp();

            if (! $stale) {
                continue;
            }

            try {
                $this->syncShow($this->tvmaze->show($show->tvmaze_id));
                $result['shows']++;
            } catch (Throwable $exception) {
                report($exception);
                $result['failed'][] = $show->name;
            }

            // Stay well under TVmaze's 20 calls per 10 seconds.
            Sleep::for(600)->milliseconds();
        }

        if ($this->tmdb->configured()) {
            foreach (Movie::all() as $movie) {
                try {
                    $this->syncMovie($this->tmdb->movie($movie->tmdb_id));
                    $result['movies']++;
                } catch (Throwable $exception) {
                    report($exception);
                    $result['failed'][] = $movie->title;
                }
            }
        }

        return $result;
    }

    /**
     * Saves a show from TVmaze (with its episodes embedded) and replaces its episodes.
     */
    public function syncShow(array $data): Show
    {
        return DB::transaction(function () use ($data) {
            $summary = TvMaze::summary($data);

            $show = Show::updateOrCreate(['tvmaze_id' => $summary['tvmaze_id']], [
                ...collect($summary)->only(['name', 'overview', 'channel', 'status', 'image_url', 'image_large_url', 'url', 'rating', 'genres'])->all(),
                'refreshed_at' => now(),
            ]);

            $episodes = collect($data['_embedded']['episodes'] ?? [])->map(fn (array $episode) => [
                'show_id' => $show->id,
                'tvmaze_id' => $episode['id'],
                'season' => $episode['season'] ?? null,
                'number' => $episode['number'] ?? null,
                'type' => $episode['type'] ?? 'regular',
                // TVmaze names episodes "TBA" until the title is known.
                'name' => in_array($episode['name'] ?? null, ['TBA', 'TBD', ''], true) ? null : $episode['name'],
                'airs_at' => filled($episode['airstamp'] ?? null) ? Carbon::parse($episode['airstamp'])->utc() : null,
                'runtime' => $episode['runtime'] ?? null,
                'url' => $episode['url'] ?? null,
            ]);

            $show->episodes()->whereNotIn('tvmaze_id', $episodes->pluck('tvmaze_id'))->delete();

            foreach ($episodes->chunk(200) as $chunk) {
                $show->episodes()->upsert($chunk->all(), ['tvmaze_id']);
            }

            return $show;
        });
    }

    /**
     * Saves a movie from TMDB (with its release_dates appended).
     */
    public function syncMovie(array $data): Movie
    {
        $releases = Tmdb::earliestReleases($data['release_dates'] ?? []);

        return Movie::updateOrCreate(['tmdb_id' => $data['id']], [
            'title' => $data['title'],
            'rating' => Tmdb::rating($data),
            'overview' => filled($data['overview'] ?? null) ? $data['overview'] : null,
            'poster_path' => $data['poster_path'] ?? null,
            'backdrop_path' => $data['backdrop_path'] ?? null,
            'services' => StreamingServices::fromWatchProviders($data['watch/providers'] ?? []),
            'cinema_date' => $releases['cinema']['date'] ?? null,
            'cinema_country' => $releases['cinema']['country'] ?? null,
            'digital_date' => $releases['digital']['date'] ?? null,
            'digital_country' => $releases['digital']['country'] ?? null,
            'release_date' => filled($data['release_date'] ?? null) ? $data['release_date'] : null,
            'refreshed_at' => now(),
        ]);
    }
}
