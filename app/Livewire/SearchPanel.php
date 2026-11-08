<?php

namespace App\Livewire;

use App\Services\Circle;
use App\Services\Search;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;

/**
 * Inside the search lightbox: shows, movies and people load as you type, and following or favouriting keeps it open.
 * The <dialog> around it is plain Blade, opened and closed by app.js.
 */
class SearchPanel extends Component
{
    public string $query = '';

    public function render(Search $search): View
    {
        $term = trim($this->query);
        $searching = mb_strlen($term) >= 2;
        // "@pip" only looks for people.
        $results = $searching && ! str_starts_with($term, '@') ? $search->run($term) : null;
        $people = $searching ? $search->people(auth()->user(), $term) : collect();

        return view('livewire.search-panel', [
            'term' => $term,
            'searchErrors' => $results['errors'] ?? [],
            'groups' => $searching ? ($results ? $this->groups($results) : []) : null,
            'people' => $people,
        ]);
    }

    /** At most 6 shows and 6 movies, each with what the heart and Follow button need. */
    private function groups(array $results): array
    {
        $user = auth()->user();
        $followedShows = $user->shows()->pluck('tvmaze_id')->flip();
        $followedMovies = $user->movies()->pluck('tmdb_id')->flip();
        $favouriteShows = $user->favouriteShows()->pluck('shows.id', 'tvmaze_id');
        $favouriteMovies = $user->favouriteMovies()->pluck('movies.id', 'tmdb_id');
        $shows = collect($results['shows'])->take(6);
        $movies = collect($results['movies'])->take(6);
        $circle = app(Circle::class)->among($user, tvmazeIds: $shows->pluck('tvmaze_id')->all(), movieIds: $movies->pluck('tmdb_id')->all());

        return array_filter([
            'Shows' => $shows->map(fn (array $show) => [
                'key' => 'show-'.$show['tvmaze_id'],
                'type' => 'show', 'tvmaze_id' => $show['tvmaze_id'], 'tmdb_id' => null,
                'title' => $show['name'], 'href' => $show['url'], 'image' => $show['image_url'],
                'meta' => collect([$show['year'], $show['channel'], $show['status'] === 'Ended' ? 'Ended' : null])->filter()->join(' · '),
                'following' => $followedShows->has($show['tvmaze_id']),
                'favourite' => $favouriteShows->get($show['tvmaze_id']),
                'friends' => $circle['show'][$show['tvmaze_id']] ?? [],
            ]),
            'Movies' => $movies->map(fn (array $movie) => [
                'key' => 'movie-'.$movie['tmdb_id'],
                'type' => 'movie', 'tvmaze_id' => null, 'tmdb_id' => $movie['tmdb_id'],
                'title' => $movie['title'], 'href' => 'https://www.themoviedb.org/movie/'.$movie['tmdb_id'],
                'image' => $movie['poster_path'] ? 'https://image.tmdb.org/t/p/w92'.$movie['poster_path'] : null,
                'meta' => $movie['year'],
                'following' => $followedMovies->has($movie['tmdb_id']),
                'favourite' => $favouriteMovies->get($movie['tmdb_id']),
                'friends' => $circle['movie'][$movie['tmdb_id']] ?? [],
            ]),
        ], fn (Collection $items) => $items->isNotEmpty());
    }
}
