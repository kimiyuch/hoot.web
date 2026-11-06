<?php

use App\Http\Controllers\PossumController;
use App\Http\Controllers\SettingsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Guests get the landing page, signed-in users their For you page. Fortify adds login, register and the rest.
Route::get('/', [PossumController::class, 'home'])->name('home');

// Subscribe to this in Apple Calendar or Google Calendar. No login: the token says whose feed it is.
Route::get('/calendar/{token}.ics', [PossumController::class, 'calendar'])->name('calendar');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::controller(PossumController::class)->group(function () {
        Route::get('/discover', 'discover')->name('discover');
        Route::get('/search', 'search')->name('search');

        Route::post('/shows', 'followShow')->name('shows.follow');
        Route::delete('/shows/{show}', 'unfollowShow')->name('shows.unfollow');
        Route::post('/movies', 'followMovie')->name('movies.follow');
        Route::delete('/movies/{movie}', 'unfollowMovie')->name('movies.unfollow');
    });

    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings');
    Route::post('/settings/calendar', [SettingsController::class, 'resetCalendar'])->name('settings.calendar');
    Route::delete('/settings', [SettingsController::class, 'destroy'])->name('settings.destroy');
});

// The old pages, now part of For you and Discover.
Route::redirect('/following', '/', 301);
Route::get('/browse', fn (Request $request) => redirect()->route('discover', ['when' => 'any', 'type' => 'movie', ...$request->query()], 301));
Route::get('/premieres', fn (Request $request) => redirect()->route('discover', match ($request->query('tab')) {
    'recent' => ['when' => 'new'],
    'movies' => ['when' => 'soon', 'type' => 'movie'],
    default => ['when' => 'soon'],
}, 301));
