<?php

namespace App\Services;

use App\Models\Movie;
use App\Models\Show;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Sleep;
use Throwable;

/**
 * Following, favourites, and keeping the followed shows and movies up to date.
 */
class Library
{
    public function __construct(private TvMaze $tvmaze, private Tmdb $tmdb) {}

    /** At most this many favourite shows, and as many favourite movies. */
    public const MAX_FAVOURITES = 10;

    public function followShow(User $user, int $tvmazeId): Show
    {
        return tap($this->show($tvmazeId), fn (Show $show) => $this->put($user, $show, ['following' => true]));
    }

    /**
     * Follows a show found on TMDB (Browse), through TVmaze, where the episodes come from.
     * Null if TVmaze doesn't have the show.
     */
    public function followTmdbShow(User $user, int $tmdbId): ?Show
    {
        $show = $this->tmdbShow($tmdbId);

        if ($show) {
            $this->put($user, $show, ['following' => true]);
        }

        return $show;
    }

    public function followMovie(User $user, int $tmdbId): Movie
    {
        return tap($this->movie($tmdbId), fn (Movie $movie) => $this->put($user, $movie, ['following' => true]));
    }

    /** Stops following. A favourite stays in your library; anything nobody has any more leaves the catalog. */
    public function unfollow(User $user, Show|Movie $item): void
    {
        $this->library($user, $item)->newPivotStatementForId($item->id)->update(['following' => false]);
        $this->tidy($user, $item);
    }

    /** Whether there's room for another favourite show (or movie). */
    public function hasRoomForFavourite(User $user, string $type): bool
    {
        $relation = $type === Show::class ? $user->libraryShows() : $user->libraryMovies();

        return $relation->newPivotQuery()->whereNotNull('favourite_position')->count() < self::MAX_FAVOURITES;
    }

    /**
     * Adds it at the end of your favourites, without following it. False if there are already MAX_FAVOURITES.
     */
    public function favourite(User $user, Show|Movie $item): bool
    {
        $relation = $this->library($user, $item);

        if ($relation->newPivotStatementForId($item->id)->whereNotNull('favourite_position')->exists()) {
            return true;
        }

        if (! $this->hasRoomForFavourite($user, $item::class)) {
            $this->tidy($user, $item);

            return false;
        }

        $last = (int) $relation->newPivotQuery()->max('favourite_position');
        $this->put($user, $item, ['favourite_position' => $last + 1]);

        return true;
    }

    /** Takes it out of your favourites and closes the gap. Following it is left alone. */
    public function unfavourite(User $user, Show|Movie $item): void
    {
        $relation = $this->library($user, $item);
        $position = $relation->newPivotStatementForId($item->id)->value('favourite_position');

        if ($position === null) {
            return;
        }

        DB::transaction(function () use ($relation, $item, $position) {
            $relation->newPivotStatementForId($item->id)->update(['favourite_position' => null]);
            $relation->newPivotQuery()->where('favourite_position', '>', $position)->decrement('favourite_position');
        });

        $this->tidy($user, $item);
    }

    /** Swaps a favourite with the one before it (-1) or after it (1). */
    public function moveFavourite(User $user, Show|Movie $item, int $by): void
    {
        $relation = $this->library($user, $item);
        $position = $relation->newPivotStatementForId($item->id)->value('favourite_position');

        if ($position === null || ! $relation->newPivotQuery()->where('favourite_position', $position + $by)->exists()) {
            return;
        }

        DB::transaction(function () use ($relation, $item, $position, $by) {
            $relation->newPivotQuery()->where('favourite_position', $position + $by)->update(['favourite_position' => $position]);
            $relation->newPivotStatementForId($item->id)->update(['favourite_position' => $position + $by]);
        });
    }

    /** A show from the catalog, or from TVmaze if it isn't there or is more than a day old. */
    public function show(int $tvmazeId): Show
    {
        return Show::where('tvmaze_id', $tvmazeId)->where('refreshed_at', '>', now()->subDay())->first()
            ?? $this->syncShow($this->tvmaze->show($tvmazeId));
    }

    /** A show found on TMDB, looked up on TVmaze by TheTVDB or IMDb id. Null if TVmaze doesn't have it. */
    public function tmdbShow(int $tmdbId): ?Show
    {
        $ids = $this->tmdb->tvExternalIds($tmdbId);
        $found = $this->tvmaze->lookup($ids['tvdb_id'] ?? null, $ids['imdb_id'] ?? null);

        return $found ? tap($this->show($found['id']))->update(['tmdb_id' => $tmdbId]) : null;
    }

    /** A movie from the catalog, or from TMDB if it isn't there or is more than a day old. */
    public function movie(int $tmdbId): Movie
    {
        return Movie::where('tmdb_id', $tmdbId)->where('refreshed_at', '>', now()->subDay())->first()
            ?? $this->syncMovie($this->tmdb->movie($tmdbId));
    }

    private function library(User $user, Show|Movie $item): BelongsToMany
    {
        return $item instanceof Show ? $user->libraryShows() : $user->libraryMovies();
    }

    /** Sets flags on your library row, adding the row (not following) if there's none yet. */
    private function put(User $user, Show|Movie $item, array $values): void
    {
        $relation = $this->library($user, $item);

        $relation->newPivotStatementForId($item->id)->exists()
            ? $relation->newPivotStatementForId($item->id)->update($values)
            : $relation->attach($item->id, $values + ['following' => false, 'created_at' => now()]);
    }

    /** Drops your library row once it's neither followed nor a favourite, and the item once nobody has it. */
    private function tidy(User $user, Show|Movie $item): void
    {
        $this->library($user, $item)->newPivotStatementForId($item->id)
            ->where('following', false)->whereNull('favourite_position')->delete();

        if (! $item->users()->exists()) {
            $item->delete();
        }
    }

    /**
     * Refreshes the followed shows TVmaze says have changed (and any not refreshed in a week) and all followed movies.
     *
     * @return array{shows: int, movies: int, failed: list<string>}
     */
    public function refresh(bool $all = false): array
    {
        $result = ['shows' => 0, 'movies' => 0, 'failed' => []];
        $changed = $all ? [] : $this->tvmaze->updates();

        foreach (Show::followed()->get() as $show) {
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
            foreach (Movie::followed()->get() as $movie) {
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
