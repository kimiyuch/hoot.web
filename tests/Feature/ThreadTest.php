<?php

namespace Tests\Feature;

use App\Livewire\LibraryActions;
use App\Livewire\Thread;
use App\Models\Activity;
use App\Models\Movie;
use App\Models\Show;
use App\Models\User;
use App\Services\Activities;
use App\Services\Library;
use App\Services\Network;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class ThreadTest extends TestCase
{
    use RefreshDatabase;

    private function person(string $username, array $attributes = []): User
    {
        return User::factory()->create(['name' => ucfirst($username), 'username' => $username, ...$attributes]);
    }

    private function show(int $tvmazeId, string $name): Show
    {
        return Show::create(['tvmaze_id' => $tvmazeId, 'name' => $name, 'url' => "https://www.tvmaze.com/shows/{$tvmazeId}", 'refreshed_at' => now()]);
    }

    private function movie(int $tmdbId, string $title): Movie
    {
        return Movie::create(['tmdb_id' => $tmdbId, 'title' => $title, 'refreshed_at' => now()]);
    }

    /** @return list<array{0: string, 1: list<string>}> each entry as [type, titles] */
    private function entries(User $user): array
    {
        return Activity::where('user_id', $user->id)->orderBy('id')->get()
            ->map(fn (Activity $activity) => [$activity->type, array_column($activity->items(), 'title')])->all();
    }

    public function test_following_shows_in_a_few_minutes_is_one_entry_and_unfollowing_soon_takes_it_back(): void
    {
        $pip = $this->person('pip');
        $library = app(Library::class);
        [$severance, $bear, $andor] = [$this->show(1, 'Severance'), $this->show(2, 'The Bear'), $this->show(3, 'Andor')];

        Carbon::setTestNow('2026-11-06 20:00:00');
        $library->followShow($pip, 1);
        Carbon::setTestNow('2026-11-06 20:05:00');
        $library->followShow($pip, 2);
        $library->followShow($pip, 2);
        $library->followMovie($pip, $this->movie(10, 'Arrival')->tmdb_id);

        $this->assertSame([['followed', ['Severance', 'The Bear', 'Arrival']]], $this->entries($pip));

        $library->unfollow($pip, $bear);
        $this->assertSame([['followed', ['Severance', 'Arrival']]], $this->entries($pip));

        Carbon::setTestNow('2026-11-06 21:00:00');
        $library->unfollow($pip, $severance);
        $this->assertSame([['followed', ['Severance', 'Arrival']]], $this->entries($pip), 'an old entry stays; unfollowing is never news');

        $library->followShow($pip, 3);
        $this->assertSame([['followed', ['Severance', 'Arrival']], ['followed', ['Andor']]], $this->entries($pip));
        $this->assertFalse(Show::whereKey($severance->id)->exists(), 'nobody has it any more');
    }

    public function test_starting_finishing_and_rating(): void
    {
        $pip = $this->person('pip');
        $library = app(Library::class);
        $show = $this->show(1, 'Severance');

        Carbon::setTestNow('2026-11-01 20:00:00');
        $library->setStatus($pip, $show, 'watching');
        $library->setStatus($pip, $show, 'watching');
        $this->assertSame([['started', ['Severance']]], $this->entries($pip));

        Carbon::setTestNow('2026-11-06 20:00:00');
        $library->setStatus($pip, $show, 'watched');
        $library->rate($pip, $show, 8);
        $this->assertSame([['started', ['Severance']], ['finished', ['Severance']]], $this->entries($pip));
        $this->assertSame(8, Activity::where('type', 'finished')->first()->items()[0]['rating'], 'a rating right after finishing joins the entry');

        $library->rate($pip, $show, null);
        $this->assertArrayNotHasKey('rating', Activity::where('type', 'finished')->first()->items()[0]);

        // Changing your mind within minutes takes it back.
        $library->setStatus($pip, $show, 'paused');
        $this->assertSame([['started', ['Severance']]], $this->entries($pip));

        Carbon::setTestNow('2026-11-08 20:00:00');
        $library->rate($pip, $show, 9);
        $library->rate($pip, $show, 7);
        $this->assertSame([['started', ['Severance']], ['rated', ['Severance']]], $this->entries($pip), 're-rating replaces the entry');
        $this->assertSame(7, Activity::where('type', 'rated')->first()->items()[0]['rating']);
    }

    public function test_favourites_and_their_undo(): void
    {
        $pip = $this->person('pip');
        $library = app(Library::class);
        $wire = $this->show(1, 'The Wire');

        $library->favourite($pip, $wire);
        $library->favourite($pip, $wire);
        $this->assertSame([['favourited', ['The Wire']]], $this->entries($pip));

        $library->unfavourite($pip, $wire);
        $this->assertSame([], $this->entries($pip));
    }

    public function test_following_people_is_news_and_blocking_forgets_it(): void
    {
        [$pip, $rue, $max] = [$this->person('pip'), $this->person('rue'), $this->person('max')];
        $network = app(Network::class);

        $network->follow($pip, $rue);
        $network->follow($pip, $rue);
        $network->follow($pip, $max);
        $this->assertSame([['followed_user', ['Rue', 'Max']]], $this->entries($pip));

        $network->unfollow($pip, $max);
        $this->assertSame([['followed_user', ['Rue']]], $this->entries($pip));

        Carbon::setTestNow(now()->addHour());
        $network->block($rue, $pip);
        $this->assertSame([], $this->entries($pip));
    }

    public function test_the_thread_shows_only_people_you_follow_and_respects_blocks_and_private_accounts(): void
    {
        [$me, $pip, $rue, $stranger] = [$this->person('me'), $this->person('pip'), $this->person('rue'), $this->person('stranger')];
        $secret = $this->person('secret');
        $network = app(Network::class);
        $library = app(Library::class);
        $activities = app(Activities::class);

        $network->follow($me, $pip);
        $network->follow($me, $rue);
        $library->setStatus($pip, $this->show(1, 'Severance'), 'watching');
        $library->setStatus($stranger, $this->show(2, 'Andor'), 'watching');

        Carbon::setTestNow(now()->addHour());
        $network->follow($pip, $me);
        $network->follow($pip, $secret);
        $secret->update(['is_private' => true]);

        $thread = $activities->thread($me);
        $this->assertSame(['pip', 'pip'], $thread['entries']->map(fn (Activity $activity) => $activity->user->username)->all());
        $this->assertSame(['Me'], array_column($thread['entries'][0]->items(), 'title'), 'a private account you don’t follow is left out');

        $this->actingAs($me)->get('/for-you')->assertOk()
            ->assertSee('From people you follow')
            ->assertSeeInOrder(['Pip', 'followed', 'you', 'Pip', 'started watching', 'Severance']);

        $network->block($me, $pip);
        $this->assertTrue($activities->thread($me)['entries']->isEmpty());
    }

    public function test_a_profile_shows_what_that_person_did_lately(): void
    {
        [$me, $pip, $secret] = [$this->person('me'), $this->person('pip'), $this->person('secret', ['is_private' => true])];
        $library = app(Library::class);
        $activities = app(Activities::class);

        $library->setStatus($pip, $this->show(1, 'Severance'), 'watching');
        $library->setStatus($secret, $this->show(2, 'Andor'), 'watching');

        // Public: anyone sees it, without following.
        $this->actingAs($me)->get('/@pip')->assertOk()->assertSee('Lately')->assertSeeInOrder(['Pip', 'started watching', 'Severance']);
        // Private and not followed: nothing, not even the heading.
        $this->assertTrue($activities->of($secret, $me)['entries']->isEmpty());
        $this->get('/@secret')->assertDontSee('Lately');
        // Not sharing: gone, on your own profile too.
        $pip->update(['shares_activity' => false]);
        $this->get('/@pip')->assertDontSee('Lately');
        $this->actingAs($pip)->get('/@pip')->assertDontSee('Lately');
        // Nothing done yet: no section.
        $this->actingAs($me)->get('/@me')->assertOk()->assertDontSee('Lately');

        Livewire::actingAs($me)->test(Thread::class, ['userId' => $secret->id])->assertDontSee('Andor');

        // Five at a time on a profile.
        $pip->update(['shares_activity' => true]);
        foreach (range(10, 16) as $id) {
            $this->travel(1)->hours();
            $library->setStatus($pip->fresh(), $this->show($id, "Show {$id}"), 'watching');
        }
        Livewire::actingAs($me)->test(Thread::class, ['userId' => $pip->id])
            ->assertSee('Show 16')->assertSee('Show 12')->assertDontSee('Show 11')->assertSee('Load more')
            ->call('loadMore')->assertSee('Show 11')->assertSee('Show 10');
    }

    public function test_entries_outlive_the_catalog_and_load_more(): void
    {
        [$me, $pip] = [$this->person('me'), $this->person('pip')];
        app(Network::class)->follow($me, $pip);
        $library = app(Library::class);

        foreach (range(1, Thread::PAGE + 2) as $id) {
            Carbon::setTestNow(now()->addMinutes(20));
            $library->setStatus($pip, $this->show($id, 'Show '.$id), 'watching');
        }

        $show = $this->show(100, 'Gone Soon');
        $library->setStatus($pip, $show, 'watching');
        Carbon::setTestNow(now()->addHour());
        $library->setStatus($pip, $show, null);
        $this->assertFalse(Show::whereKey($show->id)->exists());

        Livewire::actingAs($me)->test(Thread::class)
            ->assertSee('Gone Soon')
            ->assertSee('Load more')
            ->call('loadMore')
            ->assertSet('limit', Thread::PAGE * 2);
    }

    public function test_shows_and_movies_in_the_thread_can_be_followed_from_there(): void
    {
        [$me, $pip] = [$this->person('me'), $this->person('pip')];
        app(Network::class)->follow($me, $pip);
        $library = app(Library::class);
        $this->show(1, 'Severance');
        $this->show(2, 'Andor');

        Carbon::setTestNow(now()->addHour());
        $library->setStatus($pip, Show::where('tvmaze_id', 1)->first(), 'watching');
        Carbon::setTestNow(now()->addHour());
        $library->followShow($pip, 1);
        $library->followShow($pip, 2);
        $library->followShow($me, 2);

        Livewire::actingAs($me)->test(Thread::class)
            ->assertSeeHtml('title="Follow Severance"')
            ->assertSee('Show all 2')
            ->assertSeeHtml('title="Unfollow Andor"')
            ->assertDontSeeHtml('favourites"');

        Livewire::actingAs($me)->test(LibraryActions::class, ['type' => 'show', 'tvmazeId' => 1, 'label' => 'Severance', 'withHeart' => false, 'withStatus' => false])
            ->call('follow')
            ->assertSet('following', true);

        $this->assertTrue($me->shows()->where('tvmaze_id', 1)->exists());
    }
}
