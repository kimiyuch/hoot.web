<?php

namespace App\Services;

use App\Models\User;

/**
 * What someone hides from Discover (Settings → Hide from Discover): anime, or titles from a language or country.
 * The choices are in hoot.hideable. Shows from TVmaze are matched by their language, channel country and
 * genres; TMDB titles by their original language, origin country (shows only, TMDB doesn't give one for movies
 * in lists) and genre ids. Search, For you and profiles still show everything.
 */
class Hidden
{
    /** TMDB's genre id for Animation; anime is that in Japanese (or TVmaze's own "Anime" genre). */
    private const TMDB_ANIMATION = 16;

    /** TMDB's "anime" keyword, so Any time can leave it out in the query itself. */
    public const TMDB_ANIME_KEYWORD = 210024;

    /**
     * The keys someone hides, only ones that exist, in the order of hoot.hideable.
     *
     * @return list<string>
     */
    public static function for(?User $user): array
    {
        return array_values(array_intersect(array_keys(config('hoot.hideable')), $user?->discover_hidden ?? []));
    }

    /** "anime, Korean and Indian titles", for the note on Discover. */
    public static function describe(array $hidden): string
    {
        $labels = array_map(fn (string $key) => $key === 'anime' ? 'anime' : config("hoot.hideable.{$key}.label"), $hidden);

        return (count($labels) > 1 ? implode(', ', array_slice($labels, 0, -1)).' and '.end($labels) : ($labels[0] ?? '')).' titles';
    }

    /** A show premiere from TVmaze (TvMaze::summary with the premiere's country). */
    public static function show(array $show, array $hidden): bool
    {
        $language = $show['language'] ?? null;
        $genres = $show['genres'] ?? [];

        foreach ($hidden as $key) {
            $option = config("hoot.hideable.{$key}");
            $match = $key === 'anime'
                ? in_array('Anime', $genres, true) || (in_array('Animation', $genres, true) && $language === 'Japanese')
                : in_array($language, $option['tvmaze_languages'], true) || in_array($show['country'] ?? null, $option['countries'], true);

            if ($match) {
                return true;
            }
        }

        return false;
    }

    /** A movie or show from TMDB, as TMDB sends it or as Tmdb::summary keeps it. */
    public static function tmdb(array $item, array $hidden): bool
    {
        $language = $item['original_language'] ?? null;

        foreach ($hidden as $key) {
            $option = config("hoot.hideable.{$key}");
            $match = $key === 'anime'
                ? in_array(self::TMDB_ANIMATION, $item['genre_ids'] ?? [], true) && $language === 'ja'
                : in_array($language, $option['languages'], true) || array_intersect($item['origin_country'] ?? [], $option['countries']) !== [];

            if ($match) {
                return true;
            }
        }

        return false;
    }
}
