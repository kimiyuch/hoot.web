<?php

namespace App\Livewire;

use App\Models\Movie;
use App\Models\Show;
use App\Services\Library;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Your favourite shows or movies in Settings, in your order: move one up or down, or take it out.
 */
class FavouritesList extends Component
{
    /** show or movie */
    #[Locked]
    public string $type;

    public function move(int $id, string $direction, Library $library): void
    {
        if ($item = $this->find($id)) {
            $library->moveFavourite(auth()->user(), $item, $direction === 'up' ? -1 : 1);
        }
    }

    public function remove(int $id, Library $library): void
    {
        if ($item = $this->find($id)) {
            $library->unfavourite(auth()->user(), $item);
            $this->dispatch('toast', message: 'Removed '.($item instanceof Show ? $item->name : $item->title).' from your favourites.');
        }
    }

    public function render(): View
    {
        $user = auth()->user();

        return view('livewire.favourites-list', [
            'items' => $this->type === 'movie' ? $user->favouriteMovies()->get() : $user->favouriteShows()->get(),
            'max' => Library::MAX_FAVOURITES,
        ]);
    }

    private function find(int $id): Show|Movie|null
    {
        return ($this->type === 'movie' ? Movie::class : Show::class)::find($id);
    }
}
