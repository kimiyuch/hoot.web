<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Keep tests away from the real files in storage/app (e.g. premieres.json from the local refresh).
        Storage::fake('local');
        // And away from TVmaze and TMDB: a request a test didn't fake fails instead of going out.
        Http::preventStrayRequests();
    }
}
