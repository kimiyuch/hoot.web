<?php

namespace App\Livewire;

use App\Models\Movie;
use App\Models\Show;
use App\Services\Library;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Throwable;

/**
 * The heart and the Follow button for a show or movie, on cards and in For you. They change in place;
 * the toast says what happened.
 */
class LibraryActions extends Component
{
    /** show or movie */
    #[Locked]
    public string $type;

    #[Locked]
    public ?int $tvmazeId = null;

    #[Locked]
    public ?int $tmdbId = null;

    #[Locked]
    public string $label = '';

    /** False for just the heart (For you's Following has its own Unfollow). */
    #[Locked]
    public bool $withFollow = true;

    public bool $following = false;

    /** The catalog id while it's one of your favourites. */
    public ?int $favourite = null;

    public function follow(Library $library): void
    {
        $user = auth()->user();

        try {
            $item = match (true) {
                $this->type === 'movie' => $library->followMovie($user, $this->tmdbId),
                $this->tvmazeId !== null => $library->followShow($user, $this->tvmazeId),
                default => $library->followTmdbShow($user, $this->tmdbId),
            };
        } catch (Throwable $exception) {
            report($exception);
            $this->toast("That {$this->type} couldn’t be loaded. Try again in a moment.");

            return;
        }

        if (! $item) {
            $this->toast('TVmaze doesn’t have that show, so there are no episode dates for it.');

            return;
        }

        $this->following = true;
        $this->toast('Following '.$this->title($item).'.');
    }

    public function toggleFavourite(Library $library): void
    {
        $user = auth()->user();

        if ($this->favourite) {
            $item = $this->model()::find($this->favourite);

            if ($item) {
                $library->unfavourite($user, $item);
                $this->toast('Removed '.$this->title($item).' from your favourites.');
            }

            $this->favourite = null;

            return;
        }

        if (! $library->hasRoomForFavourite($user, $this->model())) {
            $this->toast($this->fullMessage());

            return;
        }

        try {
            $item = match (true) {
                $this->type === 'movie' => $library->movie($this->tmdbId),
                $this->tvmazeId !== null => $library->show($this->tvmazeId),
                default => $library->tmdbShow($this->tmdbId),
            };
        } catch (Throwable $exception) {
            report($exception);
            $this->toast("That {$this->type} couldn’t be loaded. Try again in a moment.");

            return;
        }

        if (! $item) {
            $this->toast('TVmaze doesn’t have that show, so it can’t be added.');

            return;
        }

        if (! $library->favourite($user, $item)) {
            $this->toast($this->fullMessage());

            return;
        }

        $this->favourite = $item->id;
        $this->toast($this->title($item).' is one of your favourites.');
    }

    public function render(): View
    {
        return view('livewire.library-actions');
    }

    /** @return class-string<Show|Movie> */
    private function model(): string
    {
        return $this->type === 'movie' ? Movie::class : Show::class;
    }

    private function title(Show|Movie $item): string
    {
        return $item instanceof Show ? $item->name : $item->title;
    }

    private function fullMessage(): string
    {
        return 'You already have '.Library::MAX_FAVOURITES.' favourite '.$this->type.'s. Remove one in Settings first.';
    }

    private function toast(string $message): void
    {
        $this->dispatch('toast', message: $message);
    }
}
