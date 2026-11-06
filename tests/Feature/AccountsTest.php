<?php

namespace Tests\Feature;

use App\Models\Movie;
use App\Models\Show;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AccountsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        config(['possum.tmdb_token' => null]);
    }

    public function test_guests_see_the_landing_page_and_the_app_pages_ask_them_to_log_in(): void
    {
        $this->get('/')->assertOk()->assertSee('Never miss a')->assertSee('Create your account');

        foreach (['/discover', '/search', '/settings'] as $page) {
            $this->get($page)->assertRedirect('/login');
        }
        $this->post('/shows', ['tvmaze_id' => 1])->assertRedirect('/login');
    }

    public function test_the_auth_pages_render(): void
    {
        foreach (['/login', '/register', '/forgot-password', '/reset-password/some-token?email=a@b.ch'] as $page) {
            $this->get($page)->assertOk()->assertSee('Possum');
        }
    }

    public function test_signing_up_sends_a_confirmation_link_and_the_link_opens_for_you(): void
    {
        Notification::fake();

        $this->post('/register', [
            'name' => 'Pip', 'email' => 'Pip@Example.com', 'password' => 'gumleaves-at-night', 'password_confirmation' => 'gumleaves-at-night',
        ])->assertRedirect('/');

        $user = User::sole();
        $this->assertSame('pip@example.com', $user->email);
        $this->assertNotNull($user->calendar_token);
        Notification::assertSentTo($user, VerifyEmail::class);

        // Not confirmed yet: For you waits for the email.
        $this->get('/')->assertRedirect(route('verification.notice'));
        $this->get(route('verification.notice'))->assertOk()->assertSee('pip@example.com');

        $link = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1($user->email)]);
        $this->get($link)->assertRedirect('/?verified=1');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->get('/')->assertOk()->assertSee('Nothing followed yet');
    }

    public function test_logging_in_and_out(): void
    {
        $user = User::factory()->create(['email' => 'pip@example.com', 'password' => 'gumleaves-at-night']);

        $this->post('/login', ['email' => 'pip@example.com', 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post('/login', ['email' => 'PIP@example.com', 'password' => 'gumleaves-at-night'])->assertRedirect('/');
        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_a_forgotten_password_can_be_reset(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'pip@example.com']);

        $this->post('/forgot-password', ['email' => 'pip@example.com'])->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $this->post('/reset-password', [
                'token' => $notification->token, 'email' => $user->email,
                'password' => 'a-new-night-out', 'password_confirmation' => 'a-new-night-out',
            ])->assertRedirect('/login');

            return true;
        });

        $this->post('/login', ['email' => 'pip@example.com', 'password' => 'a-new-night-out'])->assertRedirect('/');
    }

    public function test_everyone_has_their_own_follows_and_calendar_feed(): void
    {
        [$pip, $rue] = User::factory()->count(2)->create();
        $show = Show::create(['tvmaze_id' => 1, 'name' => 'Shared Show']);
        $show->users()->attach([$pip->id, $rue->id]);
        $pip->movies()->create(['tmdb_id' => 1, 'title' => 'Only Pip’s Movie', 'digital_date' => now()->addWeek()]);

        $this->actingAs($rue)->get('/')->assertSee('Shared Show')->assertDontSee('Only Pip’s Movie');
        $this->get(route('calendar', $rue->calendar_token))->assertDontSee('Only Pip’s Movie');
        $this->get(route('calendar', $pip->calendar_token))->assertSee('Only Pip’s Movie');
        $this->get('/calendar/not-a-token.ics')->assertNotFound();

        // Rue unfollows; Pip still follows it, so it stays.
        $this->delete("/shows/{$show->id}")->assertRedirect();
        $this->assertTrue(Show::whereKey($show->id)->exists());
        $this->assertFalse($rue->shows()->exists());

        // Pip unfollows too; nobody follows it any more, so it's gone.
        $this->actingAs($pip)->delete("/shows/{$show->id}");
        $this->assertFalse(Show::whereKey($show->id)->exists());
    }

    public function test_settings_update_the_profile_and_password_and_make_a_new_calendar_link(): void
    {
        $user = User::factory()->create(['password' => 'gumleaves-at-night']);
        $token = $user->calendar_token;

        $this->actingAs($user)->get('/settings')->assertOk()->assertSee($user->calendarUrl());

        $this->from('/settings')->put('/user/profile-information', ['name' => 'Pip', 'email' => $user->email])->assertRedirect('/settings');
        $this->assertSame('Pip', $user->fresh()->name);

        $this->from('/settings')->put('/user/password', [
            'current_password' => 'gumleaves-at-night', 'password' => 'a-new-night-out', 'password_confirmation' => 'a-new-night-out',
        ])->assertRedirect('/settings')->assertSessionHasNoErrors();

        $this->from('/settings')->post('/settings/calendar')->assertRedirect('/settings');
        $this->assertNotSame($token, $user->fresh()->calendar_token);
        $this->get("/calendar/{$token}.ics")->assertNotFound();
    }

    public function test_deleting_an_account_needs_the_password_and_takes_its_follows_along(): void
    {
        $user = User::factory()->create(['password' => 'gumleaves-at-night']);
        $other = User::factory()->create();
        $user->movies()->create(['tmdb_id' => 1, 'title' => 'Only Mine']);
        $shared = $user->movies()->create(['tmdb_id' => 2, 'title' => 'Shared']);
        $shared->users()->attach($other);

        $this->actingAs($user)->from('/settings')->delete('/settings', ['password' => 'wrong'])
            ->assertSessionHasErrorsIn('deleteAccount', 'password');
        $this->assertModelExists($user);

        $this->delete('/settings', ['password' => 'gumleaves-at-night'])->assertRedirect('/');

        $this->assertGuest();
        $this->assertModelMissing($user);
        $this->assertSame(['Shared'], Movie::pluck('title')->all());
    }
}
