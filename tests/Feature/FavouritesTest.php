<?php

namespace Tests\Feature;

use App\Models\Movie;
use App\Models\Show;
use App\Models\User;
use App\Services\Library;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\TestCase;

class FavouritesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        Carbon::setTestNow('2026-11-05 12:00:00');
        Sleep::fake();
        Http::preventStrayRequests();
        config(['hoot.timezone' => 'Europe/Zurich', 'hoot.tmdb_token' => null]);
    }

    private function favourite(Show|Movie $item): void
    {
        $this->assertTrue(app(Library::class)->favourite($this->user, $item));
    }

    public function test_a_favourite_show_is_in_the_library_without_being_followed(): void
    {
        Http::fake(['api.tvmaze.com/shows/179*' => Http::response([
            'id' => 179, 'name' => 'The Wire', 'status' => 'Ended', 'network' => ['name' => 'HBO'],
            '_embedded' => ['episodes' => [['id' => 1, 'season' => 1, 'number' => 1, 'name' => 'The Target', 'airstamp' => '2002-06-03T01:00:00+00:00']]],
        ])]);

        $this->post('/favourites/shows', ['tvmaze_id' => 179])->assertSessionHas('status', 'The Wire is one of your favourites.');

        $show = Show::sole();
        $this->assertTrue($this->user->favouriteShows()->first()->is($show));
        $this->assertSame(0, $this->user->shows()->count());
        $this->assertSame(0, Show::followed()->count());

        $this->get('/for-you')->assertOk()->assertSee('Nothing followed yet');
        $this->get('/settings')->assertOk()->assertSeeInOrder(['Favourites', 'Shows', '1 of 10', 'The Wire', 'Movies', 'None yet']);
    }

    public function test_unfollowing_keeps_a_favourite_and_unfavouriting_then_removes_it(): void
    {
        $show = $this->user->shows()->create(['tvmaze_id' => 179, 'name' => 'The Wire']);
        $this->favourite($show);

        $this->delete("/shows/{$show->id}")->assertRedirect();
        $this->assertSame(0, $this->user->shows()->count());
        $this->assertSame(1, $this->user->favouriteShows()->count());

        $this->delete("/favourites/shows/{$show->id}")->assertSessionHas('status', 'Removed The Wire from your favourites.');
        $this->assertSame(0, Show::count());
    }

    public function test_unfavouriting_a_followed_show_keeps_following_it(): void
    {
        $show = $this->user->shows()->create(['tvmaze_id' => 179, 'name' => 'The Wire']);
        $this->favourite($show);

        $this->delete("/favourites/shows/{$show->id}");

        $this->assertSame(1, $this->user->shows()->count());
        $this->assertSame(0, $this->user->favouriteShows()->count());
    }

    public function test_favourites_can_be_reordered_and_removing_one_closes_the_gap(): void
    {
        $movies = collect(['Arrival', 'Heat', 'Alien'])->map(fn ($title, $i) => Movie::create(['tmdb_id' => $i + 1, 'title' => $title]));
        $movies->each(fn (Movie $movie) => $this->favourite($movie));

        $this->post("/favourites/movies/{$movies[2]->id}/move", ['direction' => 'up']);
        $this->assertSame(['Arrival', 'Alien', 'Heat'], $this->user->favouriteMovies()->pluck('title')->all());

        // The first one can't move further up.
        $this->post("/favourites/movies/{$movies[0]->id}/move", ['direction' => 'up']);
        $this->assertSame(['Arrival', 'Alien', 'Heat'], $this->user->favouriteMovies()->pluck('title')->all());

        $this->delete("/favourites/movies/{$movies[0]->id}");
        $this->assertSame([1, 2], $this->user->favouriteMovies()->get()->pluck('pivot.favourite_position')->all());
        $this->assertSame(['Alien', 'Heat'], $this->user->favouriteMovies()->pluck('title')->all());
    }

    public function test_there_are_at_most_ten_favourite_movies(): void
    {
        foreach (range(1, Library::MAX_FAVOURITES) as $id) {
            $this->favourite(Movie::create(['tmdb_id' => $id, 'title' => "Movie {$id}"]));
        }

        // Full: TMDB isn't even asked.
        $this->post('/favourites/movies', ['tmdb_id' => 999])
            ->assertSessionHas('status', 'You already have 10 favourite movies. Remove one in Settings first.');

        $this->assertSame(10, Movie::count());
    }

    public function test_the_refresh_leaves_favourite_only_shows_alone(): void
    {
        $show = Show::create(['tvmaze_id' => 179, 'name' => 'The Wire', 'refreshed_at' => now()->subMonth()]);
        $this->favourite($show);

        Http::fake(['api.tvmaze.com/updates/shows*' => Http::response([])]);

        $this->assertSame(0, app(Library::class)->refresh()['shows']);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'shows/179'));
    }

    public function test_cards_show_the_heart_filled_for_favourites(): void
    {
        $show = Show::create(['tvmaze_id' => 82, 'name' => 'Night Shift']);
        $this->favourite($show);

        Http::fake(['api.tvmaze.com/search/shows*' => Http::response([
            ['show' => ['id' => 82, 'name' => 'Night Shift', 'premiered' => '2024-04-01']],
            ['show' => ['id' => 83, 'name' => 'Night Owls', 'premiered' => '2025-01-01']],
        ])]);

        $this->get('/search?q=night')->assertOk()
            ->assertSee('Remove Night Shift from favourites')
            ->assertSee('Add Night Owls to favourites')
            ->assertSee(route('favourites.shows.destroy', $show), false);
    }

    public function test_deleting_the_account_removes_favourite_only_items_from_the_catalog(): void
    {
        $this->favourite(Movie::create(['tmdb_id' => 1, 'title' => 'Arrival']));

        $this->delete('/settings', ['password' => 'password'])->assertRedirect('/');

        $this->assertSame(0, Movie::count());
    }
}
