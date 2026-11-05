<?php

use App\Http\Controllers\PossumController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::controller(PossumController::class)->group(function () {
    Route::get('/', 'home')->name('home');
    Route::get('/discover', 'discover')->name('discover');
    Route::get('/search', 'search')->name('search');

    Route::post('/shows', 'followShow')->name('shows.follow');
    Route::delete('/shows/{show}', 'unfollowShow')->name('shows.unfollow');
    Route::post('/movies', 'followMovie')->name('movies.follow');
    Route::delete('/movies/{movie}', 'unfollowMovie')->name('movies.unfollow');

    // Subscribe to this in Apple Calendar or Google Calendar.
    Route::get('/calendar.ics', 'calendar')->name('calendar');
});

// The old pages, now part of For you and Discover.
Route::redirect('/following', '/', 301);
Route::get('/browse', fn (Request $request) => redirect()->route('discover', ['when' => 'any', 'type' => 'movie', ...$request->query()], 301));
Route::get('/premieres', fn (Request $request) => redirect()->route('discover', match ($request->query('tab')) {
    'recent' => ['when' => 'new'],
    'movies' => ['when' => 'soon', 'type' => 'movie'],
    default => ['when' => 'soon'],
}, 301));
