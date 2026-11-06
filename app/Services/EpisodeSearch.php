<?php

namespace App\Services;

use App\Models\Episode;
use Illuminate\Support\Collection;

/**
 * A search link for episodes that are out, to wherever someone chose in Settings ("Extras"): their own URL
 * with {query} in it. Hoot doesn't come with any destination, and doesn't look anything up.
 */
class EpisodeSearch
{
    public const PLACEHOLDER = '{query}';

    /** The search URL of the person looking, or null when they haven't set one. */
    public static function template(): ?string
    {
        return auth()->user()?->episode_search_url ?: null;
    }

    /**
     * The template with the search filled in: "Ted Lasso" + S04E03 → "ted+lasso+s04e03". A whole season at once
     * searches for the season (s04); specials, without a number, for the show.
     *
     * @param  Episode|Collection<int, Episode>  $episodes
     */
    public static function url(string $template, string $show, Episode|Collection $episodes): string
    {
        return str_replace(self::PLACEHOLDER, urlencode(self::query($show, $episodes)), $template);
    }

    /** @param  Episode|Collection<int, Episode>  $episodes */
    public static function query(string $show, Episode|Collection $episodes): string
    {
        $episodes = $episodes instanceof Episode ? collect([$episodes]) : $episodes->values();
        $first = $episodes->first();

        $code = match (true) {
            $first === null || $first->number === null => null,
            $episodes->count() === 1 => sprintf('s%02de%02d', $first->season, $first->number),
            $episodes->every(fn (Episode $episode) => $episode->season === $first->season) => sprintf('s%02d', $first->season),
            default => sprintf('s%02de%02d', $first->season, $first->number),
        };

        // Letters and digits only: "Grey's Anatomy: Part 2" → "greys anatomy part 2".
        $name = mb_strtolower(str_replace(["'", '’'], '', $show));
        $name = trim(preg_replace('/[^\p{L}\p{N}]+/u', ' ', $name));

        return trim($name.' '.$code);
    }

    /** "example.org", for the link's tooltip. */
    public static function host(string $template): string
    {
        return preg_replace('/^www\./', '', (string) parse_url($template, PHP_URL_HOST));
    }
}
