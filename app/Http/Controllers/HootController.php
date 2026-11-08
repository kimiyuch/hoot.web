<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\Show;
use App\Models\User;
use App\Services\Browse;
use App\Services\Calendar;
use App\Services\Circle;
use App\Services\Library;
use App\Services\People;
use App\Services\Premieres;
use App\Services\Search;
use App\Services\StreamingServices;
use App\Services\Timeline;
use App\Services\Tmdb;
use App\Timezone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
    public function forYou(Request $request, Timeline $timeline, StreamingServices $services, People $people): View
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
            'next' => $next,
            'recent' => $recent,
            'timezone' => $timezone,
            'logos' => $services->logos(),
            'shows' => $user->shows()->with(['nextEpisode', 'episodes' => fn ($query) => $query->orderBy('number')->orderBy('airs_at')])->get()
                // Shows with a next episode first, soonest first; then the rest by name.
                ->sortBy(fn (Show $show) => [$show->nextEpisode ? 0 : 1, $show->nextEpisode?->airs_at?->getTimestamp() ?? 0, $show->name])
                ->values(),
            'movies' => $user->movies()->orderBy('title')->get(),
            'followsPeople' => $followsPeople = $user->following()->exists(),
            // Until you follow someone: a few people to start with.
            'peopleSuggestions' => $followsPeople ? null : $people->suggestions($user, 3),
        ]);
    }

    /**
     * Premieres and the TMDB catalog on one page. The Livewire DiscoverPage does the work; this only turns
     * away URLs with filters that don't exist.
     */
    public function discover(Request $request): View
    {
        $request->validate([
            'when' => [Rule::in(['soon', 'new', 'any'])],
            'type' => [Rule::in(['movie', 'tv', 'people'])],
            'q' => ['nullable', 'string', 'max:100'],
            'service' => [Rule::in(array_keys(StreamingServices::all()))],
            'region' => [Rule::in(array_keys(Browse::regions()))],
            'from' => ['integer', 'between:1950,'.now()->year],
            'sort' => [Rule::in(array_keys(Browse::SORTS))],
            'min_rating' => [Rule::in(array_keys(Browse::MIN_RATINGS))],
            'page' => ['integer', 'between:1,500'],
        ]);

        return view('discover');
    }

    public function search(Request $request, Search $search, Tmdb $tmdb, Circle $circle): View
    {
        $query = trim((string) $request->query('q'));
        $onlyPeople = str_starts_with($query, '@');
        $results = $search->run($onlyPeople ? '' : $query);

        return view('search', [
            'query' => $query,
            'onlyPeople' => $onlyPeople,
            'people' => $search->people($request->user(), $query, 24),
            'shows' => $results['shows'],
            'movies' => $results['movies'],
            'searchErrors' => $results['errors'],
            'moviesEnabled' => $tmdb->configured(),
            'circle' => $circle->among($request->user(), tvmazeIds: array_column($results['shows'], 'tvmaze_id'), movieIds: array_column($results['movies'], 'tmdb_id')),
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
