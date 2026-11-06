<?php

namespace Tests\Feature;

use App\Livewire\DiscoverPage;
use App\Models\Movie;
use App\Models\Show;
use App\Models\User;
use App\Services\Library;
use App\Services\Network;
use App\Services\People;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DiscoverPeopleTest extends TestCase
{
    use RefreshDatabase;

    private function person(string $username, array $attributes = []): User
    {
        return User::factory()->create(['name' => ucfirst($username), 'username' => $username, ...$attributes]);
    }

    public function test_suggestions_are_public_people_with_the_most_in_common_that_you_dont_follow(): void
    {
        $me = $this->person('me');
        $severance = Show::create(['tvmaze_id' => 1, 'name' => 'Severance']);
        $bear = Show::create(['tvmaze_id' => 2, 'name' => 'The Bear']);
        $arrival = Movie::create(['tmdb_id' => 1, 'title' => 'Arrival']);
        $me->shows()->attach([$severance->id, $bear->id]);
        app(Library::class)->favourite($me, $arrival);

        $pip = $this->person('pip');
        $pip->shows()->attach([$severance->id, $bear->id]);
        app(Library::class)->favourite($pip, $arrival);
        $rue = $this->person('rue');
        $rue->shows()->attach($bear->id);
        $secret = $this->person('secret', ['is_private' => true]);
        $secret->shows()->attach([$severance->id, $bear->id]);
        $followed = $this->person('followed');
        $followed->shows()->attach($severance->id);
        app(Network::class)->follow($me, $followed);
        $blocker = $this->person('blocker');
        $blocker->shows()->attach($severance->id);
        app(Network::class)->block($blocker, $me);
        $this->person('nothing_shared');

        $suggestions = app(People::class)->suggestions($me);

        $this->assertSame(['pip', 'rue'], $suggestions->pluck('username')->all());
        $this->assertSame(3, $suggestions[0]->shared);
        $this->assertSame('Arrival', $suggestions[0]->sharedTitles[0], 'favourites come first');
    }

    public function test_the_people_page_has_suggestions_popular_and_new_without_private_accounts(): void
    {
        $me = $this->person('me');
        $show = Show::create(['tvmaze_id' => 1, 'name' => 'Severance']);
        $me->shows()->attach($show->id);
        $pip = $this->person('pip');
        $pip->shows()->attach($show->id);
        $star = $this->person('star');
        $secret = $this->person('secret', ['is_private' => true]);
        app(Network::class)->follow($pip, $star);
        app(Network::class)->follow($me, $star);

        $this->actingAs($me)->get('/discover?type=people')->assertOk()
            ->assertSeeInOrder(['Follow what you follow', '@pip', '1 in common: Severance', 'Popular on Hoot', '@star', '2 followers', 'New on Hoot'])
            ->assertDontSee('@secret')
            ->assertDontSee('Premiering soon');

        // Search finds private accounts by name too.
        Livewire::actingAs($me)->withQueryParams(['type' => 'people'])->test(DiscoverPage::class)
            ->set('q', 'secr')
            ->assertSee('@secret')->assertDontSee('Popular on Hoot')
            ->set('q', 'zzz')
            ->assertSee('Nobody found for “zzz”.');
    }

    public function test_switching_away_from_people_drops_the_search(): void
    {
        Livewire::actingAs($this->person('me'))->withQueryParams(['type' => 'people', 'q' => 'pip'])->test(DiscoverPage::class)
            ->assertSet('q', 'pip')
            ->call('choose', 'type', 'movie')
            ->assertSet('q', '')
            ->assertSet('type', 'movie');
    }

    public function test_for_you_suggests_people_until_you_follow_someone(): void
    {
        $me = $this->person('me');
        $show = Show::create(['tvmaze_id' => 1, 'name' => 'Severance']);
        $me->shows()->attach($show->id);
        $pip = $this->person('pip');
        $pip->shows()->attach($show->id);

        $this->actingAs($me)->get('/for-you')->assertOk()
            ->assertSee('Find people')->assertSee('@pip')->assertSee('More people');

        app(Network::class)->follow($me, $pip);

        $this->get('/for-you')->assertOk()->assertDontSee('More people');
    }
}
