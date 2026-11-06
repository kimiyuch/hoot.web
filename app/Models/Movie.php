<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Movie extends Model
{
    protected $guarded = [];

    /** The people who follow it. */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /** Only what someone follows; the rest stays in the catalog but isn't refreshed. */
    public function scopeFollowed(Builder $query): void
    {
        $query->has('users');
    }

    protected function casts(): array
    {
        return [
            'cinema_date' => 'immutable_date',
            'digital_date' => 'immutable_date',
            'release_date' => 'immutable_date',
            'refreshed_at' => 'datetime',
            'rating' => 'float',
            'services' => 'array',
        ];
    }

    public function posterUrl(): ?string
    {
        return $this->poster_path ? 'https://image.tmdb.org/t/p/w342'.$this->poster_path : null;
    }

    public function backdropUrl(): ?string
    {
        return $this->backdrop_path ? 'https://image.tmdb.org/t/p/w1280'.$this->backdrop_path : null;
    }

    public function url(): string
    {
        return 'https://www.themoviedb.org/movie/'.$this->tmdb_id;
    }
}
