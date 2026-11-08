<?php

namespace Tests\Feature;

use App\Models\Show;
use App\Models\User;
use App\Notifications\WeeklyDigest;
use App\Services\Library;
use App\Services\Network;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DigestTest extends TestCase
{
    use RefreshDatabase;

    private function person(string $username, array $attributes = []): User
    {
        return User::factory()->create(['name' => ucfirst($username), 'username' => $username, ...$attributes]);
    }

    public function test_the_weekly_mail_goes_only_to_those_who_want_it_and_have_something_to_hear(): void
    {
        Carbon::setTestNow('2026-11-09 07:00:00'); // a Monday
        [$me, $quiet, $off, $pip] = [$this->person('me', ['weekly_digest' => true]), $this->person('quiet', ['weekly_digest' => true]), $this->person('off'), $this->person('pip')];

        $show = Show::create(['tvmaze_id' => 1, 'name' => 'Severance', 'channel' => 'Apple TV', 'refreshed_at' => now()]);
        $show->episodes()->create(['tvmaze_id' => 11, 'season' => 2, 'number' => 5, 'airs_at' => now()->addDays(2)->setTime(1, 0)]);
        $show->episodes()->create(['tvmaze_id' => 12, 'season' => 2, 'number' => 6, 'airs_at' => now()->addDays(9)]);
        app(Library::class)->followShow($me, 1);
        app(Library::class)->followShow($off, 1);
        app(Network::class)->follow($pip, $me);
        Notification::fake();

        $this->artisan('hoot:digest')->expectsOutputToContain('Sent 1 of 2')->assertSuccessful();

        Notification::assertSentTo($me, WeeklyDigest::class, function (WeeklyDigest $digest) use ($me) {
            $html = (string) $digest->toMail($me)->render();

            return str_contains($html, 'Wed 11 Nov') && str_contains($html, 'Severance S02E05 · Apple TV, 02:00')
                && ! str_contains($html, 'S02E06') && str_contains($html, 'New followers:</strong> Pip');
        });
        Notification::assertNotSentTo([$quiet, $off], WeeklyDigest::class);
    }

    public function test_switching_it_on_in_settings(): void
    {
        $me = $this->person('me');
        $this->actingAs($me)->get('/settings')->assertSee('Weekly mail');
        $this->put('/settings/mail', ['weekly_digest' => '1'])->assertRedirect();
        $this->assertTrue($me->fresh()->weekly_digest);
        $this->put('/settings/mail', ['weekly_digest' => '0']);
        $this->assertFalse($me->fresh()->weekly_digest);
    }
}
