<?php

use App\Services\Library;
use App\Services\Premieres;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('possum:refresh {--all : Refresh every show, not just the ones TVmaze says have changed}', function (Library $library, Premieres $premieres) {
    // TVmaze's full schedule is a 10+ MB JSON response.
    ini_set('memory_limit', '512M');

    $result = $library->refresh($this->option('all'));
    $this->info("Refreshed {$result['shows']} shows and {$result['movies']} movies.");
    foreach ($result['failed'] as $name) {
        $this->warn("Couldn't refresh {$name}.");
    }

    $found = $premieres->refresh();
    $this->info('Found '.count($found['shows']).' show premieres and '.count($found['movies']).' upcoming movies.');
})->purpose('Refresh the followed shows and movies from TVmaze and TMDB, and the premieres');

// On Hostpoint, a cron job runs "php artisan schedule:run" every few minutes.
Schedule::command('possum:refresh')->everySixHours()->withoutOverlapping();
