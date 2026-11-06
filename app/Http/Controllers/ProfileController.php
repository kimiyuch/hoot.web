<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Profiles at /@username: who someone is, their favourites, what they follow, and who follows whom.
 */
class ProfileController extends Controller
{
    public function show(Request $request, User $user): View
    {
        $viewer = $request->user();
        $this->hideFromBlocked($viewer, $user);

        $visible = $user->profileVisibleTo($viewer);

        return view('profile', [
            'user' => $user,
            'own' => $viewer->is($user),
            'visible' => $visible,
            'blockedByYou' => $viewer->hasBlocked($user),
            'followsYou' => ! $viewer->is($user) && $viewer->isFollowedBy($user),
            'followerCount' => $user->followers()->count(),
            'followingCount' => $user->following()->count(),
            'favouriteShows' => $visible ? $user->favouriteShows()->get() : collect(),
            'favouriteMovies' => $visible ? $user->favouriteMovies()->get() : collect(),
            // Shows being watched, latest started first.
            'watching' => $visible ? $user->libraryShows()->wherePivot('status', 'watching')->orderByPivot('started_at', 'desc')->get() : collect(),
            // Shows and movies finished, latest first.
            'watched' => $visible ? $user->libraryShows()->wherePivot('status', 'watched')->get()
                ->concat($user->libraryMovies()->wherePivot('status', 'watched')->get())
                ->sortByDesc(fn ($item) => $item->pivot->finished_at)->take(12)->values() : collect(),
            // Want to watch, latest added first.
            'planned' => $visible ? $user->libraryShows()->wherePivot('status', 'plan')->get()
                ->concat($user->libraryMovies()->wherePivot('status', 'plan')->get())
                ->sortByDesc(fn ($item) => $item->pivot->created_at)->values() : collect(),
            'shows' => $visible ? $user->shows()->orderBy('name')->get() : collect(),
            'movies' => $visible ? $user->movies()->orderBy('title')->get() : collect(),
        ]);
    }

    public function followers(Request $request, User $user): View
    {
        return $this->people($request, $user, 'followers');
    }

    public function following(Request $request, User $user): View
    {
        return $this->people($request, $user, 'following');
    }

    /** Followers or Following: the people, newest follow first, minus anyone the viewer blocked or was blocked by. */
    private function people(Request $request, User $user, string $list): View
    {
        $viewer = $request->user();
        $this->hideFromBlocked($viewer, $user);

        $visible = $user->profileVisibleTo($viewer);
        $relation = $list === 'followers' ? $user->followers() : $user->following();

        return view('profile-people', [
            'user' => $user,
            'list' => $list,
            'own' => $viewer->is($user),
            'visible' => $visible,
            'followerCount' => $user->followers()->count(),
            'followingCount' => $user->following()->count(),
            'people' => $visible
                ? $relation->visibleTo($viewer)->orderByPivot('created_at', 'desc')->orderBy('users.id')->simplePaginate(50)
                : null,
        ]);
    }

    /** Someone who blocked you doesn't exist for you. */
    private function hideFromBlocked(User $viewer, User $user): void
    {
        abort_if($user->hasBlocked($viewer), 404);
    }
}
