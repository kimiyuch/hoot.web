<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Who to show in Discover → People: suggestions from what you have in common, the most followed, and the
 * newest. Only public accounts with a username, never yourself, never across a block. (Search, which finds
 * private accounts by name too, is in Search::people.)
 */
class People
{
    /**
     * People with shows and movies in common with you (followed or favourites), most in common first, that
     * you don't follow yet. Each has `shared` (how many) and `sharedTitles` (up to three).
     *
     * @return Collection<int, User>
     */
    public function suggestions(User $viewer, int $limit = 12): Collection
    {
        $counts = collect();
        $titles = [];

        foreach ([['show_user', 'show_id', 'shows', 'name'], ['movie_user', 'movie_id', 'movies', 'title']] as [$pivot, $key, $table, $title]) {
            $mine = DB::table($pivot)->where('user_id', $viewer->id)->select($key);

            $rows = DB::table($pivot)
                ->join($table, $table.'.id', '=', $pivot.'.'.$key)
                ->whereIn($pivot.'.'.$key, $mine)
                ->where($pivot.'.user_id', '!=', $viewer->id)
                ->get([$pivot.'.user_id', $pivot.'.favourite_position', $table.'.'.$title.' as title']);

            foreach ($rows as $row) {
                $counts[$row->user_id] = ($counts[$row->user_id] ?? 0) + 1;
                $titles[$row->user_id][] = ['favourite' => $row->favourite_position !== null, 'title' => $row->title];
            }
        }

        if ($counts->isEmpty()) {
            return new Collection;
        }

        $users = $this->discoverable($viewer)
            ->whereKey($counts->keys())
            ->whereNotIn('users.id', $viewer->following()->select('users.id'))
            ->get();

        return $users
            ->each(function (User $user) use ($counts, $titles) {
                $user->shared = $counts[$user->id];
                // Their favourites first, so the titles shown are the telling ones.
                $user->sharedTitles = collect($titles[$user->id])->sortByDesc('favourite')->pluck('title')->unique()->take(3)->values()->all();
            })
            ->sortBy([['shared', 'desc'], ['name', 'asc']])
            ->take($limit)
            ->values();
    }

    /** The most followed people (at least one follower). Each has `followers_count`. */
    public function popular(User $viewer, int $limit = 8): Collection
    {
        return $this->discoverable($viewer)
            ->whereHas('followers')
            ->withCount('followers')
            ->orderByDesc('followers_count')
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    /** The newest accounts that have confirmed their email. */
    public function newest(User $viewer, int $limit = 8): Collection
    {
        return $this->discoverable($viewer)
            ->whereNotNull('email_verified_at')
            ->latest()
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /** Public, with a username, not you, and no block either way. */
    private function discoverable(User $viewer): Builder
    {
        return User::visibleTo($viewer)
            ->where('is_private', false)
            ->whereKeyNot($viewer->id);
    }
}
