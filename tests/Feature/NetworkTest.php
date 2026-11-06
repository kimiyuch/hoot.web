<?php

namespace Tests\Feature;

use App\Livewire\PersonActions;
use App\Livewire\SearchPanel;
use App\Models\Show;
use App\Models\User;
use App\Services\Network;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NetworkTest extends TestCase
{
    use RefreshDatabase;

    private function person(string $username, array $attributes = []): User
    {
        return User::factory()->create(['name' => ucfirst($username), 'username' => $username, ...$attributes]);
    }

    public function test_following_and_unfollowing_someone_from_their_profile(): void
    {
        $pip = $this->person('pip');
        $rue = $this->person('rue');

        $this->actingAs($rue)->get('/@pip')->assertOk()
            ->assertSeeInOrder(['0', 'followers', '0', 'following'])
            ->assertSee('aria-label="Follow @pip"', false);

        Livewire::actingAs($rue)->test(PersonActions::class, ['userId' => $pip->id, 'context' => 'profile'])
            ->call('follow')
            ->assertSet('following', true)
            ->assertDispatched('toast', message: 'Following Pip.')
            ->assertSee('Unfollow @pip');

        $this->assertTrue($rue->isFollowing($pip));
        $this->assertFalse($pip->isFollowing($rue));
        $this->get('/@pip')->assertSeeInOrder(['1', 'follower', '0', 'following']);
        $this->actingAs($pip)->get('/@rue')->assertSee('Follows you');

        Livewire::actingAs($rue)->test(PersonActions::class, ['userId' => $pip->id, 'context' => 'profile'])
            ->call('unfollow')
            ->assertSet('following', false);

        $this->assertFalse($rue->isFollowing($pip));
    }

    public function test_you_cant_follow_yourself_or_a_private_account_yet(): void
    {
        $pip = $this->person('pip');
        $secret = $this->person('secret', ['is_private' => true]);
        $network = app(Network::class);

        $this->assertFalse($network->follow($pip, $pip));
        $this->assertFalse($network->follow($pip, $secret));
        $this->assertSame(0, $pip->following()->count());

        $this->actingAs($pip)->get('/@secret')->assertOk()
            ->assertSee('Secret’s profile is private.')
            ->assertDontSee('aria-label="Follow @secret"', false)
            ->assertDontSee(route('profile.followers', $secret));

        Livewire::actingAs($pip)->test(PersonActions::class, ['userId' => $secret->id])
            ->call('follow')
            ->assertSet('following', false)
            ->assertDispatched('toast', message: 'You can’t follow Secret.');
    }

    public function test_someone_who_followed_before_an_account_went_private_still_sees_it(): void
    {
        $pip = $this->person('pip');
        $rue = $this->person('rue');
        $max = $this->person('max');
        $pip->shows()->attach(Show::create(['tvmaze_id' => 82, 'name' => 'Night Shift']));
        app(Network::class)->follow($rue, $pip);

        $pip->update(['is_private' => true]);

        $this->actingAs($rue)->get('/@pip')->assertSee('Night Shift');
        $this->actingAs($rue)->get('/@pip/followers')->assertOk()->assertSee('@rue');
        $this->actingAs($max)->get('/@pip')->assertDontSee('Night Shift');
        $this->actingAs($max)->get('/@pip/followers')->assertOk()->assertSee('Pip’s profile is private.')->assertDontSee('@rue');
    }

    public function test_the_followers_and_following_lists(): void
    {
        $pip = $this->person('pip');
        $rue = $this->person('rue');
        $max = $this->person('max');
        $network = app(Network::class);
        $network->follow($rue, $pip);
        $network->follow($max, $pip);
        $network->follow($pip, $max);

        $this->actingAs($this->person('eve'))->get('/@pip/followers')->assertOk()
            ->assertSee('Followers')->assertSee('@rue')->assertSee('@max')
            ->assertDontSee('wire:click="removeFollower"', false);
        $this->get('/@pip/following')->assertOk()->assertSee('@max')->assertDontSee('@rue');
        $this->get('/@rue/following')->assertOk()->assertSee('@pip');
        $this->get('/@eve/following')->assertOk()->assertSee('You don’t follow anyone yet.');
        $this->get('/@eve/followers')->assertOk()->assertSee('Nobody follows you yet.');

        // Your own followers list can remove someone.
        $this->actingAs($pip)->get('/@pip/followers')->assertSee('wire:click="removeFollower"', false);

        Livewire::actingAs($pip)->test(PersonActions::class, ['userId' => $rue->id, 'context' => 'followers'])
            ->call('removeFollower')
            ->assertSet('removed', true);

        $this->assertFalse($rue->isFollowing($pip));
        $this->assertTrue($max->isFollowing($pip));

        // Elsewhere it can't.
        Livewire::actingAs($pip)->test(PersonActions::class, ['userId' => $max->id, 'context' => 'list'])
            ->call('removeFollower')
            ->assertForbidden();
    }

    public function test_blocking_removes_follows_both_ways_and_hides_each_from_the_other(): void
    {
        $pip = $this->person('pip');
        $rue = $this->person('rue');
        $max = $this->person('max');
        $network = app(Network::class);
        $network->follow($rue, $pip);
        $network->follow($pip, $rue);
        $network->follow($max, $pip);
        $network->follow($rue, $max);

        Livewire::actingAs($pip)->test(PersonActions::class, ['userId' => $rue->id, 'context' => 'profile'])
            ->call('block')
            ->assertRedirect('/@rue');

        $this->assertTrue($pip->hasBlocked($rue));
        $this->assertFalse($rue->isFollowing($pip));
        $this->assertFalse($pip->isFollowing($rue));
        $this->assertFalse($network->follow($rue, $pip));
        $this->assertFalse($network->follow($pip, $rue));

        // The blocked one can't find the blocker at all.
        $this->actingAs($rue)->get('/@pip')->assertNotFound();
        $this->actingAs($rue)->get('/@pip/followers')->assertNotFound();
        $this->actingAs($rue)->get('/@max/followers')->assertOk()->assertDontSee('@pip');

        // The blocker sees who it is, and Unblock, but nothing of theirs.
        $this->actingAs($pip)->get('/@rue')->assertOk()
            ->assertSee('You blocked @rue.')->assertSee('aria-label="Unblock @rue"', false);
        $this->actingAs($pip)->get('/@rue/followers')->assertOk()->assertSee('Rue’s profile is private.');
        $this->actingAs($pip)->get('/@max/following')->assertOk()->assertDontSee('@rue');
        $this->actingAs($pip)->get('/settings')->assertSee('Blocked people')->assertSee('@rue');

        // Unblocking doesn't bring the follows back.
        Livewire::actingAs($pip)->test(PersonActions::class, ['userId' => $rue->id, 'context' => 'blocked'])
            ->call('unblock')
            ->assertSet('blocked', false)
            ->assertSee('Unblocked');

        $this->assertFalse($pip->hasBlocked($rue));
        $this->assertFalse($rue->isFollowing($pip));
        $this->actingAs($rue)->get('/@pip')->assertOk();
    }

    public function test_people_in_search_leave_out_blocks_and_accounts_without_a_username(): void
    {
        $viewer = $this->person('viewer');
        $this->person('pip_owl', ['name' => 'Pip Owl']);
        $this->person('pipster', ['name' => 'Someone']);
        $this->person('rue', ['name' => 'Pippa Rue']);
        $blocker = $this->person('pip_blocker', ['name' => 'Blocker']);
        $blocked = $this->person('pip_blocked', ['name' => 'Blocked']);
        User::factory()->withoutUsername()->create(['name' => 'Pip Nobody']);
        app(Network::class)->block($blocker, $viewer);
        app(Network::class)->block($viewer, $blocked);

        Livewire::actingAs($viewer)->test(SearchPanel::class)
            ->set('query', '@pip')
            ->assertSeeInOrder(['People', '@pip_owl', '@pipster', '@rue'])
            ->assertDontSee('@pip_blocker')->assertDontSee('@pip_blocked')->assertDontSee('Pip Nobody')
            ->assertDontSee('Shows');

        // A % in the term is a character, not a wildcard.
        Livewire::actingAs($viewer)->test(SearchPanel::class)
            ->set('query', '@%%')
            ->assertSee('Nothing found');

        $this->actingAs($viewer)->get('/search?q=@pip')->assertOk()
            ->assertSee('@pip_owl')->assertDontSee('@pip_blocker')->assertDontSee('results-shows', false);
    }

    public function test_deleting_an_account_deletes_its_follows_and_blocks(): void
    {
        $pip = $this->person('pip');
        $rue = $this->person('rue');
        $network = app(Network::class);
        $network->follow($rue, $pip);
        $network->follow($pip, $rue);
        $network->block($pip, $this->person('max'));

        $this->actingAs($pip)->delete('/settings', ['password' => 'password'])->assertRedirect('/');

        $this->assertDatabaseCount('follows', 0);
        $this->assertDatabaseCount('blocks', 0);
    }
}
