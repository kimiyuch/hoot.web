<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    protected $guarded = [];

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
