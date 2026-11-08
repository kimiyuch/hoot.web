<?php

use App\Models\User;
use App\Services\Digest;
use App\Services\Library;
use App\Services\Premieres;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('hoot:refresh {--all : Refresh every show, not just the ones TVmaze says have changed}', function (Library $library, Premieres $premieres) {
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
Schedule::command('hoot:refresh')->everySixHours()->withoutOverlapping();

Artisan::command('hoot:digest {username? : Only this account (for trying it), even with the mail switched off}', function (Digest $digest, ?string $username = null) {
    $users = $username ? User::where('username', ltrim(strtolower($username), '@'))->get() : $digest->recipients()->get();
    $sent = $users->filter(fn (User $user) => $digest->send($user))->count();
    $this->info("Sent {$sent} of {$users->count()} weekly mails (the rest had nothing to tell).");
})->purpose('Send the weekly mail: what comes out this week, and new followers');

// Monday mornings, Zurich time.
Schedule::command('hoot:digest')->weeklyOn(1, '7:00')->timezone(config('hoot.timezone'));

Artisan::command('hoot:suspend {username : Without the @} {--lift : Lift the suspension}', function (string $username) {
    $user = User::where('username', ltrim(strtolower($username), '@'))->first();

    if (! $user) {
        $this->error("There's no @{$username}.");

        return 1;
    }

    $user->forceFill(['suspended_at' => $this->option('lift') ? null : now()])->save();
    $this->info($this->option('lift')
        ? "@{$user->username} can log in again and is visible as before."
        : "@{$user->username} is suspended: signed out, can't log in, and hidden from everyone. Lift it with --lift.");
})->purpose('Suspend an account (after a report), or lift the suspension');
