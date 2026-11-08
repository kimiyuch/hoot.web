<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * "People you follow" on cards: for a page of shows and movies, who of the people you follow has each one in
 * their library (following it, a favourite, a status or a rating), with what they make of it. Only accepted
 * follows, nobody across a block and no suspended accounts, so it shows nothing their profile wouldn't.
 * Two queries for a whole page, however many cards.
 */
class Circle
{
    /**
     * @param  list<int>  $tvmazeIds  shows by TVmaze id (premieres, search)
     * @param  list<int>  $tmdbShowIds  shows by TMDB id (Discover → Any time)
     * @param  list<int>  $movieIds  movies by TMDB id
     * @return array{show: array<int, list<array{user: User, label: string}>>, tv: array<int, list<array{user: User, label: string}>>, movie: array<int, list<array{user: User, label: string}>>}
     */
    public function among(User $viewer, array $tvmazeIds = [], array $tmdbShowIds = [], array $movieIds = []): array
    {
        $found = ['show' => [], 'tv' => [], 'movie' => []];

        $people = User::query()->visibleTo($viewer)
            ->whereIn('users.id', DB::table('follows')->select('followed_id')->where('follower_id', $viewer->id)->whereNotNull('accepted_at'))
            ->get()->keyBy('id');

        if ($people->isEmpty()) {
            return $found;
        }

        $columns = ['user_id', 'following', 'favourite_position', 'status', 'rating'];

        if ($tvmazeIds || $tmdbShowIds) {
            $rows = DB::table('show_user')->join('shows', 'shows.id', '=', 'show_user.show_id')
                ->whereIn('show_user.user_id', $people->keys())
                ->where(fn ($query) => $query->whereIn('shows.tvmaze_id', $tvmazeIds)->orWhereIn('shows.tmdb_id', $tmdbShowIds))
                ->get([...array_map(fn ($column) => "show_user.{$column}", $columns), 'shows.tvmaze_id', 'shows.tmdb_id']);

            foreach ($rows as $row) {
                $entry = $this->entry($people[$row->user_id], $row, 'show');
                if (in_array($row->tvmaze_id, $tvmazeIds)) {
                    $found['show'][$row->tvmaze_id][] = $entry;
                }
                if ($row->tmdb_id !== null && in_array($row->tmdb_id, $tmdbShowIds)) {
                    $found['tv'][$row->tmdb_id][] = $entry;
                }
            }
        }

        if ($movieIds) {
            $rows = DB::table('movie_user')->join('movies', 'movies.id', '=', 'movie_user.movie_id')
                ->whereIn('movie_user.user_id', $people->keys())
                ->whereIn('movies.tmdb_id', $movieIds)
                ->get([...array_map(fn ($column) => "movie_user.{$column}", $columns), 'movies.tmdb_id']);

            foreach ($rows as $row) {
                $found['movie'][$row->tmdb_id][] = $this->entry($people[$row->user_id], $row, 'movie');
            }
        }

        // The ones with the most to say first: a favourite, then a rating, then a status, then just following.
        foreach ($found as $kind => $items) {
            $found[$kind] = array_map(fn (array $entries) => (new Collection($entries))->sortByDesc('weight')->values()->all(), $items);
        }

        return $found;
    }

    /** @return array{user: User, label: string, weight: int} */
    private function entry(User $user, object $row, string $type): array
    {
        $parts = array_filter([
            $row->favourite_position !== null ? 'Favourite' : null,
            $row->status ? Library::statuses($type)[$row->status] ?? null : null,
            $row->rating ? Library::stars($row->rating) : null,
        ]);

        return [
            'user' => $user,
            'label' => $parts ? implode(' · ', $parts) : 'Follows it',
            'weight' => ($row->favourite_position !== null ? 4 : 0) + ($row->rating ? 2 : 0) + ($row->status ? 1 : 0),
        ];
    }
}
