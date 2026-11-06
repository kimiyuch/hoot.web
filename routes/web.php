<?php

use App\Http\Controllers\FavouriteController;
use App\Http\Controllers\HootController;
use App\Http\Controllers\SettingsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// The landing page. Signed-in users go on to For you. Fortify adds login, register and the rest.
Route::get('/', [HootController::class, 'welcome'])->name('home');

// Subscribe to this in Apple Calendar or Google Calendar. No login: the token says whose feed it is.
Route::get('/calendar/{token}.ics', [HootController::class, 'calendar'])->name('calendar');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::controller(HootController::class)->group(function () {
        Route::get('/for-you', 'forYou')->name('for-you');
        Route::get('/discover', 'discover')->name('discover');
        Route::get('/search', 'search')->name('search');

        Route::post('/shows', 'followShow')->name('shows.follow');
        Route::delete('/shows/{show}', 'unfollowShow')->name('shows.unfollow');
        Route::post('/movies', 'followMovie')->name('movies.follow');
        Route::delete('/movies/{movie}', 'unfollowMovie')->name('movies.unfollow');
    });

    Route::controller(FavouriteController::class)->prefix('favourites')->name('favourites.')->group(function () {
        Route::post('/shows', 'storeShow')->name('shows.store');
        Route::delete('/shows/{show}', 'destroyShow')->name('shows.destroy');
        Route::post('/shows/{show}/move', 'moveShow')->name('shows.move');
        Route::post('/movies', 'storeMovie')->name('movies.store');
        Route::delete('/movies/{movie}', 'destroyMovie')->name('movies.destroy');
        Route::post('/movies/{movie}/move', 'moveMovie')->name('movies.move');
    });

    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings');
    Route::post('/settings/calendar', [SettingsController::class, 'resetCalendar'])->name('settings.calendar');
    Route::delete('/settings', [SettingsController::class, 'destroy'])->name('settings.destroy');
});

// The old pages, now part of For you and Discover.
Route::redirect('/following', '/for-you', 301);
Route::get('/browse', fn (Request $request) => redirect()->route('discover', ['when' => 'any', 'type' => 'movie', ...$request->query()], 301));
Route::get('/premieres', fn (Request $request) => redirect()->route('discover', match ($request->query('tab')) {
    'recent' => ['when' => 'new'],
    'movies' => ['when' => 'soon', 'type' => 'movie'],
    default => ['when' => 'soon'],
}, 301));
