<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token', 'calendar_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::creating(fn (User $user) => $user->calendar_token ??= Str::random(40));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** Every show in your library: followed, a favourite or both. */
    public function libraryShows(): BelongsToMany
    {
        return $this->belongsToMany(Show::class)->withPivot('created_at', 'following', 'favourite_position');
    }

    /** Every movie in your library: followed, a favourite or both. */
    public function libraryMovies(): BelongsToMany
    {
        return $this->belongsToMany(Movie::class)->withPivot('created_at', 'following', 'favourite_position');
    }

    /** The shows you follow: their episodes are in For you and the calendar feed. */
    public function shows(): BelongsToMany
    {
        return $this->libraryShows()->wherePivot('following', true);
    }

    /** The movies you follow. */
    public function movies(): BelongsToMany
    {
        return $this->libraryMovies()->wherePivot('following', true);
    }

    /** Your favourite shows, in your order. */
    public function favouriteShows(): BelongsToMany
    {
        return $this->libraryShows()->wherePivotNotNull('favourite_position')->orderByPivot('favourite_position');
    }

    /** Your favourite movies, in your order. */
    public function favouriteMovies(): BelongsToMany
    {
        return $this->libraryMovies()->wherePivotNotNull('favourite_position')->orderByPivot('favourite_position');
    }

    /** The calendar feed, for Copy link. */
    public function calendarUrl(): string
    {
        return route('calendar', $this->calendar_token);
    }

    /** The same feed as a webcal:// link, which calendar apps open as a subscription. */
    public function calendarSubscribeUrl(): string
    {
        return preg_replace('#^https?://#', 'webcal://', $this->calendarUrl());
    }

    /** A new secret for the calendar feed, so the old URL stops working. */
    public function resetCalendarToken(): void
    {
        $this->forceFill(['calendar_token' => Str::random(40)])->save();
    }
}
