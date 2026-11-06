<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Profiles at /@username: who someone is, their favourites, and what they follow.
 */
class ProfileController extends Controller
{
    public function show(Request $request, User $user): View
    {
        $visible = $user->profileVisibleTo($request->user());

        return view('profile', [
            'user' => $user,
            'own' => $request->user()->is($user),
            'visible' => $visible,
            'favouriteShows' => $visible ? $user->favouriteShows()->get() : collect(),
            'favouriteMovies' => $visible ? $user->favouriteMovies()->get() : collect(),
            'shows' => $visible ? $user->shows()->orderBy('name')->get() : collect(),
            'movies' => $visible ? $user->movies()->orderBy('title')->get() : collect(),
        ]);
    }
}
