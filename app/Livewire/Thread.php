<?php

namespace App\Livewire;

use App\Models\User;
use App\Services\Activities;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * For you's "From people you follow": the newest entries of the thread, and more with every "Load more".
 * Each show or movie in it can be followed from there. With a userId, it's that person's "Lately" on their profile.
 */
class Thread extends Component
{
    public const PAGE = 8;

    /** On a profile, a shorter list: five at a time. */
    public const PROFILE_PAGE = 5;

    public int $limit = self::PAGE;

    /** Set on a profile: only this person's entries. */
    public ?int $userId = null;

    public function mount(): void
    {
        $this->limit = $this->page();
    }

    public function loadMore(): void
    {
        $this->limit += $this->page();
    }

    private function page(): int
    {
        return $this->userId ? self::PROFILE_PAGE : self::PAGE;
    }

    public function render(Activities $activities): View
    {
        $user = auth()->user();

        return view('livewire.thread', [
            ...($this->userId
                ? $activities->of(User::findOrFail($this->userId), $user, $this->limit)
                : $activities->thread($user, $this->limit)),
            'profile' => $this->userId !== null,
            // What you follow already, so the Follow buttons start in the right state.
            'followed' => [
                'show' => $user->shows()->pluck('tvmaze_id')->flip(),
                'movie' => $user->movies()->pluck('tmdb_id')->flip(),
            ],
        ]);
    }
}
