<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * TVmaze: TV shows and their episodes, with the exact UTC time each episode first airs.
 * No API key; about 20 calls per 10 seconds per IP (a 429 means wait a moment and try again).
 *
 * @see https://www.tvmaze.com/api
 */
class TvMaze
{
    private const URL = 'https://api.tvmaze.com';

    /**
     * @return list<array{tvmaze_id: int, name: string, year: ?string, channel: ?string, status: ?string, image_url: ?string, url: ?string, rating: ?float}>
     */
    public function search(string $query): array
    {
        $results = $this->http()->get('/search/shows', ['q' => $query])->throw()->json() ?? [];

        return array_map(fn (array $result) => self::summary($result['show']), $results);
    }

    /**
     * A show with all of its episodes and its cast embedded.
     */
    public function show(int $id): array
    {
        // TVmaze wants embed[]=…&embed[]=…, which an array in the query wouldn't give (it numbers the brackets).
        return $this->http()->get("/shows/{$id}?embed[]=episodes&embed[]=cast")->throw()->json();
    }

    /**
     * The first actors of several shows, asked for in parallel and cached for a week per show (the same cache as
     * the premieres use). A show that can't be loaded is left out, and asked again next time.
     *
     * @param  list<int>  $ids
     * @return array<int, list<string>>
     */
    public function casts(array $ids): array
    {
        $casts = [];
        $missing = [];
        foreach (array_unique($ids) as $id) {
            $cached = Cache::get("cast:tvmaze:{$id}");
            $cached === null ? $missing[] = $id : $casts[$id] = $cached;
        }

        // TVmaze allows 20 calls in 10 seconds; a search page has at most 10 shows.
        $missing = array_slice($missing, 0, 10);
        if ($missing === []) {
            return $casts;
        }

        try {
            $responses = Http::pool(fn (Pool $pool) => array_map(
                fn (int $id) => $pool->as((string) $id)->baseUrl(self::URL)->acceptJson()->timeout(10)
                    ->withUserAgent('Hoot (https://hoot.kimiyu.ch)')->get("/shows/{$id}/cast"),
                $missing,
            ));
        } catch (Throwable $exception) {
            report($exception);

            return $casts;
        }

        foreach ($missing as $id) {
            $response = $responses[(string) $id] ?? null;
            if ($response instanceof Response && $response->successful()) {
                $casts[$id] = self::castNames($response->json() ?? []);
                Cache::put("cast:tvmaze:{$id}", $casts[$id], now()->addWeek());
            }
        }

        return $casts;
    }

    /**
     * The first $limit actors from TVmaze's cast list (main cast first), each name once.
     *
     * @return list<string>
     */
    public static function castNames(array $cast, int $limit = 3): array
    {
        return self::names(array_map(fn (array $credit) => $credit['person']['name'] ?? null, $cast), $limit);
    }

    /**
     * The first $limit actors of a show, in TVmaze's order (main cast first), each name once.
     *
     * @return list<string>
     */
    public function cast(int $id, int $limit = 3): array
    {
        return self::castNames($this->http()->get("/shows/{$id}/cast")->throw()->json() ?? [], $limit);
    }

    /** @return list<string> */
    public static function names(array $names, int $limit = 3): array
    {
        return array_slice(array_values(array_unique(array_filter($names))), 0, $limit);
    }

    /**
     * The shows that changed in the last week: TVmaze id => unix time of the change.
     *
     * @return array<int, int>
     */
    public function updates(): array
    {
        return $this->http()->get('/updates/shows', ['since' => 'week'])->throw()->json() ?? [];
    }

    /**
     * The TVmaze show for a TheTVDB or IMDb id (TMDB knows both), or null if TVmaze doesn't have it.
     */
    public function lookup(?int $tvdbId, ?string $imdbId): ?array
    {
        foreach (array_filter(['thetvdb' => $tvdbId, 'imdb' => $imdbId]) as $source => $id) {
            $response = $this->http()->get('/lookup/shows', [$source => $id]);
            if ($response->successful()) {
                return $response->json();
            }
        }

        return null;
    }

    /**
     * Every upcoming episode worldwide, on TV and streaming. It's a big response (10+ MB), so only call it
     * from the scheduled refresh, never on a page load.
     */
    public function fullSchedule(): array
    {
        return $this->http()->timeout(120)->get('/schedule/full')->throw()->json() ?? [];
    }

    /**
     * Everything that aired on one day: on streaming services worldwide ($country null), or on the TV channels
     * of one country. Unlike the full schedule, these also work for past days.
     */
    public function scheduleOn(string $date, ?string $country = null): array
    {
        $episodes = $country === null
            ? $this->http()->get('/schedule/web', ['date' => $date])->throw()->json()
            : $this->http()->get('/schedule', ['date' => $date, 'country' => $country])->throw()->json();

        // The channel schedule puts the show in "show", the others in "_embedded.show".
        return array_map(function (array $episode) {
            if (isset($episode['show'])) {
                $episode['_embedded']['show'] = $episode['show'];
                unset($episode['show']);
            }

            return $episode;
        }, $episodes ?? []);
    }

    /**
     * The fields of a show that Hoot keeps.
     */
    public static function summary(array $show): array
    {
        return [
            'tvmaze_id' => $show['id'],
            'name' => $show['name'],
            'year' => isset($show['premiered']) ? substr($show['premiered'], 0, 4) : null,
            'channel' => $show['webChannel']['name'] ?? $show['network']['name'] ?? null,
            'status' => $show['status'] ?? null,
            'image_url' => $show['image']['medium'] ?? null,
            'image_large_url' => $show['image']['original'] ?? null,
            'url' => $show['url'] ?? null,
            'official_url' => str_starts_with($show['officialSite'] ?? '', 'http') ? $show['officialSite'] : null,
            'rating' => $show['rating']['average'] ?? null,
            'genres' => $show['genres'] ?? [],
            'language' => $show['language'] ?? null,
            // The channel's country, for Hidden (premieres set the same).
            'country' => $show['network']['country']['code'] ?? $show['webChannel']['country']['code'] ?? null,
            // TVmaze's summary is HTML.
            'overview' => filled($show['summary'] ?? null) ? trim(html_entity_decode(strip_tags($show['summary']))) : null,
        ];
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl(self::URL)
            ->acceptJson()
            ->timeout(15)
            ->withUserAgent('Hoot (https://hoot.kimiyu.ch)')
            // Rate limited: wait and try again, up to three times.
            ->retry(3, 2500, fn ($exception) => $exception->response?->status() === 429, throw: false);
    }
}
