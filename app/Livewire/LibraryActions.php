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
 * The heart, the watch status and rating menu, and the Follow button for a show or movie, on cards and in
 * For you. They change in place; the toast says what happened.
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

    /** False for no heart (the thread, where only Follow fits). */
    #[Locked]
    public bool $withHeart = true;

    /** A smaller Follow button, for tight spots like the thread. */
    #[Locked]
    public bool $small = false;

    /** False for no status menu (the search lightbox, where a menu would be clipped). */
    #[Locked]
    public bool $withStatus = true;

    /** Cards show the status as just an icon; For you has room for the label. */
    #[Locked]
    public bool $compact = true;

    public ?string $status = null;

    /** Half stars, 1–10. */
    public ?int $rating = null;

    /** The status and rating come from your library row, if there is one. */
    public function mount(): void
    {
        if (! $this->withStatus) {
            return;
        }

        $relation = $this->type === 'movie' ? auth()->user()->libraryMovies() : auth()->user()->libraryShows();
        $item = match (true) {
            $this->type === 'movie' => $relation->where('movies.tmdb_id', $this->tmdbId),
            $this->tvmazeId !== null => $relation->where('shows.tvmaze_id', $this->tvmazeId),
            default => $relation->where('shows.tmdb_id', $this->tmdbId),
        };
        $row = $item->first()?->pivot;

        [$this->status, $this->rating] = [$row?->status, $row?->rating];
    }

    public function setStatus(?string $status, Library $library): void
    {
        $status = $status === '' ? null : $status;

        if (! $item = $this->resolve($library)) {
            return;
        }

        if (! $library->setStatus(auth()->user(), $item, $status)) {
            return;
        }

        $this->status = $status;
        $this->toast($status === null
            ? 'Cleared the status of '.$this->title($item).'.'
            : $this->title($item).': '.Library::STATUSES[$status].'.');
    }

    public function rate(?int $rating, Library $library): void
    {
        if (! $item = $this->resolve($library)) {
            return;
        }

        if (! $library->rate(auth()->user(), $item, $rating ?: null)) {
            return;
        }

        $this->rating = $rating ?: null;
        $this->toast($this->rating
            ? 'You rated '.$this->title($item).' '.Library::stars($this->rating).'.'
            : 'Took away your rating of '.$this->title($item).'.');
    }

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

    /** Stops following. A favourite, status or rating stays. */
    public function unfollow(Library $library): void
    {
        $item = match (true) {
            $this->type === 'movie' => Movie::where('tmdb_id', $this->tmdbId)->first(),
            $this->tvmazeId !== null => Show::where('tvmaze_id', $this->tvmazeId)->first(),
            default => Show::where('tmdb_id', $this->tmdbId)->first(),
        };

        if ($item) {
            $library->unfollow(auth()->user(), $item);
            $this->toast('Stopped following '.$this->title($item).'.');
        }

        $this->following = false;
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

        if (! $item = $this->resolve($library)) {
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
        return view('livewire.library-actions', ['statuses' => Library::statuses($this->type)]);
    }

    /** The show or movie, from the catalog or fetched into it. Null (with a toast) if that fails. */
    private function resolve(Library $library): Show|Movie|null
    {
        try {
            $item = match (true) {
                $this->type === 'movie' => $library->movie($this->tmdbId),
                $this->tvmazeId !== null => $library->show($this->tvmazeId),
                default => $library->tmdbShow($this->tmdbId),
            };
        } catch (Throwable $exception) {
            report($exception);
            $this->toast("That {$this->type} couldn’t be loaded. Try again in a moment.");

            return null;
        }

        if (! $item) {
            $this->toast('TVmaze doesn’t have that show, so it can’t be added.');
        }

        return $item;
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
