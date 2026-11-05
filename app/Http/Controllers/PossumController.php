<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\Show;
use App\Services\Browse;
use App\Services\Calendar;
use App\Services\Library;
use App\Services\Premieres;
use App\Services\StreamingServices;
use App\Services\Timeline;
use App\Services\Tmdb;
use App\Services\TvMaze;
use App\Timezone;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class PossumController extends Controller
{
    public function home(Request $request, Timeline $timeline, StreamingServices $services): View
    {
        $timezone = Timezone::for($request);
        $sections = $timeline->sections($timezone);

        // The first release that isn't out yet, for the big card at the top.
        $next = collect($sections)->except('Just released')->flatten(1)->flatMap(fn ($day) => $day)
            ->first(fn (array $release) => $release['at']->isFuture() || ! $release['timed']);

        // What came out in the last week: the latest per show or movie.
        $recent = collect($sections['Just released'] ?? [])->flatMap(fn ($day) => $day)
            ->unique(fn (array $release) => isset($release['show']) ? 'show-'.$release['show']->id : 'movie-'.$release['movie']->id)
            ->values();

        return view('home', [
            'sections' => collect($sections)->except('Just released')->all(),
            'next' => $next,
            'recent' => $recent,
            'timezone' => $timezone,
            'logos' => $services->logos(),
            'shows' => Show::with('nextEpisode')->get()
                // Shows with a next episode first, soonest first; then the rest by name.
                ->sortBy(fn (Show $show) => [$show->nextEpisode ? 0 : 1, $show->nextEpisode?->airs_at?->getTimestamp() ?? 0, $show->name])
                ->values(),
            'movies' => Movie::orderBy('title')->get(),
        ]);
    }

    /**
     * Premieres and the TMDB catalog on one page. "When" picks the source: soon and new come from
     * the premieres that possum:refresh builds, "any" asks TMDB discover.
     */
    public function discover(Request $request, Premieres $premieres, Browse $browse, Tmdb $tmdb, StreamingServices $services): View
    {
        $input = $request->validate([
            'when' => [Rule::in(['soon', 'new', 'any'])],
            'type' => [Rule::in(['movie', 'tv'])],
            'service' => [Rule::in(array_keys(StreamingServices::all()))],
            'region' => [Rule::in(array_keys(config('possum.regions')))],
            'from' => ['integer', 'between:1950,'.now()->year],
            'sort' => [Rule::in(array_keys(Browse::SORTS))],
            'min_rating' => [Rule::in(array_keys(Browse::MIN_RATINGS))],
            'page' => ['integer', 'between:1,500'],
        ]);

        $filters = [
            'when' => $input['when'] ?? 'soon',
            'type' => $input['type'] ?? 'tv',
            'service' => $input['service'] ?? null,
            'region' => $input['region'] ?? config('possum.region'),
            'from' => (int) ($input['from'] ?? config('possum.browse_from_year') ?? now()->year),
            'sort' => $input['sort'] ?? 'popular',
            'min_rating' => (int) ($input['min_rating'] ?? 0),
            'page' => (int) ($input['page'] ?? 1),
        ];

        $shared = [
            'filters' => $filters,
            'moviesEnabled' => $tmdb->configured(),
            'logos' => $services->logos(),
            'timezone' => Timezone::for($request),
            'followedMovies' => Movie::pluck('tmdb_id')->flip(),
        ];

        if ($filters['when'] === 'any') {
            $results = ['results' => [], 'total_pages' => 0];
            $error = null;

            if ($tmdb->configured()) {
                try {
                    $results = $browse->results($filters);
                } catch (Throwable $exception) {
                    report($exception);
                    $error = 'TMDB can’t be reached right now.';
                }
            }

            return view('discover', $shared + [
                'items' => collect($results['results']),
                'totalPages' => $results['total_pages'],
                'error' => $error,
                'serviceCounts' => null,
                'refreshedAt' => null,
                'followedShows' => Show::whereNotNull('tmdb_id')->pluck('tmdb_id')->flip(),
            ]);
        }

        $data = $premieres->get();
        $now = CarbonImmutable::now()->utc()->toIso8601String();
        $today = now()->toDateString();

        $items = $filters['type'] === 'tv'
            ? collect($data['shows'])->filter(fn ($show) => $filters['when'] === 'soon' ? $show['airs_at'] > $now : $show['airs_at'] <= $now)
            // A movie is "soon" while one of its releases is still to come, and "new" when one was in the last month.
            : collect($data['movies'])->filter(function ($movie) use ($filters, $today) {
                $dates = array_filter([$movie['cinema_date'] ?? null, $movie['digital_date'] ?? null]);

                return $filters['when'] === 'soon'
                    ? collect($dates)->contains(fn ($date) => $date > $today)
                    : collect($dates)->contains(fn ($date) => $date <= $today);
            });

        // Which services have anything here, with how many.
        $serviceKeys = fn (array $item) => isset($item['services']) ? $item['services'] : array_filter([$item['service'] ?? null]);
        $serviceCounts = $items->flatMap($serviceKeys)->countBy()->sortDesc();

        if ($filters['service'] && ! $serviceCounts->has($filters['service'])) {
            $filters['service'] = null;
        }
        if ($filters['service']) {
            $items = $items->filter(fn ($item) => in_array($filters['service'], $serviceKeys($item), true));
        }

        return view('discover', array_merge($shared, [
            'filters' => $filters,
            'items' => $items->values(),
            'totalPages' => 1,
            'error' => null,
            'serviceCounts' => $serviceCounts,
            'refreshedAt' => $data['refreshed_at'],
            'followedShows' => Show::pluck('tvmaze_id')->flip(),
        ]));
    }

    public function search(Request $request, TvMaze $tvmaze, Tmdb $tmdb): View
    {
        $query = trim((string) $request->query('q'));
        $shows = $movies = [];
        $errors = [];

        if ($query !== '') {
            try {
                // Cached for a day, so going back and forth between results doesn't ask TVmaze again.
                $shows = Cache::remember('search:tv:'.mb_strtolower($query), now()->addDay(), fn () => $tvmaze->search($query));
            } catch (Throwable $exception) {
                report($exception);
                $errors[] = 'TVmaze can’t be reached right now.';
            }

            if ($tmdb->configured()) {
                try {
                    $movies = Cache::remember('search:movie:'.mb_strtolower($query), now()->addDay(),
                        fn () => collect($tmdb->searchMovies($query))->sortByDesc('popularity')->take(18)->values()->all());
                } catch (Throwable $exception) {
                    report($exception);
                    $errors[] = 'TMDB can’t be reached right now.';
                }
            }
        }

        return view('search', [
            'query' => $query,
            'shows' => $shows,
            'movies' => $movies,
            'searchErrors' => $errors,
            'moviesEnabled' => $tmdb->configured(),
            'followedShows' => Show::pluck('tvmaze_id')->flip(),
            'followedMovies' => Movie::pluck('tmdb_id')->flip(),
        ]);
    }

    public function followShow(Request $request, Library $library): RedirectResponse
    {
        $ids = $request->validate([
            'tvmaze_id' => ['required_without:tmdb_id', 'integer', 'min:1'],
            'tmdb_id' => ['required_without:tvmaze_id', 'integer', 'min:1'],
        ]);

        try {
            $show = isset($ids['tvmaze_id']) ? $library->followShow($ids['tvmaze_id']) : $library->followTmdbShow($ids['tmdb_id']);
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('status', 'That show couldn’t be loaded. Try again in a moment.');
        }

        if (! $show) {
            return back()->with('status', 'TVmaze doesn’t have that show, so there are no episode dates for it.');
        }

        return back()->with('status', "Following {$show->name}.");
    }

    public function unfollowShow(Show $show): RedirectResponse
    {
        $show->delete();

        return back()->with('status', "Stopped following {$show->name}.");
    }

    public function followMovie(Request $request, Library $library): RedirectResponse
    {
        $id = $request->validate(['tmdb_id' => ['required', 'integer', 'min:1']])['tmdb_id'];

        try {
            $movie = $library->followMovie($id);
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('status', 'That movie couldn’t be loaded from TMDB. Try again in a moment.');
        }

        return back()->with('status', "Following {$movie->title}.");
    }

    public function unfollowMovie(Movie $movie): RedirectResponse
    {
        $movie->delete();

        return back()->with('status', "Stopped following {$movie->title}.");
    }

    public function calendar(Calendar $calendar): Response
    {
        return response($calendar->ics(), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'inline; filename="possum.ics"',
            'Cache-Control' => 'public, max-age=900',
        ]);
    }
}
