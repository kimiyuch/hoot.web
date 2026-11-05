<?php

return [
    // GitHub repository of this site.
    'repository_url' => 'https://github.com/kimiyuch/possum.web',

    // Used until the browser has told us its own timezone (it does on the first page load), and for the calendar feed.
    'timezone' => env('POSSUM_TIMEZONE', 'Europe/Zurich'),

    // TMDB, for movies. Either the "API Read Access Token" (a long JWT) or the short "API Key"; both work.
    // Without one, Possum only does TV.
    'tmdb_token' => env('TMDB_TOKEN'),

    // Where to look up which service has what on the Browse page (TMDB only knows that per country).
    // Can be changed on the page; this is the default.
    'region' => env('POSSUM_REGION', 'CH'),
    'regions' => ['CH' => 'Switzerland', 'DE' => 'Germany', 'AT' => 'Austria', 'FR' => 'France', 'IT' => 'Italy', 'GB' => 'United Kingdom', 'US' => 'United States'],

    // The streaming services to filter by: TMDB's provider ids, and the channel names TVmaze uses for their shows.
    // Shown first in the Discover service filter; the rest are behind "Show more".
    'featured_services' => ['netflix', 'max', 'apple', 'prime', 'disney'],

    'services' => [
        'netflix' => ['name' => 'Netflix', 'tmdb' => [8], 'tvmaze' => ['Netflix']],
        'prime' => ['name' => 'Prime Video', 'tmdb' => [119, 9], 'tvmaze' => ['Prime Video', 'Amazon Prime Video', 'Amazon Freevee']],
        'apple' => ['name' => 'Apple TV', 'tmdb' => [350], 'tvmaze' => ['Apple TV', 'Apple TV+']],
        'disney' => ['name' => 'Disney+', 'tmdb' => [337], 'tvmaze' => ['Disney+']],
        'max' => ['name' => 'HBO Max', 'tmdb' => [1899, 384], 'tvmaze' => ['HBO Max', 'Max', 'HBO']],
        'paramount' => ['name' => 'Paramount+', 'tmdb' => [531], 'tvmaze' => ['Paramount+', 'Showtime']],
        'hulu' => ['name' => 'Hulu', 'tmdb' => [15], 'tvmaze' => ['Hulu', 'FX on Hulu']],
        'peacock' => ['name' => 'Peacock', 'tmdb' => [386], 'tvmaze' => ['Peacock']],
        'sky' => ['name' => 'Sky', 'tmdb' => [210, 29], 'tvmaze' => ['Sky Atlantic', 'Sky Max', 'Sky Showcase', 'Sky One', 'NOW', 'WOW']],
        'bbc' => ['name' => 'BBC iPlayer', 'tmdb' => [38], 'tvmaze' => ['BBC iPlayer', 'BBC One', 'BBC Two', 'BBC Three', 'BBC Four']],
        'mubi' => ['name' => 'MUBI', 'tmdb' => [11], 'tvmaze' => ['MUBI']],
        'crunchyroll' => ['name' => 'Crunchyroll', 'tmdb' => [283], 'tvmaze' => ['Crunchyroll']],
        'playsuisse' => ['name' => 'Play Suisse', 'tmdb' => [691], 'tvmaze' => ['Play Suisse', 'SRF 1', 'SRF zwei', 'RTS 1']],
    ],

    // Discover's "Any time" starts at this year. Null means the current year
    // (worked out per request, so a cached config doesn't get stuck in an old year).
    'browse_from_year' => null,

    // How far ahead the Upcoming page and the calendar feed look, and how far back "Just released" goes.
    'days_ahead' => 120,
    'days_back' => 7,
];
