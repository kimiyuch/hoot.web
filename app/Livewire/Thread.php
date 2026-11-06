<?php

namespace App\Livewire;

use App\Services\Activities;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * For you's "From people you follow": the newest entries of the thread, and more with every "Load more".
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
        return view('livewire.thread', $activities->thread(auth()->user(), $this->limit));
    }
}
