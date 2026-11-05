<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Episode extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['airs_at' => 'immutable_datetime'];
    }

    public function show(): BelongsTo
    {
        return $this->belongsTo(Show::class);
    }

    /** "S01E05", or "Special" for episodes without a number. */
    public function code(): string
    {
        if ($this->number === null) {
            return $this->season ? sprintf('S%02d Special', $this->season) : 'Special';
        }

        return sprintf('S%02dE%02d', $this->season, $this->number);
    }
}
