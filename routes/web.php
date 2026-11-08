<?php

use App\Http\Controllers\HootController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// The landing page. Signed-in users go on to For you. Fortify adds login, register and the rest.
Route::get('/', [HootController::class, 'welcome'])->name('home');

// What's stored, who sees what, and the few rules. Public, so it can be read before signing up.
Route::view('/privacy', 'privacy')->name('privacy');
Route::view('/imprint', 'imprint')->name('imprint');

// The source of the link preview image (scripts/og-image.sh renders it to public/images/og.png). Local only.
if (app()->isLocal()) {
    Route::view('/og-image', 'og-image');
}

// Subscribe to this in Apple Calendar or Google Calendar. No login: the token says whose feed it is.
Route::get('/calendar/{token}.ics', [HootController::class, 'calendar'])->name('calendar');

// Accounts from before profiles pick a username here first.
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/choose-username', [OnboardingController::class, 'edit'])->name('onboarding');
    Route::post('/choose-username', [OnboardingController::class, 'update'])->name('onboarding.update');
});

Route::middleware(['auth', 'verified', 'username'])->group(function () {
    Route::controller(ProfileController::class)->where(['user' => '[a-z0-9_]+'])->group(function () {
        Route::get('/@{user:username}', 'show')->name('profile');
        Route::get('/@{user:username}/followers', 'followers')->name('profile.followers');
        Route::get('/@{user:username}/following', 'following')->name('profile.following');
    });

    Route::controller(HootController::class)->group(function () {
        Route::get('/for-you', 'forYou')->name('for-you');
        Route::get('/discover', 'discover')->name('discover');
        Route::get('/search', 'search')->name('search');

        Route::post('/shows', 'followShow')->name('shows.follow');
        Route::delete('/shows/{show}', 'unfollowShow')->name('shows.unfollow');
        Route::post('/movies', 'followMovie')->name('movies.follow');
        Route::delete('/movies/{movie}', 'unfollowMovie')->name('movies.unfollow');
    });

    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings');
    Route::put('/settings/hidden', [SettingsController::class, 'updateHidden'])->name('settings.hidden');
    Route::put('/settings/extras', [SettingsController::class, 'updateExtras'])->name('settings.extras');
    Route::post('/settings/calendar', [SettingsController::class, 'resetCalendar'])->name('settings.calendar');
    Route::get('/settings/export', [SettingsController::class, 'export'])->name('settings.export');
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
