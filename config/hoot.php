<?php

return [
    // Usernames nobody can take: the app's own words and pages, so a profile can't pose as one.
    'reserved_usernames' => [
        'admin', 'administrator', 'hoot', 'kimiyu', 'support', 'help', 'about', 'team', 'staff', 'moderator', 'mod', 'root', 'system',
        'settings', 'discover', 'search', 'login', 'logout', 'register', 'signup', 'for_you', 'foryou', 'calendar', 'profile',
        'people', 'shows', 'movies', 'favourites', 'favorites', 'following', 'followers', 'premieres', 'browse', 'api', 'www', 'mail',
        'privacy', 'terms', 'null', 'undefined', 'anonymous', 'me', 'you',
    ],

    // GitHub repository of this site.
    'repository_url' => 'https://github.com/kimiyuch/hoot.web',

    // Where "Report" on a profile and the privacy page's contact go.
    'report_email' => env('HOOT_REPORT_EMAIL', env('MAIL_FROM_ADDRESS')),

    // Used until the browser has told us its own timezone (it does on the first page load), and for the calendar feed.
    'timezone' => env('HOOT_TIMEZONE', 'Europe/Zurich'),

    // TMDB, for movies. Either the "API Read Access Token" (a long JWT) or the short "API Key"; both work.
    // Without one, Hoot only does TV.
    'tmdb_token' => env('TMDB_TOKEN'),

    // Where to look up which service has what on the Browse page (TMDB only knows that per country).
    // Can be changed on the page; this is the default.
    'region' => env('HOOT_REGION', 'CH'),
    'regions' => ['CH' => 'Switzerland', 'DE' => 'Germany', 'AT' => 'Austria', 'FR' => 'France', 'IT' => 'Italy', 'GB' => 'United Kingdom', 'US' => 'United States'],

    // The streaming services to filter by: TMDB's provider ids, and the channel names TVmaze uses for their shows.
    // Shown first in the Discover service filter; the rest are behind "Show more".
    'featured_services' => ['netflix', 'max', 'apple', 'prime', 'disney'],

    // url: the service's homepage; search: its search, to which the title is added (only where it's known to work).
    'services' => [
        'netflix' => ['name' => 'Netflix', 'tmdb' => [8], 'tvmaze' => ['Netflix'], 'url' => 'https://www.netflix.com', 'search' => 'https://www.netflix.com/search?q='],
        'prime' => ['name' => 'Prime Video', 'tmdb' => [119, 9], 'tvmaze' => ['Prime Video', 'Amazon Prime Video', 'Amazon Freevee'], 'url' => 'https://www.primevideo.com', 'search' => 'https://www.primevideo.com/search?phrase='],
        'apple' => ['name' => 'Apple TV', 'tmdb' => [350], 'tvmaze' => ['Apple TV', 'Apple TV+'], 'url' => 'https://tv.apple.com', 'search' => 'https://tv.apple.com/search?term='],
        'disney' => ['name' => 'Disney+', 'tmdb' => [337], 'tvmaze' => ['Disney+'], 'url' => 'https://www.disneyplus.com'],
        'max' => ['name' => 'HBO Max', 'tmdb' => [1899, 384], 'tvmaze' => ['HBO Max', 'Max', 'HBO'], 'url' => 'https://www.hbomax.com'],
        'paramount' => ['name' => 'Paramount+', 'tmdb' => [531], 'tvmaze' => ['Paramount+', 'Showtime'], 'url' => 'https://www.paramountplus.com'],
        'hulu' => ['name' => 'Hulu', 'tmdb' => [15], 'tvmaze' => ['Hulu', 'FX on Hulu'], 'url' => 'https://www.hulu.com'],
        'peacock' => ['name' => 'Peacock', 'tmdb' => [386], 'tvmaze' => ['Peacock'], 'url' => 'https://www.peacocktv.com'],
        'sky' => ['name' => 'Sky', 'tmdb' => [210, 29], 'tvmaze' => ['Sky Atlantic', 'Sky Max', 'Sky Showcase', 'Sky One', 'NOW', 'WOW'], 'url' => 'https://www.sky.com'],
        'bbc' => ['name' => 'BBC iPlayer', 'tmdb' => [38], 'tvmaze' => ['BBC iPlayer', 'BBC One', 'BBC Two', 'BBC Three', 'BBC Four'], 'url' => 'https://www.bbc.co.uk/iplayer', 'search' => 'https://www.bbc.co.uk/iplayer/search?q='],
        'mubi' => ['name' => 'MUBI', 'tmdb' => [11], 'tvmaze' => ['MUBI'], 'url' => 'https://mubi.com'],
        'crunchyroll' => ['name' => 'Crunchyroll', 'tmdb' => [283], 'tvmaze' => ['Crunchyroll'], 'url' => 'https://www.crunchyroll.com'],
        'playsuisse' => ['name' => 'Play Suisse', 'tmdb' => [691], 'tvmaze' => ['Play Suisse', 'SRF 1', 'SRF zwei', 'RTS 1'], 'url' => 'https://www.playsuisse.ch'],
    ],

    // Discover's "Any time" starts at this year. Null means the current year
    // (worked out per request, so a cached config doesn't get stuck in an old year).
    'browse_from_year' => null,

    // How far ahead the Upcoming page and the calendar feed look, and how far back "Just released" goes.
    'days_ahead' => 120,
    'days_back' => 7,
    // How far back the Upcoming timeline's "Show earlier" can go.
    'days_history' => 365,
];
