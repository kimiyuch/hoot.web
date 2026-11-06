<?php

namespace Tests\Feature;

use App\Models\Episode;
use App\Models\Movie;
use App\Models\Show;
use App\Models\User;
use App\Services\Premieres;
use App\Services\StreamingServices;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\TestCase;

class HootTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        Carbon::setTestNow('2026-11-05 12:00:00');
        Sleep::fake();
        Http::preventStrayRequests();
        config(['hoot.timezone' => 'Europe/Zurich', 'hoot.tmdb_token' => null]);
    }

    private function tvmazeShow(array $episodes): array
    {
        return [
            'id' => 82, 'name' => 'Night Shift', 'premiered' => '2024-04-01', 'status' => 'Running',
            'network' => null, 'webChannel' => ['name' => 'Apple TV+', 'country' => null],
            'image' => ['medium' => 'https://static.tvmaze.com/poster.jpg'], 'url' => 'https://www.tvmaze.com/shows/82',
            'weight' => 90, 'genres' => ['Drama'],
            '_embedded' => ['episodes' => $episodes],
        ];
    }

    private function episode(int $id, int $season, ?int $number, ?string $airstamp, string $name = 'Episode'): array
    {
        return ['id' => $id, 'season' => $season, 'number' => $number, 'type' => 'regular', 'name' => $name,
            'airstamp' => $airstamp, 'runtime' => 50, 'url' => "https://www.tvmaze.com/episodes/{$id}"];
    }

    public function test_following_a_show_saves_its_episodes_and_shows_them_in_local_time(): void
    {
        Http::fake(['api.tvmaze.com/shows/82*' => Http::response($this->tvmazeShow([
            $this->episode(1, 1, 1, '2026-10-30T01:00:00+00:00', 'Pilot'),
            // Sunday 9pm in New York is Monday 03:00 in Zurich.
            $this->episode(2, 1, 2, '2026-11-10T02:00:00+00:00', 'The Long Night'),
            $this->episode(3, 1, 3, null, 'TBA'),
        ]))]);

        $this->from('/search?q=night')->post('/shows', ['tvmaze_id' => 82])
            ->assertRedirect('/search?q=night')
            ->assertSessionHas('status', 'Following Night Shift.');

        $this->assertSame(3, Episode::count());
        $this->assertSame('Apple TV+', Show::first()->channel);

        $this->get('/for-you')->assertOk()
            ->assertSeeInOrder(['Up next', 'Night Shift', 'S01E02', 'The Long Night', 'Tuesday 10 Nov, 03:00'])
            ->assertSeeInOrder(['Tue 10 Nov', 'Night Shift', 'S01E02', '03:00', 'Just released', 'Night Shift', 'S01E01'])
            ->assertDontSee('TBA');

        $this->withUnencryptedCookie('tz', 'America/New_York')->get('/for-you')
            ->assertSee('Monday 9 Nov, 21:00')
            ->assertSeeInOrder(['Mon 9 Nov', 'The Long Night', '21:00']);
    }

    public function test_an_unknown_timezone_cookie_falls_back_to_the_configured_one(): void
    {
        $this->withUnencryptedCookie('tz', 'Mars/Olympus')->get('/for-you')
            ->assertSee('data-timezone="Europe/Zurich"', false);
    }

    public function test_a_season_dropping_at_once_is_one_release(): void
    {
        $show = $this->user->shows()->create(['tvmaze_id' => 1, 'name' => 'Binge']);
        foreach (range(1, 8) as $number) {
            $show->episodes()->create(['tvmaze_id' => $number, 'season' => 2, 'number' => $number, 'airs_at' => '2026-11-07 08:00:00']);
        }

        $this->get('/for-you')->assertSee('S02E01–E08')->assertSee('all 8 episodes');
    }

    public function test_unfollowing_a_show_removes_its_episodes(): void
    {
        $show = $this->user->shows()->create(['tvmaze_id' => 1, 'name' => 'Gone']);
        $show->episodes()->create(['tvmaze_id' => 1, 'season' => 1, 'number' => 1, 'airs_at' => '2026-11-07 08:00:00']);

        $this->delete("/shows/{$show->id}")->assertRedirect();

        $this->assertSame(0, Show::count());
        $this->assertSame(0, Episode::count());
    }

    public function test_following_a_movie_keeps_its_earliest_cinema_and_digital_release_anywhere(): void
    {
        config(['hoot.tmdb_token' => 'abc123']);
        Http::fake(['api.themoviedb.org/3/movie/550*' => Http::response([
            'id' => 550, 'title' => 'Owl Light', 'poster_path' => '/owl.jpg', 'release_date' => '2026-11-20',
            'release_dates' => ['results' => [
                ['iso_3166_1' => 'US', 'release_dates' => [
                    ['type' => 1, 'release_date' => '2026-09-01T00:00:00.000Z'],
                    ['type' => 3, 'release_date' => '2026-11-20T00:00:00.000Z'],
                    ['type' => 4, 'release_date' => '2026-12-15T00:00:00.000Z'],
                ]],
                ['iso_3166_1' => 'AU', 'release_dates' => [
                    ['type' => 3, 'release_date' => '2026-11-10T00:00:00.000Z'],
                    ['type' => 4, 'release_date' => '2026-12-10T00:00:00.000Z'],
                ]],
            ]],
        ])]);

        $this->post('/movies', ['tmdb_id' => 550])->assertSessionHas('status', 'Following Owl Light.');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'api_key=abc123'));

        $movie = Movie::first();
        $this->assertSame('2026-11-10', $movie->cinema_date->toDateString());
        $this->assertSame('AU', $movie->cinema_country);
        $this->assertSame('2026-12-10', $movie->digital_date->toDateString());

        $this->get('/for-you')->assertSeeInOrder(['Tue 10 Nov', 'Owl Light', 'In cinemas', 'first in AU', 'Thu 10 Dec', 'Owl Light', 'Digital release']);
    }

    public function test_a_long_tmdb_token_is_sent_as_a_bearer_token(): void
    {
        config(['hoot.tmdb_token' => $token = str_repeat('x', 200)]);
        Http::fake(['api.themoviedb.org/*' => Http::response(['results' => []])]);

        $this->get('/search?q=owl')->assertOk();

        Http::assertSent(fn ($request) => ! str_contains($request->url(), 'tvmaze') ? $request->hasHeader('Authorization', "Bearer {$token}") : true);
    }

    public function test_search_finds_shows_and_marks_followed_ones(): void
    {
        $this->user->shows()->create(['tvmaze_id' => 82, 'name' => 'Night Shift']);
        Http::fake(['api.tvmaze.com/search/shows*' => Http::response([
            ['score' => 1, 'show' => ['id' => 82, 'name' => 'Night Shift', 'premiered' => '2024-04-01', 'network' => ['name' => 'HBO']]],
            ['score' => 1, 'show' => ['id' => 83, 'name' => 'Night Court', 'premiered' => '1984-01-04', 'network' => ['name' => 'NBC']]],
        ])]);

        $this->get('/search?q=night')->assertOk()
            ->assertSeeInOrder(['Night Shift', '2024 · HBO', 'Following', 'Night Court', '1984 · NBC', 'Follow Night Court'])
            ->assertSee('Movies need a TMDB key');

        // The search lightbox only gets the results, without the page around them.
        $this->get('/search?q=night', ['X-Hoot-Partial' => '1'])->assertOk()
            ->assertSeeInOrder(['Shows', 'Night Shift', 'Following', 'Night Court', 'Show all results'])
            ->assertDontSee('<html', false);
    }

    public function test_search_says_so_when_tvmaze_is_down(): void
    {
        Http::fake(['api.tvmaze.com/*' => Http::response('', 500)]);

        $this->get('/search?q=night')->assertOk()->assertSee('TVmaze can’t be reached');
    }

    public function test_the_calendar_feed_has_episodes_and_movie_releases(): void
    {
        $show = $this->user->shows()->create(['tvmaze_id' => 1, 'name' => 'Night Shift', 'channel' => 'HBO']);
        $show->episodes()->create(['tvmaze_id' => 7, 'season' => 1, 'number' => 2, 'name' => 'Dawn, again', 'airs_at' => '2026-11-10 02:00:00', 'runtime' => 50]);
        $this->user->movies()->create(['tmdb_id' => 550, 'title' => 'Owl Light', 'digital_date' => '2026-12-10', 'digital_country' => 'AU']);

        $ics = $this->get(route('calendar', $this->user->calendar_token))->assertOk()
            ->assertHeader('Content-Type', 'text/calendar; charset=utf-8')
            ->getContent();

        $this->assertStringContainsString("UID:episode-7@hoot.kimiyu.ch\r\n", $ics);
        $this->assertStringContainsString("DTSTART:20261110T020000Z\r\nDTEND:20261110T025000Z\r\nSUMMARY:Night Shift S01E02\r\n", $ics);
        $this->assertStringContainsString('DESCRIPTION:Dawn\, again\nHBO', $ics);
        $this->assertStringContainsString("DTSTART;VALUE=DATE:20261210\r\nDTEND;VALUE=DATE:20261211\r\nSUMMARY:Owl Light (digital)", $ics);

        foreach (explode("\r\n", $ics) as $line) {
            $this->assertLessThanOrEqual(75, strlen($line));
        }
    }

    public function test_the_refresh_only_reloads_shows_that_changed(): void
    {
        $fresh = $this->user->shows()->create(['tvmaze_id' => 82, 'name' => 'Fresh', 'refreshed_at' => now()->subHour()]);
        $this->user->shows()->create(['tvmaze_id' => 90, 'name' => 'Changed', 'refreshed_at' => now()->subDay()]);

        Http::fake([
            'api.tvmaze.com/updates/shows*' => Http::response([82 => now()->subDays(2)->getTimestamp(), 90 => now()->subHours(2)->getTimestamp()]),
            'api.tvmaze.com/shows/90*' => Http::response(['id' => 90, 'name' => 'Changed', '_embedded' => ['episodes' => [$this->episode(5, 1, 1, '2026-11-08T20:00:00+00:00')]]]),
            'api.tvmaze.com/schedule/web*' => Http::response([
                $this->episode(20, 1, 1, '2026-10-30T12:00:00+00:00') + ['_embedded' => ['show' => ['id' => 400, 'name' => 'Nocturne', 'weight' => 100, 'webChannel' => ['name' => 'Apple TV']]]],
                $this->episode(21, 1, 2, '2026-10-30T12:00:00+00:00') + ['_embedded' => ['show' => ['id' => 400, 'name' => 'Nocturne', 'weight' => 100]]],
            ]),
            'api.tvmaze.com/schedule?*' => Http::response([
                $this->episode(30, 5, 1, '2026-10-20T01:00:00+00:00') + ['show' => ['id' => 500, 'name' => 'Old Network Favourite', 'weight' => 70, 'network' => ['name' => 'NBC', 'country' => ['code' => 'US']]]],
            ]),
            'api.tvmaze.com/schedule/full' => Http::response([
                $this->episode(10, 1, 1, '2026-11-08T20:00:00+00:00') + ['_embedded' => ['show' => ['id' => 300, 'name' => 'Brand New', 'weight' => 50, 'network' => ['name' => 'BBC One', 'country' => ['code' => 'GB']]]]],
                $this->episode(11, 3, 1, '2026-11-09T20:00:00+00:00') + ['_embedded' => ['show' => ['id' => 301, 'name' => 'Big Return', 'weight' => 99, 'webChannel' => ['name' => 'Netflix']]]],
                $this->episode(12, 3, 2, '2026-11-09T21:00:00+00:00') + ['_embedded' => ['show' => ['id' => 301, 'name' => 'Big Return', 'weight' => 99]]],
            ]),
        ]);

        $this->artisan('hoot:refresh')
            ->expectsOutput('Refreshed 1 shows and 0 movies.')
            ->expectsOutput('Found 4 show premieres and 0 upcoming movies.')
            ->assertSuccessful();

        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'shows/82'));
        $this->assertSame(1, Episode::count());
        $this->assertTrue($fresh->fresh()->refreshed_at->eq(now()->subHour()));

        // Most popular first.
        $this->get('/discover')->assertOk()
            ->assertSeeInOrder(['Big Return', 'Season 3', 'Mon 9 Nov', 'Netflix', 'Brand New', 'New show', 'Sun 8 Nov', 'BBC One'])
            ->assertDontSee('Nocturne');

        // Recently premiered: most popular first.
        $this->get('/discover?when=new')->assertOk()
            ->assertSeeInOrder(['Nocturne', 'New show', 'Fri 30 Oct', 'Apple TV', 'Old Network Favourite', 'Season 5', 'NBC']);

        $this->get('/discover?when=new&service=apple')->assertOk()
            ->assertSee('Nocturne')->assertDontSee('Old Network Favourite');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'schedule/web?date=2026-10-06'));
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'date=2026-10-05'));
    }

    public function test_pages_render_without_anything_followed(): void
    {
        $this->get('/for-you')->assertOk()->assertSee('Nothing followed yet');
        $this->get('/discover')->assertOk()->assertSee('No premieres loaded yet');
        $this->get('/search')->assertOk();
        $this->get('/search')->assertOk();
        $this->assertSame([], app(Premieres::class)->get()['shows']);
    }

    public function test_browse_asks_tmdb_for_the_service_in_the_region_and_shows_ratings(): void
    {
        config(['hoot.tmdb_token' => 'abc123']);
        $this->user->movies()->create(['tmdb_id' => 1, 'title' => 'Followed Film']);
        Http::fake([
            'api.themoviedb.org/3/watch/providers/*' => Http::response(['results' => [['provider_id' => 8, 'logo_path' => '/netflix.png']]]),
            'api.themoviedb.org/3/discover/movie*' => Http::response(['total_pages' => 3, 'results' => [
                ['id' => 1, 'title' => 'Followed Film', 'release_date' => '2016-03-01', 'poster_path' => null, 'vote_average' => 7.84, 'vote_count' => 900],
                ['id' => 2, 'title' => 'Obscure Film', 'release_date' => '2019-03-01', 'poster_path' => null, 'vote_average' => 9.5, 'vote_count' => 3],
            ]]),
        ]);

        $this->get('/discover?when=any&type=movie&service=netflix&region=DE&sort=rating&min_rating=7')->assertOk()
            ->assertSeeInOrder(['Followed Film', '2016', 'Following', 'Obscure Film', '2019', 'Page 1 of 3'])
            ->assertSee('Rated 7.8')
            ->assertDontSee('Rated 9.5')
            ->assertSee('https://image.tmdb.org/t/p/w92/netflix.png');

        Http::assertSent(fn ($request) => str_contains($request->url(), '/discover/movie')
            && collect(['with_watch_providers=8', 'watch_region=DE', 'sort_by=vote_average.desc', 'vote_average.gte=7', 'primary_release_date.gte='.now()->year.'-01-01'])
                ->every(fn ($part) => str_contains(urldecode($request->url()), $part)));

        $this->get('/discover?service=nope')->assertSessionHasErrors('service');
    }

    public function test_a_show_from_browse_is_followed_through_tvmaze(): void
    {
        config(['hoot.tmdb_token' => 'abc123']);
        Http::fake([
            'api.themoviedb.org/3/tv/292741/external_ids*' => Http::response(['tvdb_id' => 4242, 'imdb_id' => 'tt26598805']),
            'api.tvmaze.com/lookup/shows*' => Http::response(['id' => 82]),
            'api.tvmaze.com/shows/82*' => Http::response($this->tvmazeShow([])),
        ]);

        $this->post('/shows', ['tmdb_id' => 292741])->assertSessionHas('status', 'Following Night Shift.');

        $this->assertSame(292741, Show::first()->tmdb_id);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'lookup/shows?thetvdb=4242'));
    }

    public function test_the_old_pages_redirect_to_for_you_and_discover(): void
    {
        $this->get('/following')->assertRedirect('/for-you');
        $this->get('/premieres?tab=recent')->assertRedirect(route('discover', ['when' => 'new']));
        $this->get('/premieres?tab=movies')->assertRedirect(route('discover', ['when' => 'soon', 'type' => 'movie']));
        $this->get('/browse?service=netflix')->assertRedirect(route('discover', ['when' => 'any', 'type' => 'movie', 'service' => 'netflix']));
    }

    public function test_for_you_lists_what_you_follow_with_the_calendar_feed(): void
    {
        $this->user->movies()->create(['tmdb_id' => 1, 'title' => 'Owl Light']);

        $this->get('/for-you')->assertOk()
            ->assertSeeInOrder(['Following', '0 shows · 1 movie', 'Owl Light', 'Unfollow', 'In your calendar'])
            ->assertSee('webcal://', false);
    }

    public function test_a_movies_streaming_services_come_from_every_country(): void
    {
        $this->assertSame(['netflix', 'max'], StreamingServices::fromWatchProviders(['results' => [
            'AD' => ['flatrate' => [['provider_id' => 8], ['provider_id' => 99999]]],
            'US' => ['buy' => [['provider_id' => 350]], 'ads' => [['provider_id' => 1899]]],
        ]]));
    }
}
