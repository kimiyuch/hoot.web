<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class InvitesTest extends TestCase
{
    use RefreshDatabase;

    private function person(string $username, array $attributes = []): User
    {
        return User::factory()->create(['name' => ucfirst($username), 'username' => $username, ...$attributes]);
    }

    private function signUp(string $username, ?string $then = null): User
    {
        $response = $this->post('/register', [
            'name' => ucfirst($username), 'username' => $username, 'email' => $username.'@example.com', 'password' => 'gumleaves-at-night', 'password_confirmation' => 'gumleaves-at-night',
        ]);

        if ($then) {
            // Where sign-up sends you; it waits for the email, and the confirmation link brings you back.
            $response->assertRedirect($then);
            $this->get($then)->assertRedirect(route('verification.notice'));
        }

        return User::where('username', $username)->sole();
    }

    private function confirm(User $user)
    {
        return $this->get(URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1($user->email)]));
    }

    public function test_signing_up_through_an_invite_follows_the_inviter_once_the_email_is_confirmed(): void
    {
        Notification::fake();
        $pip = $this->person('pip');

        $this->get('/invite/pip')->assertRedirect(route('register'));
        $this->get('/register')->assertOk()->assertSee('Join Pip on Hoot')->assertSee('(@pip) invited you', false);

        $kim = $this->signUp('kim', $pip->profileUrl());
        $this->assertTrue($kim->invitedBy->is($pip));
        $this->assertFalse($kim->isFollowing($pip), 'Not before the email is confirmed.');

        $this->confirm($kim)->assertRedirect($pip->profileUrl());
        $this->assertTrue($kim->fresh()->isFollowing($pip));
        $this->assertSame(1, Activity::where('user_id', $kim->id)->where('type', 'followed_user')->count());

        // The session forgets the invite: the next sign-up from here is a plain one.
        auth()->logout();
        $this->get('/register')->assertSee('Create your account');
    }

    public function test_an_invite_from_a_private_account_is_accepted_straight_away(): void
    {
        Notification::fake();
        $secret = $this->person('secret', ['is_private' => true]);

        $this->get('/invite/secret');
        $kim = $this->signUp('kim');
        $this->confirm($kim);

        $this->assertTrue($kim->fresh()->isFollowing($secret));
        $this->assertCount(0, $secret->followRequests()->get());
    }

    public function test_signed_in_an_invite_is_the_profile_and_broken_ones_are_404(): void
    {
        $pip = $this->person('pip');
        $this->get('/invite/nobody')->assertNotFound();
        $this->person('gone', ['suspended_at' => now()]);
        $this->get('/invite/gone')->assertNotFound();

        $this->actingAs($this->person('kim'))->get('/invite/pip')->assertRedirect($pip->profileUrl());
        $this->assertFalse(auth()->user()->isFollowing($pip));
    }

    public function test_the_link_shows_in_settings_and_on_discover_people(): void
    {
        $pip = $this->person('pip');

        $this->actingAs($pip)->get('/settings')->assertSee('Invite friends')->assertSee(url('/invite/pip'));
        $this->get('/discover?type=people')->assertSee('Invite a friend')->assertSee(url('/invite/pip'));
    }
}
