<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\Show;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Your account. Fortify handles the name, email and password forms; this is the page and the rest.
 */
class SettingsController extends Controller
{
    public function edit(Request $request): View
    {
        return view('settings', [
            'user' => $request->user(),
            'blocked' => $request->user()->blocked()->orderByPivot('created_at', 'desc')->get(),
        ]);
    }

    public function resetCalendar(Request $request): RedirectResponse
    {
        $request->user()->resetCalendarToken();

        return back()->with('status', 'New calendar link made. Subscribe again with it; the old one has stopped working.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('deleteAccount', ['password' => ['required', 'current_password']]);

        $user = $request->user();
        $shows = $user->libraryShows()->pluck('shows.id');
        $movies = $user->libraryMovies()->pluck('movies.id');

        Auth::logout();
        $user->delete();

        // What only this user had in their library leaves the catalog with them.
        Show::whereKey($shows)->doesntHave('users')->delete();
        Movie::whereKey($movies)->doesntHave('users')->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('status', 'Your account is deleted.');
    }
}
