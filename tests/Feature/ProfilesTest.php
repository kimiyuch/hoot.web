<?php

namespace Tests\Feature;

use App\Models\Movie;
use App\Models\Show;
use App\Models\User;
use App\Services\Library;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfilesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_public_profile_shows_favourites_and_what_they_follow(): void
    {
        $pip = User::factory()->create(['name' => 'Pip', 'username' => 'pip', 'bio' => 'Late shows only.']);
        $library = app(Library::class);

        $wire = Show::create(['tvmaze_id' => 179, 'name' => 'The Wire']);
        $library->favourite($pip, $wire);
        $pip->shows()->attach(Show::create(['tvmaze_id' => 82, 'name' => 'Night Shift']));
        $library->favourite($pip, Movie::create(['tmdb_id' => 1, 'title' => 'Arrival']));

        $this->actingAs(User::factory()->create())->get('/@pip')->assertOk()
            ->assertSeeInOrder(['Pip', '@pip', '1 show · 0 movies followed', 'Late shows only.', 'Favourite shows', 'The Wire', 'Favourite movies', 'Arrival', 'Following', 'Night Shift'])
            ->assertDontSee('Edit profile')
            ->assertDontSee($pip->email);
    }

    public function test_a_private_profile_only_shows_the_name_to_others(): void
    {
        $pip = User::factory()->create(['name' => 'Pip', 'username' => 'pip', 'is_private' => true]);
        $pip->shows()->attach(Show::create(['tvmaze_id' => 82, 'name' => 'Night Shift']));

        $this->actingAs(User::factory()->create())->get('/@pip')->assertOk()
            ->assertSee('Pip’s profile is private.')->assertDontSee('Night Shift');

        $this->actingAs($pip)->get('/@pip')->assertOk()
            ->assertSee('Night Shift')->assertSee('Edit profile')->assertDontSee('aria-label="Follow @', false);
    }

    public function test_unknown_profiles_are_not_found_and_profiles_need_an_account(): void
    {
        $this->get('/@pip')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/@nobody')->assertNotFound();
    }

    public function test_accounts_from_before_profiles_pick_a_username_first(): void
    {
        $user = User::factory()->withoutUsername()->create(['name' => 'Rue Night']);
        User::factory()->create(['username' => 'taken']);

        $this->actingAs($user)->get('/for-you')->assertRedirect('/choose-username');
        $this->get('/choose-username')->assertOk()->assertSee('Hoot is getting social')->assertSee('value="rue_night"', false);

        $this->post('/choose-username', ['username' => 'taken', 'visibility' => 'public'])->assertSessionHasErrors(['username' => 'That username is taken. Pick another one.']);
        $this->post('/choose-username', ['username' => 'admin', 'visibility' => 'public'])->assertSessionHasErrors(['username' => 'That username is reserved. Pick another one.']);
        $this->post('/choose-username', ['username' => 'no spaces', 'visibility' => 'public'])->assertSessionHasErrors('username');

        $this->post('/choose-username', ['username' => '@Rue', 'visibility' => 'private'])->assertRedirect('/for-you');

        $this->assertSame('rue', $user->fresh()->username);
        $this->assertTrue($user->fresh()->is_private);
        $this->get('/for-you')->assertOk();
        $this->get('/choose-username')->assertRedirect('/for-you');
    }

    public function test_the_header_avatar_opens_a_menu_with_your_profile_settings_and_log_out(): void
    {
        $this->actingAs(User::factory()->create(['name' => 'Pip', 'username' => 'pip']))
            ->get('/for-you')->assertSeeInOrder([
                'aria-label="Your account"', '@pip',
                'href="'.url('/@pip').'"', 'Your profile',
                'href="'.route('settings').'"', 'Settings',
                'action="'.route('logout').'"', 'Log out',
            ], false);
    }
}
