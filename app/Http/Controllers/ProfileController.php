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
