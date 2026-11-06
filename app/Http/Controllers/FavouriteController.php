<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\Show;
use App\Services\Library;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Favourites: the shows and movies that are part of your taste. Independent of following.
 */
class FavouriteController extends Controller
{
    public function storeShow(Request $request, Library $library): RedirectResponse
    {
        $ids = $request->validate([
            'tvmaze_id' => ['required_without:tmdb_id', 'integer', 'min:1'],
            'tmdb_id' => ['required_without:tvmaze_id', 'integer', 'min:1'],
        ]);

        if (! $library->hasRoomForFavourite($request->user(), Show::class)) {
            return $this->full('shows');
        }

        try {
            $show = isset($ids['tvmaze_id']) ? $library->show($ids['tvmaze_id']) : $library->tmdbShow($ids['tmdb_id']);
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('status', 'That show couldn’t be loaded. Try again in a moment.');
        }

        if (! $show) {
            return back()->with('status', 'TVmaze doesn’t have that show, so it can’t be added.');
        }

        return $library->favourite($request->user(), $show)
            ? back()->with('status', "{$show->name} is one of your favourites.")
            : $this->full('shows');
    }

    public function storeMovie(Request $request, Library $library): RedirectResponse
    {
        $id = $request->validate(['tmdb_id' => ['required', 'integer', 'min:1']])['tmdb_id'];

        if (! $library->hasRoomForFavourite($request->user(), Movie::class)) {
            return $this->full('movies');
        }

        try {
            $movie = $library->movie($id);
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('status', 'That movie couldn’t be loaded from TMDB. Try again in a moment.');
        }

        return $library->favourite($request->user(), $movie)
            ? back()->with('status', "{$movie->title} is one of your favourites.")
            : $this->full('movies');
    }

    public function destroyShow(Request $request, Show $show, Library $library): RedirectResponse
    {
        $library->unfavourite($request->user(), $show);

        return back()->with('status', "Removed {$show->name} from your favourites.");
    }

    public function destroyMovie(Request $request, Movie $movie, Library $library): RedirectResponse
    {
        $library->unfavourite($request->user(), $movie);

        return back()->with('status', "Removed {$movie->title} from your favourites.");
    }

    public function moveShow(Request $request, Show $show, Library $library): RedirectResponse
    {
        $library->moveFavourite($request->user(), $show, $this->direction($request));

        return back();
    }

    public function moveMovie(Request $request, Movie $movie, Library $library): RedirectResponse
    {
        $library->moveFavourite($request->user(), $movie, $this->direction($request));

        return back();
    }

    private function direction(Request $request): int
    {
        return $request->validate(['direction' => ['required', 'in:up,down']])['direction'] === 'up' ? -1 : 1;
    }

    private function full(string $kind): RedirectResponse
    {
        return back()->with('status', 'You already have '.Library::MAX_FAVOURITES." favourite {$kind}. Remove one in Settings first.");
    }
}
