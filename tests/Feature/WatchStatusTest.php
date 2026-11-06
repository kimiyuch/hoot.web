<?php

namespace Tests\Feature;

use App\Livewire\LibraryActions;
use App\Models\Movie;
use App\Models\Show;
use App\Models\User;
use App\Services\Library;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class WatchStatusTest extends TestCase
{
    use RefreshDatabase;

    private function row(User $user, Show|Movie $item): ?object
    {
        return ($item instanceof Show ? $user->libraryShows() : $user->libraryMovies())->find($item->id)?->pivot;
    }

    public function test_statuses_note_when_you_started_and_finished(): void
    {
        $user = User::factory()->create();
        $show = Show::create(['tvmaze_id' => 1, 'name' => 'Severance']);
        $library = app(Library::class);

        Carbon::setTestNow('2026-11-01 20:00:00');
        $this->assertTrue($library->setStatus($user, $show, 'watching'));
        $this->assertSame('watching', $this->row($user, $show)->status);
        $this->assertFalse((bool) $this->row($user, $show)->following, 'a status doesn’t follow it');

        Carbon::setTestNow('2026-11-06 22:00:00');
        $library->setStatus($user, $show, 'watched');
        $row = $this->row($user, $show);
        $this->assertStringStartsWith('2026-11-01', (string) $row->started_at);
        $this->assertStringStartsWith('2026-11-06', (string) $row->finished_at);

        $library->setStatus($user, $show, 'plan');
        $this->assertNull($this->row($user, $show)->started_at);
        $this->assertNull($this->row($user, $show)->finished_at);

        $this->assertFalse($library->setStatus($user, $show, 'binging'));
    }

    public function test_movies_only_have_want_to_watch_and_watched(): void
    {
        $user = User::factory()->create();
        $movie = Movie::create(['tmdb_id' => 1, 'title' => 'Arrival']);
        $library = app(Library::class);

        $this->assertFalse($library->setStatus($user, $movie, 'watching'));
        $this->assertTrue($library->setStatus($user, $movie, 'watched'));
        $this->assertSame(['plan' => 'Want to watch', 'watched' => 'Watched'], Library::statuses('movie'));
    }

    public function test_a_status_or_rating_keeps_the_row_and_clearing_everything_removes_it(): void
    {
        $user = User::factory()->create();
        $library = app(Library::class);
        $show = Show::create(['tvmaze_id' => 1, 'name' => 'Severance']);
        $user->shows()->attach($show);

        $library->rate($user, $show, 9);
        $library->unfollow($user, $show);
        $this->assertSame(9, $this->row($user, $show)->rating);
        $this->assertTrue(Show::whereKey($show->id)->exists());

        $this->assertFalse($library->rate($user, $show, 11));
        $library->rate($user, $show, null);

        $this->assertNull($this->row($user, $show));
        $this->assertFalse(Show::whereKey($show->id)->exists(), 'nobody has it any more');
    }

    public function test_the_menu_sets_status_and_rating_in_place(): void
    {
        $user = User::factory()->create();
        Show::create(['tvmaze_id' => 1, 'name' => 'Severance', 'refreshed_at' => now()]);

        Livewire::actingAs($user)->test(LibraryActions::class, ['type' => 'show', 'tvmazeId' => 1, 'label' => 'Severance'])
            ->assertSee('Status and rating for Severance')
            ->assertSee('Want to watch')->assertSee('Dropped')
            ->call('setStatus', 'watching')
            ->assertSet('status', 'watching')
            ->assertDispatched('toast', message: 'Severance: Watching.')
            ->call('rate', 7)
            ->assertSet('rating', 7)
            ->assertDispatched('toast', message: 'You rated Severance ★★★½.')
            ->assertSee('Watching, rated ★★★½ for Severance')
            ->call('setStatus', '')
            ->assertSet('status', null);

        // A new card for it starts with what's saved.
        Livewire::actingAs($user)->test(LibraryActions::class, ['type' => 'show', 'tvmazeId' => 1, 'label' => 'Severance'])
            ->assertSet('rating', 7)->assertSet('status', null);

        // Movies get the short list.
        Movie::create(['tmdb_id' => 5, 'title' => 'Arrival', 'refreshed_at' => now()]);
        Livewire::actingAs($user)->test(LibraryActions::class, ['type' => 'movie', 'tmdbId' => 5, 'label' => 'Arrival'])
            ->assertSee('Want to watch')->assertDontSee('Dropped');
    }

    public function test_a_card_can_unfollow_in_place_and_keeps_the_rest(): void
    {
        $user = User::factory()->create();
        $show = Show::create(['tvmaze_id' => 1, 'name' => 'Severance', 'refreshed_at' => now()]);
        $user->shows()->attach($show);
        app(Library::class)->rate($user, $show, 8);

        Livewire::actingAs($user)->test(LibraryActions::class, ['type' => 'show', 'tvmazeId' => 1, 'label' => 'Severance', 'following' => true])
            ->assertSee('Unfollow Severance')
            ->call('unfollow')
            ->assertSet('following', false)
            ->assertDispatched('toast', message: 'Stopped following Severance.')
            ->assertSee('Follow Severance');

        $this->assertSame(0, $user->shows()->count());
        $this->assertSame(8, $this->row($user, $show)->rating);
    }

    public function test_profiles_show_what_they_are_watching_and_what_they_watched_with_ratings(): void
    {
        $pip = User::factory()->create(['name' => 'Pip', 'username' => 'pip']);
        $library = app(Library::class);
        $library->setStatus($pip, Show::create(['tvmaze_id' => 1, 'name' => 'Severance']), 'watching');
        $arrival = Movie::create(['tmdb_id' => 1, 'title' => 'Arrival']);
        $library->setStatus($pip, $arrival, 'watched');
        $library->rate($pip, $arrival, 10);
        $library->setStatus($pip, Show::create(['tvmaze_id' => 2, 'name' => 'Someday']), 'plan');

        $this->actingAs(User::factory()->create())->get('/@pip')->assertOk()
            ->assertSeeInOrder(['Watching now', 'Severance', 'Watched lately', 'Arrival', '★★★★★', 'Want to watch', 'Someday']);

        $pip->update(['is_private' => true]);
        $this->get('/@pip')->assertDontSee('Watching now')->assertDontSee('Severance');
    }
}
