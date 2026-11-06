<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable(['name', 'email', 'password', 'username', 'bio', 'is_private'])]
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
            'is_private' => 'boolean',
        ];
    }

    /** The profile page, /@username. */
    public function profileUrl(): string
    {
        return route('profile', $this);
    }

    /** The first letter of the name, for the avatar. */
    public function initial(): string
    {
        return Str::upper(Str::substr($this->name, 0, 1));
    }

    /**
     * Whether $viewer may see this profile's library and lists: on public profiles, on your own, and on a
     * private one you follow. Never across a block.
     */
    public function profileVisibleTo(?User $viewer): bool
    {
        if ($viewer?->is($this)) {
            return true;
        }

        if ($viewer && $this->blocksEitherWay($viewer)) {
            return false;
        }

        return ! $this->is_private || ($viewer && $this->isFollowedBy($viewer));
    }

    /** The people you follow (accepted follows only). */
    public function following(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'followed_id')
            ->withPivot('accepted_at')->withTimestamps()->wherePivotNotNull('accepted_at');
    }

    /** The people who follow you (accepted follows only). */
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'followed_id', 'follower_id')
            ->withPivot('accepted_at')->withTimestamps()->wherePivotNotNull('accepted_at');
    }

    /** The people you blocked. */
    public function blocked(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'blocks', 'blocker_id', 'blocked_id')->withPivot('created_at');
    }

    public function isFollowing(User $user): bool
    {
        return $this->following()->whereKey($user->id)->exists();
    }

    public function isFollowedBy(User $user): bool
    {
        return $user->isFollowing($this);
    }

    public function hasBlocked(User $user): bool
    {
        return $this->blocked()->whereKey($user->id)->exists();
    }

    /** Whether one of the two blocked the other. */
    public function blocksEitherWay(User $user): bool
    {
        return $this->hasBlocked($user) || $user->hasBlocked($this);
    }

    /** Leaves out everyone $viewer blocked or was blocked by, and anyone without a username yet. */
    public function scopeVisibleTo(Builder $query, User $viewer): void
    {
        $query->whereNotNull('users.username')
            ->whereNotIn('users.id', fn ($blocks) => $blocks->select('blocked_id')->from('blocks')->where('blocker_id', $viewer->id))
            ->whereNotIn('users.id', fn ($blocks) => $blocks->select('blocker_id')->from('blocks')->where('blocked_id', $viewer->id));
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
