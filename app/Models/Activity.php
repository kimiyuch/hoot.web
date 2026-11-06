<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An entry in the thread. `data.items` is a list of snapshots: {kind: show|movie|user, id, title, poster, url}
 * plus `rating` (half stars) on finished and rated entries. Written only by App\Services\Activities.
 */
class Activity extends Model
{
    protected $guarded = [];

    /** Entries that hold several items (the rest are about one show or movie). */
    public const COLLAPSING = ['followed', 'favourited', 'followed_user'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return ['data' => 'array'];
    }

    /** @return list<array<string, mixed>> */
    public function items(): array
    {
        return $this->data['items'] ?? [];
    }
}
