<?php

namespace Tests\Feature;

use App\Models\Movie;
use App\Models\Show;
use App\Models\User;
use App\Services\Circle;
use App\Services\Library;
use App\Services\Network;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CircleTest extends TestCase
{
    use RefreshDatabase;

    private function person(string $username, array $attributes = []): User
    {
        return User::factory()->create(['name' => ucfirst($username), 'username' => $username, ...$attributes]);
    }

    public function test_cards_name_the_people_you_follow_who_have_it_and_what_they_make_of_it(): void
    {
        [$me, $pip, $rue, $stranger, $secret] = [$this->person('me'), $this->person('pip'), $this->person('rue'), $this->person('stranger'), $this->person('secret', ['is_private' => true])];
        $network = app(Network::class);
        $library = app(Library::class);
        $network->follow($me, $pip);
        $network->follow($me, $rue);
        $network->follow($me, $secret); // a request, not accepted

        $severance = Show::create(['tvmaze_id' => 1, 'tmdb_id' => 95396, 'name' => 'Severance']);
        $arrival = Movie::create(['tmdb_id' => 329865, 'title' => 'Arrival']);
        $library->setStatus($pip, $severance, 'watching');
        $library->rate($pip, $severance, 9);
        $library->favourite($rue, $severance);
        $library->setStatus($stranger, $severance, 'watched');
        $library->setStatus($secret, $severance, 'watched');
        $library->setStatus($rue, $arrival, 'plan');

        $circle = app(Circle::class)->among($me, [1], [95396], [329865]);

        $describe = fn (array $entries) => array_map(fn ($entry) => $entry['user']->username.': '.$entry['label'], $entries);
        $this->assertSame(['rue: Favourite', 'pip: Watching · ★★★★½'], $describe($circle['show'][1]));
        $this->assertSame($describe($circle['show'][1]), $describe($circle['tv'][95396]), 'the same show by its TMDB id');
        $this->assertSame(['rue: Want to watch'], $describe($circle['movie'][329865]));

        // Blocking takes them out.
        $network->block($me, $rue);
        $this->assertSame(['pip: Watching · ★★★★½'], $describe(app(Circle::class)->among($me, [1])['show'][1]));
        // Nobody followed: nothing to look up.
        $this->assertSame(['show' => [], 'tv' => [], 'movie' => []], app(Circle::class)->among($stranger, [1]));
    }

    public function test_the_search_page_shows_them_on_the_cards(): void
    {
        [$me, $pip] = [$this->person('me'), $this->person('pip')];
        app(Network::class)->follow($me, $pip);
        app(Library::class)->setStatus($pip, Show::create(['tvmaze_id' => 1, 'name' => 'Severance']), 'watching');

        Cache::put('search:tv:v2:severance', [['tvmaze_id' => 1, 'name' => 'Severance', 'year' => '2022', 'channel' => 'Apple TV', 'status' => 'Running', 'image_url' => null, 'url' => null, 'rating' => 8.4]], 60);
        Cache::put('search:movie:severance', [], 60);
        config(['hoot.tmdb_token' => 'test']);

        $this->actingAs($me)->get('/search?q=severance')->assertOk()
            ->assertSeeInOrder(['Severance', 'People you follow:', 'Pip', 'Watching']);
    }
}
