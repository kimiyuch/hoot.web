<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Network;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    use RefreshDatabase;

    private function person(string $username, array $attributes = []): User
    {
        return User::factory()->create(['name' => ucfirst($username), 'username' => $username, ...$attributes]);
    }

    /** @return list<string> "kind:username", newest first */
    private function news(User $user): array
    {
        return $user->notifications()->latest()->get()
            ->map(fn ($notification) => $notification->data['kind'].':'.User::find($notification->data['user_id'])?->username)->all();
    }

    public function test_follows_requests_and_answers_are_news_and_undoing_them_takes_it_back(): void
    {
        [$me, $pip, $secret] = [$this->person('me'), $this->person('pip'), $this->person('secret', ['is_private' => true])];
        $network = app(Network::class);

        $network->follow($pip, $me);
        $this->assertSame(['followed:pip'], $this->news($me));
        $network->unfollow($pip, $me);
        $this->assertSame([], $this->news($me), 'unfollowing takes it back');

        $network->follow($me, $secret);
        $this->assertSame(['requested:me'], $this->news($secret));
        $network->accept($secret, $me);
        $this->assertSame([], $this->news($secret), 'a request dealt with is gone');
        $this->assertSame(['accepted:secret'], $this->news($me));

        $network->follow($pip, $secret);
        $network->decline($secret, $pip);
        $this->assertSame([], $this->news($secret));

        $network->follow($pip, $me);
        $network->block($me, $pip);
        $this->assertSame(['accepted:secret'], $this->news($me), 'blocking forgets everything between the two');
    }

    public function test_the_page_lists_them_marks_them_read_and_the_menu_counts_them(): void
    {
        [$me, $pip, $rue] = [$this->person('me', ['is_private' => true]), $this->person('pip'), $this->person('rue')];
        $network = app(Network::class);
        $network->follow($pip, $me);
        $this->travel(1)->minutes();
        $network->follow($rue, $me, invited: true);

        $this->actingAs($me)->get('/for-you')->assertSee('2 new notifications');

        $this->get('/notifications')->assertOk()
            ->assertSeeInOrder(['Rue', 'joined Hoot through your invite and follows you', 'Pip', 'asked to follow you', 'Accept'])
            ->assertSee('New');
        $this->assertSame(0, $me->unreadNotifications()->count());
        $this->get('/for-you')->assertDontSee('new notification');

        // Someone who's blocked or gone isn't listed.
        $network->block($me, $rue);
        $this->get('/notifications')->assertDontSee('Rue');
        $this->actingAs($pip)->delete('/settings', ['password' => 'password']);
        $this->assertSame(0, $me->notifications()->count());
    }
}
