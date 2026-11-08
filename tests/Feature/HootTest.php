<?php

namespace Tests\Feature;

use App\Livewire\DiscoverPage;
use App\Livewire\SearchPanel;
use App\Livewire\UpcomingDays;
use App\Models\Episode;
use App\Models\Movie;
use App\Models\Show;
use App\Models\User;
use App\Services\Premieres;
use App\Services\StreamingServices;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;
use Livewire\Livewire;
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
            ->assertDontSeeText('TBA');

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
            'credits' => ['cast' => [['name' => 'Second Billed', 'order' => 1], ['name' => 'Top Billed', 'order' => 0]]],
        ])]);

        $this->post('/movies', ['tmdb_id' => 550])->assertSessionHas('status', 'Following Owl Light.');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'api_key=abc123'));

        $movie = Movie::first();
        $this->assertSame('2026-11-10', $movie->cinema_date->toDateString());
        $this->assertSame(['Top Billed', 'Second Billed'], $movie->cast, 'the cast, in billing order, for For you');
        $this->assertSame('AU', $movie->cinema_country);
        $this->assertSame('2026-12-10', $movie->digital_date->toDateString());

        // This month first; December comes with Load more.
        $this->get('/for-you')->assertSeeInOrder(['Tue 10 Nov', 'Owl Light', 'In cinemas', 'first in AU', 'Load December', '1 more release later'])
            ->assertDontSee('Thu 10 Dec');

        Livewire::test(UpcomingDays::class)
            ->call('loadMore')
            ->assertSet('until', '2026-12')
            ->assertSeeInOrder(['Tue 10 Nov', 'Owl Light', 'Thu 10 Dec', 'Owl Light', 'Digital release'])
            ->assertDontSee('Load ');
    }

    public function test_the_timeline_goes_back_a_month_at_a_time(): void
    {
        $show = Show::create(['tvmaze_id' => 7, 'name' => 'Night Shift']);
        $this->user->shows()->attach($show);
        foreach ([['2026-09-20', 'Before the Dark'], ['2026-11-03', 'Lights Out'], ['2026-11-20', 'Dawn']] as $i => [$date, $name]) {
            $show->episodes()->create(['tvmaze_id' => 300 + $i, 'season' => 1, 'number' => $i + 1, 'name' => $name, 'airs_at' => $date.' 20:00:00']);
        }

        // It starts today, with a quiet way back: last month (with the rest of this one), then September.
        Livewire::test(UpcomingDays::class)
            ->assertSeeInOrder(['Missed something?', 'Show last month', 'Dawn'])
            ->assertDontSee('Lights Out')
            ->call('loadEarlier')
            ->assertSet('since', '2026-10')
            ->assertSeeInOrder(['Show September', 'Tue 3 Nov', 'Lights Out', 'Fri 20 Nov', 'Dawn'])
            ->assertDontSee('Before the Dark')
            ->call('loadEarlier')
            ->assertSet('since', '2026-09')
            ->assertSeeInOrder(['Sun 20 Sep', 'Before the Dark', 'Tue 3 Nov', 'Lights Out', 'Dawn'])
            ->assertDontSee('Missed something?');
    }

    public function test_an_episode_search_link_shows_once_set_and_only_for_episodes_that_are_out(): void
    {
        $show = Show::create(['tvmaze_id' => 9, 'name' => 'Ted Lasso']);
        $this->user->shows()->attach($show);
        $show->episodes()->create(['tvmaze_id' => 401, 'season' => 4, 'number' => 3, 'name' => 'Out', 'airs_at' => '2026-11-04 20:00:00']);
        $show->episodes()->create(['tvmaze_id' => 402, 'season' => 4, 'number' => 4, 'name' => 'Soon', 'airs_at' => '2026-11-11 20:00:00']);

        $this->get('/for-you')->assertDontSee('search.example');

        // It needs to be a web address, with {query} in it.
        $this->from('/settings')->put('/settings/extras', ['episode_search_url' => 'search.example/?q={query}'])->assertSessionHasErrorsIn('extras', 'episode_search_url');
        $this->from('/settings')->put('/settings/extras', ['episode_search_url' => 'https://search.example/'])->assertSessionHasErrorsIn('extras', 'episode_search_url');
        $this->from('/settings')->put('/settings/extras', ['episode_search_url' => 'https://www.search.example/find?q={query}&type=tv'])
            ->assertRedirect('/settings')->assertSessionHasNoErrors();

        // In Following, for the episode that's out, not the one coming.
        $this->get('/for-you')
            ->assertSee('href="https://www.search.example/find?q=ted+lasso+s04e03&amp;type=tv"', false)
            ->assertSee('Search on search.example')
            ->assertDontSee('s04e04');

        // In the timeline, once you go back.
        Livewire::test(UpcomingDays::class)
            ->assertDontSee('search.example')
            ->call('loadEarlier')
            ->assertSee('q=ted+lasso+s04e03', false)
            ->assertDontSee('s04e04');

        $this->put('/settings/extras', ['episode_search_url' => '']);
        $this->assertNull($this->user->fresh()->episode_search_url);
        $this->get('/for-you')->assertDontSee('search.example');
    }

    public function test_following_shows_one_season_at_a_time_starting_with_the_next_episode(): void
    {
        $show = Show::create(['tvmaze_id' => 5, 'name' => 'True Detective']);
        $this->user->shows()->attach($show);
        foreach ([[1, 1, '2014-01-12', 'The Long Bright Dark'], [2, 1, '2015-06-21', 'The Western Book of the Dead'], [0, null, '2016-01-01', 'Making Of'], [3, 1, '2026-12-20', 'Night Again']] as $i => [$season, $number, $date, $name]) {
            $show->episodes()->create(['tvmaze_id' => 100 + $i, 'season' => $season, 'number' => $number, 'name' => $name, 'airs_at' => $date.' 20:00:00']);
        }

        $this->get('/for-you')->assertOk()
            // Badges in order, specials last; season 3 (the next episode) is the one shown.
            ->assertSeeInOrder(['aria-label="Seasons"', 'aria-label="Season 1"', 'aria-label="Season 2"', 'aria-label="Season 3"', 'aria-label="Specials"'], false)
            ->assertSee('x-data="{ season: 3 }"', false)
            ->assertSeeInOrder(['display: none', 'The Long Bright Dark', 'Night Again'], false);
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

        // The search lightbox loads results as you type, from the second letter.
        Livewire::test(SearchPanel::class)
            ->set('query', 'n')->assertSee('Search shows on TVmaze')
            ->set('query', 'night')->assertSeeInOrder(['Shows', 'Night Shift', 'Night Court', 'Show all results']);
    }

    public function test_search_leaves_out_what_you_hide_says_so_and_shows_the_cast(): void
    {
        $this->user->forceFill(['discover_hidden' => ['korean']])->save();
        Http::fake([
            'api.tvmaze.com/search/shows*' => Http::response([
                ['score' => 1, 'show' => ['id' => 82, 'name' => 'Night Shift', 'language' => 'English', 'network' => ['name' => 'HBO', 'country' => ['code' => 'US']]]],
                ['score' => 1, 'show' => ['id' => 83, 'name' => 'Night Market', 'language' => 'Korean', 'network' => ['name' => 'tvN', 'country' => ['code' => 'KR']]]],
            ]),
            'api.tvmaze.com/shows/82/cast' => Http::response([['person' => ['name' => 'Ann Lead']], ['person' => ['name' => 'Bo Second']]]),
            'api.tvmaze.com/shows/83/cast' => Http::response([['person' => ['name' => 'Kim Lead']]]),
        ]);

        $this->get('/search?q=night')->assertOk()
            ->assertSee('Night Shift')->assertSee('Ann Lead, Bo Second')
            ->assertDontSee('Night Market')
            ->assertSee('1 result left out: you hide Korean titles.', false)
            ->assertSee('Show them');
        $this->get('/search?q=night&all=1')->assertOk()->assertSee('Night Market')->assertSee('Kim Lead')->assertDontSee('left out');

        // The casts are cached: no second round of requests.
        Http::assertSentCount(3);
        $this->get('/search?q=night');
        Http::assertSentCount(3);

        Livewire::test(SearchPanel::class)->set('query', 'night')
            ->assertSee('Night Shift')->assertDontSee('Night Market')->assertSee('1 result hidden by your settings');
    }

    public function test_following_a_show_keeps_its_cast_from_tvmaze_for_for_you(): void
    {
        Http::fake(['api.tvmaze.com/shows/82*' => Http::response(array_replace($this->tvmazeShow([]), ['_embedded' => ['episodes' => [], 'cast' => [['person' => ['name' => 'Ann Lead']], ['person' => ['name' => 'Bo Second']]]]]))]);
        $this->post('/shows', ['tvmaze_id' => 82]);

        Http::assertSent(fn ($request) => str_contains(urldecode($request->url()), 'embed[]=episodes&embed[]=cast'));
        $this->assertSame(['Ann Lead', 'Bo Second'], Show::where('tvmaze_id', 82)->value('cast'));
        $this->get('/for-you')->assertSee('Ann Lead, Bo Second');
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
            // The cast: a person playing two parts is named once, and only the first three.
            'api.tvmaze.com/shows/301/cast' => Http::response(array_map(fn ($name) => ['person' => ['name' => $name]], ['Ann Lead', 'Bo Second', 'Ann Lead', 'Cy Third', 'Di Fourth'])),
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
            ->assertSeeInOrder(['Big Return', 'Season 3', 'Mon 9 Nov', 'Netflix', 'With', 'Ann Lead, Bo Second, Cy Third', 'Brand New', 'New show', 'Sun 8 Nov', 'BBC One'])
            ->assertDontSee('Di Fourth')
            ->assertDontSee('Nocturne');
        $this->assertSame(['Ann Lead', 'Bo Second', 'Cy Third'], Cache::get('cast:tvmaze:301'));

        // Recently premiered: most popular first.
        $this->get('/discover?when=new')->assertOk()
            ->assertSeeInOrder(['Nocturne', 'New show', 'Fri 30 Oct', 'Apple TV', 'Old Network Favourite', 'Season 5', 'NBC']);

        $this->get('/discover?when=new&service=apple')->assertOk()
            ->assertSee('Nocturne')->assertDontSee('Old Network Favourite');

        // The filters apply in place.
        Livewire::withQueryParams(['when' => 'new'])->test(DiscoverPage::class)
            ->call('choose', 'service', 'apple')
            ->assertSet('service', 'apple')->assertSee('Nocturne')->assertDontSee('Old Network Favourite')
            ->call('choose', 'when', 'soon')
            ->assertSet('service', '')->assertSeeInOrder(['Shows premiering soon', 'Big Return', 'Brand New'])
            ->call('choose', 'service', 'nope')
            ->assertSet('service', '');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'schedule/web?date=2026-10-06'));
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'date=2026-10-05'));
    }

    public function test_movie_premieres_bring_their_cast_from_the_same_request(): void
    {
        config(['hoot.tmdb_token' => 'abc123']);
        Http::fake([
            'api.themoviedb.org/3/discover/movie*' => Http::response(['total_pages' => 1, 'results' => [
                ['id' => 7, 'title' => 'Owl Light', 'release_date' => '2026-11-20'],
            ]]),
            'api.themoviedb.org/3/movie/7*' => Http::response([
                'release_dates' => ['results' => [['iso_3166_1' => 'US', 'release_dates' => [['type' => 3, 'release_date' => '2026-11-20T00:00:00.000Z']]]]],
                'credits' => ['cast' => [['name' => 'Wren Moss', 'order' => 0], ['name' => 'Pip Hart', 'order' => 1]]],
            ]),
        ]);

        $this->assertSame(['Wren Moss', 'Pip Hart'], app(Premieres::class)->movies()[0]['cast']);
        Http::assertSent(fn ($request) => str_contains(urldecode($request->url()), 'append_to_response=release_dates,watch/providers,credits'));
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/credits'));
    }

    public function test_hidden_kinds_are_saved_in_settings(): void
    {
        $this->put('/settings/hidden', ['hidden' => ['korean', 'nope']])->assertSessionHasErrors('hidden.1');

        $this->put('/settings/hidden', ['hidden' => ['indian', 'anime', 'korean']])
            ->assertSessionHas('status', 'Discover now hides anime, Korean and Indian titles.');
        $this->assertSame(['anime', 'korean', 'indian'], $this->user->fresh()->discover_hidden);
        $this->get('/settings')->assertOk()->assertSee('Hide from Discover');

        $this->put('/settings/hidden', [])->assertSessionHas('status', 'Discover shows everything again.');
        $this->assertNull($this->user->fresh()->discover_hidden);
    }

    public function test_discover_leaves_out_hidden_premieres(): void
    {
        $show = fn (int $id, string $name, array $extra) => $extra + ['tvmaze_id' => $id, 'name' => $name, 'year' => '2026', 'channel' => null, 'status' => null,
            'image_url' => null, 'url' => null, 'rating' => null, 'genres' => [], 'language' => 'English', 'overview' => null,
            'season' => 1, 'airs_at' => '2026-11-10T20:00:00+00:00', 'weight' => 100 - $id, 'country' => 'US', 'service' => null];
        Storage::disk('local')->put('premieres.json', json_encode(['shows' => [
            $show(1, 'Plain Drama', []),
            $show(2, 'Seoul Nights', ['language' => 'Korean', 'country' => null]),
            $show(3, 'Mumbai Calling', ['language' => 'English', 'country' => 'IN']),
            $show(4, 'Spirit Blade', ['language' => 'Japanese', 'country' => 'JP', 'genres' => ['Anime', 'Action']]),
            $show(5, 'Tokyo Kitchen', ['language' => 'Japanese', 'country' => 'JP', 'genres' => ['Food']]),
        ], 'movies' => [], 'refreshed_at' => now()->toIso8601String()]));

        $this->get('/discover')->assertOk()->assertSee(['Seoul Nights', 'Mumbai Calling', 'Spirit Blade'])->assertDontSee('Hiding');

        $this->user->forceFill(['discover_hidden' => ['anime', 'korean', 'indian']])->save();

        $this->get('/discover')->assertOk()
            ->assertSee('Hiding anime, Korean and Indian titles.')
            ->assertSee(['Plain Drama', 'Tokyo Kitchen'])
            ->assertDontSee('Seoul Nights')->assertDontSee('Mumbai Calling')->assertDontSee('Spirit Blade');
    }

    public function test_any_time_leaves_out_hidden_titles_and_still_fills_pages_of_twenty(): void
    {
        config(['hoot.tmdb_token' => 'abc123']);
        $this->user->forceFill(['discover_hidden' => ['anime', 'korean']])->save();
        // 40 shows by popularity over two TMDB pages: every third one Korean, show 7 Japanese animation. 26 are left.
        $shows = array_map(fn ($i) => ['id' => $i, 'name' => sprintf('Show-%02d', $i), 'first_air_date' => '2026-03-01', 'popularity' => 100 - $i,
            'original_language' => $i % 3 === 0 ? 'ko' : ($i === 7 ? 'ja' : 'en'), 'genre_ids' => $i === 7 ? [16] : [18], 'origin_country' => []], range(1, 40));
        Http::fake([
            'api.themoviedb.org/3/watch/providers/*' => Http::response(['results' => []]),
            'api.themoviedb.org/3/discover/tv*page=1*' => Http::response(['total_pages' => 2, 'total_results' => 40, 'results' => array_slice($shows, 0, 20)]),
            'api.themoviedb.org/3/discover/tv*page=2*' => Http::response(['total_pages' => 2, 'total_results' => 40, 'results' => array_slice($shows, 20)]),
            'api.themoviedb.org/3/tv/*' => Http::response(['cast' => []]),
        ]);

        // Page 1 is the first 20 that are left (1, 2, 4, 5, 8 … 29, 31), taking from TMDB's second page.
        $first = $this->get('/discover?when=any&type=tv')->assertOk()
            ->assertSee('Hiding anime and Korean titles.')
            ->assertSeeInOrder(['Show-01', 'Show-02', 'Show-04', 'Show-29', 'Show-31', 'Page 1', 'Next'])
            ->assertDontSee('Page 1 of');
        foreach (['Show-03', 'Show-07', 'Show-30', 'Show-32'] as $title) {
            $first->assertDontSee($title);
        }

        $this->get('/discover?when=any&type=tv&page=2')->assertOk()
            ->assertSeeInOrder(['Show-32', 'Show-34', 'Show-40', 'Page 2'])
            ->assertDontSee('Show-31')->assertDontSee('Show-39')->assertDontSee('Next');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'without_keywords=210024'));
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

    public function test_browse_in_all_regions_merges_the_countries_into_pages_of_twenty(): void
    {
        config(['hoot.tmdb_token' => 'abc123', 'hoot.regions' => ['CH' => 'Switzerland', 'US' => 'United States']]);
        $film = fn (int $id, int $popularity) => ['id' => $id, 'title' => sprintf('Film-%03d', $id), 'release_date' => '2026-03-01', 'popularity' => $popularity];
        // Switzerland: 25 films over two pages, popularity 100, 98, … 52. The US: film 1 again, and ten of its own at 99, 97, … 81.
        $swiss = array_map(fn ($i) => $film($i, 102 - 2 * $i), range(1, 25));
        $american = [$film(1, 100), ...array_map(fn ($i) => $film(100 + $i, 101 - 2 * $i), range(1, 10))];
        Http::fake([
            'api.themoviedb.org/3/watch/providers/*' => Http::response(['results' => []]),
            'api.themoviedb.org/3/discover/movie*watch_region=CH&page=1*' => Http::response(['total_pages' => 2, 'total_results' => 25, 'results' => array_slice($swiss, 0, 20)]),
            'api.themoviedb.org/3/discover/movie*watch_region=CH&page=2*' => Http::response(['total_pages' => 2, 'total_results' => 25, 'results' => array_slice($swiss, 20)]),
            'api.themoviedb.org/3/discover/movie*watch_region=US*' => Http::response(['total_pages' => 1, 'total_results' => 11, 'results' => $american]),
            // The cast, in billing order ("order"), not the order TMDB lists it in.
            'api.themoviedb.org/3/movie/1/credits*' => Http::response(['cast' => [['name' => 'Second Billed', 'order' => 1], ['name' => 'Top Billed', 'order' => 0]]]),
            'api.themoviedb.org/3/movie/*/credits*' => Http::response(['cast' => []]),
        ]);

        // 35 different films: page 1 is popularity 100 to 81, both countries interleaved, film 1 once.
        $first = $this->get('/discover?when=any&type=movie&service=netflix&region=all');
        $first->assertOk()
            ->assertSeeInOrder(['in any region', 'Film-001', 'Top Billed, Second Billed', 'Film-101', 'Film-002', 'Film-102', 'Film-010', 'Film-110', 'Page 1', 'Next'])
            ->assertDontSee('Film-011');
        $this->assertSame(1, substr_count($first->getContent(), 'Film-001</'));

        $this->get('/discover?when=any&type=movie&service=netflix&region=all&page=2')->assertOk()
            ->assertSeeInOrder(['Film-011', 'Film-012', 'Film-025', 'Page 2'])
            ->assertDontSee('Film-110')->assertDontSee('Next');
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
