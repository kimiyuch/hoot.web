<?php

namespace App\Models;

use App\Services\StreamingServices;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Show extends Model
{
    protected $guarded = [];

    /** The people who have it in their library: following it, as a favourite or both. */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /** Only what someone follows; the rest (favourites only) stays in the catalog but isn't refreshed. */
    public function scopeFollowed(Builder $query): void
    {
        $query->whereHas('users', fn (Builder $users) => $users->where('show_user.following', true));
    }

    protected function casts(): array
    {
        return ['refreshed_at' => 'datetime', 'rating' => 'float', 'genres' => 'array', 'cast' => 'array'];
    }

    /** The streaming service key (config/hoot.php) of the show's channel, if it's one of them. */
    public function service(): ?string
    {
        return StreamingServices::forChannel($this->channel);
    }

    public function episodes(): HasMany
    {
        return $this->hasMany(Episode::class);
    }

    public function nextEpisode(): HasOne
    {
        return $this->hasOne(Episode::class)->ofMany(['airs_at' => 'min'], fn ($query) => $query->where('airs_at', '>=', now()));
    }

    public function previousEpisode(): HasOne
    {
        return $this->hasOne(Episode::class)->ofMany(['airs_at' => 'max'], fn ($query) => $query->where('airs_at', '<', now()));
    }
}
