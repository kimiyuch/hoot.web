<?php

namespace Tests\Unit;

use App\Services\StreamingServices;
use Tests\TestCase;

class StreamingServicesTest extends TestCase
{
    public function test_links_go_to_the_official_site_then_the_services_search_then_its_homepage(): void
    {
        $this->assertSame('https://tv.apple.com/show/nocturne/umc.1', StreamingServices::link('apple', 'https://tv.apple.com/show/nocturne/umc.1', 'Nocturne'));
        $this->assertSame('https://tv.apple.com/search?term=Bad%20Monkey', StreamingServices::link('apple', null, 'Bad Monkey'));
        $this->assertSame('https://www.hbomax.com', StreamingServices::link('max', null, 'War'));
        $this->assertNull(StreamingServices::link(null, null, 'History Show'));
        $this->assertNull(StreamingServices::link('nope'));
    }
}
