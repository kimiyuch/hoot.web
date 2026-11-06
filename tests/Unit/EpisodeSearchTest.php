<?php

namespace Tests\Unit;

use App\Models\Episode;
use App\Services\EpisodeSearch;
use Tests\TestCase;

class EpisodeSearchTest extends TestCase
{
    public function test_the_search_is_the_show_and_the_episode_in_lower_case(): void
    {
        $episode = fn (int $season, ?int $number) => new Episode(['season' => $season, 'number' => $number]);

        $this->assertSame('ted lasso s04e03', EpisodeSearch::query('Ted Lasso', $episode(4, 3)));
        $this->assertSame('greys anatomy part 2 s01e10', EpisodeSearch::query('Grey’s Anatomy: Part 2', $episode(1, 10)));
        // A season dropping at once searches for the season; a special for the show.
        $this->assertSame('night shift s02', EpisodeSearch::query('Night Shift', collect([$episode(2, 1), $episode(2, 2)])));
        $this->assertSame('night shift', EpisodeSearch::query('Night Shift', $episode(2, null)));
    }

    public function test_the_query_goes_where_the_placeholder_is(): void
    {
        $episode = new Episode(['season' => 4, 'number' => 3]);

        $this->assertSame('https://example.org/search?q=ted+lasso+s04e03&cat=tv', EpisodeSearch::url('https://example.org/search?q={query}&cat=tv', 'Ted Lasso', $episode));
        $this->assertSame('example.org', EpisodeSearch::host('https://www.example.org/search?q={query}'));
    }
}
