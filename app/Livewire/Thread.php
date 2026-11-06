<?php

namespace App\Livewire;

use App\Services\Activities;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * For you's "From people you follow": the newest entries of the thread, and more with every "Load more".
 * Each show or movie in it can be followed from there.
 */
class Thread extends Component
{
    public const PAGE = 8;

    public int $limit = self::PAGE;

    public function loadMore(): void
    {
        $this->limit += self::PAGE;
    }

    public function render(Activities $activities): View
    {
        $user = auth()->user();

        return view('livewire.thread', [
            ...$activities->thread($user, $this->limit),
            // What you follow already, so the Follow buttons start in the right state.
            'followed' => [
                'show' => $user->shows()->pluck('tvmaze_id')->flip(),
                'movie' => $user->movies()->pluck('tmdb_id')->flip(),
            ],
        ]);
    }
}
