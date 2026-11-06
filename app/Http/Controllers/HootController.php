<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\Show;
use App\Models\User;
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

class HootController extends Controller
{
    /** The landing page. Signed-in users don't need it and go on to For you. */
    public function welcome(Request $request): View|RedirectResponse
    {
        return $request->user() ? redirect()->route('for-you') : view('welcome');
    }

    /** Up next, the timeline by day, what just came out and everything you follow. */
    public function forYou(Request $request, Timeline $timeline, StreamingServices $services): View
    {
        $user = $request->user();
        $timezone = Timezone::for($request);
        $sections = $timeline->sections($user, $timezone);

        // The first release that isn't out yet, for the big card at the top.
        $next = collect($sections)->except('Just released')->flatten(1)->flatMap(fn ($day) => $day)
            ->first(fn (array $release) => $release['at']->isFuture() || ! $release['timed']);

        // What came out in the last week: the latest per show or movie.
        $recent = collect($sections['Just released'] ?? [])->flatMap(fn ($day) => $day)
            ->unique(fn (array $release) => isset($release['show']) ? 'show-'.$release['show']->id : 'movie-'.$release['movie']->id)
            ->values();

        return view('for-you', [
            'sections' => collect($sections)->except('Just released')->all(),
            'next' => $next,
            'recent' => $recent,
            'timezone' => $timezone,
            'logos' => $services->logos(),
            'shows' => $user->shows()->with(['nextEpisode', 'episodes' => fn ($query) => $query->orderBy('number')->orderBy('airs_at')])->get()
                // Shows with a next episode first, soonest first; then the rest by name.
                ->sortBy(fn (Show $show) => [$show->nextEpisode ? 0 : 1, $show->nextEpisode?->airs_at?->getTimestamp() ?? 0, $show->name])
                ->values(),
            'movies' => $user->movies()->orderBy('title')->get(),
        ]);
    }

    /**
     * Premieres and the TMDB catalog on one page. "When" picks the source: soon and new come from
     * the premieres that hoot:refresh builds, "any" asks TMDB discover.
     */
    public function discover(Request $request, Premieres $premieres, Browse $browse, Tmdb $tmdb, StreamingServices $services): View
    {
        $input = $request->validate([
            'when' => [Rule::in(['soon', 'new', 'any'])],
            'type' => [Rule::in(['movie', 'tv'])],
            'service' => [Rule::in(array_keys(StreamingServices::all()))],
            'region' => [Rule::in(array_keys(config('hoot.regions')))],
            'from' => ['integer', 'between:1950,'.now()->year],
            'sort' => [Rule::in(array_keys(Browse::SORTS))],
            'min_rating' => [Rule::in(array_keys(Browse::MIN_RATINGS))],
            'page' => ['integer', 'between:1,500'],
        ]);

        $filters = [
            'when' => $input['when'] ?? 'soon',
            'type' => $input['type'] ?? 'tv',
            'service' => $input['service'] ?? null,
            'region' => $input['region'] ?? config('hoot.region'),
            'from' => (int) ($input['from'] ?? config('hoot.browse_from_year') ?? now()->year),
            'sort' => $input['sort'] ?? 'popular',
            'min_rating' => (int) ($input['min_rating'] ?? 0),
            'page' => (int) ($input['page'] ?? 1),
        ];

        $shared = [
            'filters' => $filters,
            'moviesEnabled' => $tmdb->configured(),
            'logos' => $services->logos(),
            'timezone' => Timezone::for($request),
            'followedMovies' => $request->user()->movies()->pluck('tmdb_id')->flip(),
            'favouriteMovies' => $request->user()->favouriteMovies()->pluck('movies.id', 'tmdb_id'),
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
                'followedShows' => $request->user()->shows()->whereNotNull('tmdb_id')->pluck('tmdb_id')->flip(),
                'favouriteShows' => $request->user()->favouriteShows()->whereNotNull('tmdb_id')->pluck('shows.id', 'tmdb_id'),
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
            'followedShows' => $request->user()->shows()->pluck('tvmaze_id')->flip(),
            'favouriteShows' => $request->user()->favouriteShows()->pluck('shows.id', 'tvmaze_id'),
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

        // The lightbox fetches just the results.
        return view($request->header('X-Hoot-Partial') ? 'partials.search-results' : 'search', [
            'query' => $query,
            'shows' => $shows,
            'movies' => $movies,
            'searchErrors' => $errors,
            'moviesEnabled' => $tmdb->configured(),
            'followedShows' => $request->user()->shows()->pluck('tvmaze_id')->flip(),
            'followedMovies' => $request->user()->movies()->pluck('tmdb_id')->flip(),
            'favouriteShows' => $request->user()->favouriteShows()->pluck('shows.id', 'tvmaze_id'),
            'favouriteMovies' => $request->user()->favouriteMovies()->pluck('movies.id', 'tmdb_id'),
        ]);
    }

    public function followShow(Request $request, Library $library): RedirectResponse
    {
        $ids = $request->validate([
            'tvmaze_id' => ['required_without:tmdb_id', 'integer', 'min:1'],
            'tmdb_id' => ['required_without:tvmaze_id', 'integer', 'min:1'],
        ]);

        try {
            $show = isset($ids['tvmaze_id']) ? $library->followShow($request->user(), $ids['tvmaze_id']) : $library->followTmdbShow($request->user(), $ids['tmdb_id']);
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('status', 'That show couldn’t be loaded. Try again in a moment.');
        }

        if (! $show) {
            return back()->with('status', 'TVmaze doesn’t have that show, so there are no episode dates for it.');
        }

        return back()->with('status', "Following {$show->name}.");
    }

    public function unfollowShow(Request $request, Show $show, Library $library): RedirectResponse
    {
        $library->unfollow($request->user(), $show);

        return back()->with('status', "Stopped following {$show->name}.");
    }

    public function followMovie(Request $request, Library $library): RedirectResponse
    {
        $id = $request->validate(['tmdb_id' => ['required', 'integer', 'min:1']])['tmdb_id'];

        try {
            $movie = $library->followMovie($request->user(), $id);
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('status', 'That movie couldn’t be loaded from TMDB. Try again in a moment.');
        }

        return back()->with('status', "Following {$movie->title}.");
    }

    public function unfollowMovie(Request $request, Movie $movie, Library $library): RedirectResponse
    {
        $library->unfollow($request->user(), $movie);

        return back()->with('status', "Stopped following {$movie->title}.");
    }

    /** The feed has no login: the secret token in the URL says whose it is. */
    public function calendar(string $token, Calendar $calendar): Response
    {
        $user = User::where('calendar_token', $token)->firstOrFail();

        return response($calendar->ics($user), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'inline; filename="hoot.ics"',
            'Cache-Control' => 'private, max-age=900',
        ]);
    }
}
