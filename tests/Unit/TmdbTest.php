<?php

namespace Tests\Unit;

use App\Services\Tmdb;
use PHPUnit\Framework\TestCase;

class TmdbTest extends TestCase
{
    public function test_a_limited_release_counts_when_there_is_no_wide_one(): void
    {
        $releases = Tmdb::earliestReleases(['results' => [
            ['iso_3166_1' => 'FR', 'release_dates' => [['type' => 2, 'release_date' => '2026-05-20T00:00:00.000Z']]],
        ]]);

        $this->assertSame(['date' => '2026-05-20', 'country' => 'FR'], $releases['cinema']);
        $this->assertNull($releases['digital']);
    }
}
