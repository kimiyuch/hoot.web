<?php

namespace Tests\Feature;

use App\Livewire\PersonActions;
use App\Models\Activity;
use App\Models\Show;
use App\Models\User;
use App\Services\Activities;
use App\Services\Library;
use App\Services\Network;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PrivateAccountsTest extends TestCase
{
    use RefreshDatabase;

    private function person(string $username, array $attributes = []): User
    {
        return User::factory()->create(['name' => ucfirst($username), 'username' => $username, ...$attributes]);
    }

    public function test_accepting_a_request_lets_them_see_the_profile_and_is_news(): void
    {
        [$pip, $secret] = [$this->person('pip'), $this->person('secret', ['is_private' => true])];
        $network = app(Network::class);
        $network->follow($pip, $secret);

        $this->assertSame([], Activity::pluck('type')->all(), 'a request isn’t news');

        $this->actingAs($secret)->get('/@secret')->assertSee('1 follow request');
        $this->get('/settings')->assertOk()->assertSee('Follow requests')->assertSee('@pip');

        Livewire::actingAs($secret)->test(PersonActions::class, ['userId' => $pip->id, 'context' => 'requests'])
            ->call('accept')
            ->assertSet('answer', 'accepted')
            ->assertDispatched('toast', message: 'Pip follows you now.');

        $this->assertTrue($pip->isFollowing($secret));
        $this->assertSame(['followed_user'], Activity::where('user_id', $pip->id)->pluck('type')->all());
        $this->actingAs($pip)->get('/@secret')->assertOk()->assertDontSee('Secret’s profile is private.');
    }

    public function test_declining_or_taking_back_a_request(): void
    {
        [$pip, $rue, $secret] = [$this->person('pip'), $this->person('rue'), $this->person('secret', ['is_private' => true])];
        $network = app(Network::class);

        $network->follow($pip, $secret);
        Livewire::actingAs($secret)->test(PersonActions::class, ['userId' => $pip->id, 'context' => 'requests'])
            ->call('decline')
            ->assertSet('answer', 'declined');
        $this->assertFalse($pip->hasRequestedToFollow($secret));

        $network->follow($rue, $secret);
        Livewire::actingAs($rue)->test(PersonActions::class, ['userId' => $secret->id])
            ->assertSet('requested', true)
            ->call('unfollow')
            ->assertSet('requested', false)
            ->assertDispatched('toast', message: 'Took back your request to follow Secret.');
        $this->assertSame(0, $secret->followRequests()->count());

        // Only the private account answers its requests.
        $network->follow($rue, $secret);
        Livewire::actingAs($pip)->test(PersonActions::class, ['userId' => $rue->id])->call('accept')->assertForbidden();
    }

    public function test_going_public_accepts_the_waiting_requests(): void
    {
        [$pip, $secret] = [$this->person('pip'), $this->person('secret', ['is_private' => true])];
        app(Network::class)->follow($pip, $secret);

        $this->actingAs($secret)->put('/user/profile-information', [
            'name' => 'Secret', 'username' => 'secret', 'email' => $secret->email, 'visibility' => 'public',
        ])->assertSessionHasNoErrors();

        $this->assertTrue($pip->isFollowing($secret));
    }

    public function test_not_sharing_activity_keeps_it_out_of_the_thread(): void
    {
        [$me, $pip] = [$this->person('me'), $this->person('pip')];
        app(Network::class)->follow($me, $pip);
        $library = app(Library::class);
        $library->setStatus($pip, Show::create(['tvmaze_id' => 1, 'name' => 'Severance']), 'watching');

        $this->actingAs($pip)->put('/user/profile-information', [
            'name' => 'Pip', 'username' => 'pip', 'email' => $pip->email, 'visibility' => 'public', 'shares_activity' => '0',
        ])->assertSessionHasNoErrors();

        $this->assertFalse($pip->fresh()->shares_activity);
        $this->assertTrue(app(Activities::class)->thread($me)['entries']->isEmpty(), 'what was shared is hidden');

        $library->setStatus($pip->fresh(), Show::create(['tvmaze_id' => 2, 'name' => 'Andor']), 'watching');
        $this->assertSame(1, Activity::where('user_id', $pip->id)->count(), 'nothing new is noted');
    }

    public function test_a_suspended_account_is_signed_out_hidden_and_cant_log_in(): void
    {
        [$me, $pip] = [$this->person('me'), $this->person('pip', ['password' => 'secret-password'])];
        app(Network::class)->follow($me, $pip);

        $this->artisan('hoot:suspend', ['username' => '@pip'])->assertSuccessful();
        $this->assertTrue($pip->fresh()->isSuspended());

        $this->actingAs($pip->fresh())->get('/for-you')->assertRedirect(route('login'));
        $this->assertGuest();

        $this->post('/login', ['email' => $pip->email, 'password' => 'secret-password'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->actingAs($me)->get('/@pip')->assertNotFound();
        $this->assertFalse(app(Network::class)->canFollow($me, $pip->fresh()));
        $this->get('/search?q=pip')->assertOk()->assertDontSee('@pip');

        $this->artisan('hoot:suspend', ['username' => 'pip', '--lift' => true])->assertSuccessful();
        $this->get('/@pip')->assertOk();
    }

    public function test_the_privacy_page_the_export_and_report(): void
    {
        $this->get('/privacy')->assertOk()->assertSee('Privacy and rules')->assertSee('Who sees what');
        $this->get('/imprint')->assertOk()->assertSee('Letzigraben 149')->assertSee(route('privacy'));

        [$me, $pip] = [$this->person('me'), $this->person('pip')];
        app(Network::class)->follow($me, $pip);
        app(Library::class)->rate($me, Show::create(['tvmaze_id' => 1, 'name' => 'Severance']), 9);

        $export = $this->actingAs($me)->get('/settings/export')->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename="hoot-me-'.now()->format('Y-m-d').'.json"')
            ->json();
        $this->assertSame('me', $export['account']['username']);
        $this->assertSame(['Severance', 9], [$export['library'][0]['title'], $export['library'][0]['rating']]);
        $this->assertSame('pip', $export['following'][0]['username']);
        $this->assertSame(['followed_user', 'rated'], array_column($export['activity'], 'type'));
        $this->assertArrayNotHasKey('calendar_token', $export['account']);

        config(['hoot.report_email' => 'admin@example.com']);
        $this->get('/@pip')->assertSee('mailto:admin@example.com?subject=Report%3A%20%40pip', false);
    }
}
