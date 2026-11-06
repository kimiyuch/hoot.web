<?php

namespace App\Livewire;

use App\Models\User;
use App\Services\Network;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Follow and unfollow a person, and on their profile block or unblock them. On your own followers list
 * it can also remove a follower; in Settings it's just Unblock. Changes happen in place with a toast,
 * except blocking and unblocking on a profile, which reload it (what you may see changes).
 */
class PersonActions extends Component
{
    #[Locked]
    public int $userId;

    /** profile, list, followers (your own followers list), or blocked (Settings) */
    #[Locked]
    public string $context = 'list';

    public bool $following = false;

    public bool $blocked = false;

    public bool $removed = false;

    public function mount(): void
    {
        $person = $this->person();
        $this->following = auth()->user()->isFollowing($person);
        $this->blocked = auth()->user()->hasBlocked($person);
    }

    public function follow(Network $network): void
    {
        $user = auth()->user();
        $person = $this->person();

        if (RateLimiter::tooManyAttempts('follow:'.$user->id, 60)) {
            $this->toast('That’s a lot of following. Wait a minute and try again.');

            return;
        }

        RateLimiter::hit('follow:'.$user->id);

        if (! $network->follow($user, $person)) {
            $this->toast('You can’t follow '.$person->name.'.');

            return;
        }

        $this->following = true;
        $this->toast('Following '.$person->name.'.');
    }

    public function unfollow(Network $network): void
    {
        $person = $this->person();
        $network->unfollow(auth()->user(), $person);

        $this->following = false;
        $this->toast('Stopped following '.$person->name.'.');
    }

    public function removeFollower(Network $network): void
    {
        abort_unless($this->context === 'followers', 403);

        $person = $this->person();
        $network->removeFollower(auth()->user(), $person);

        $this->removed = true;
        $this->toast($person->name.' no longer follows you.');
    }

    public function block(Network $network): void
    {
        $person = $this->person();
        $network->block(auth()->user(), $person);

        session()->flash('status', 'You blocked '.$person->name.'.');
        $this->redirect($person->profileUrl());
    }

    public function unblock(Network $network): void
    {
        $person = $this->person();
        $network->unblock(auth()->user(), $person);

        if ($this->context === 'profile') {
            session()->flash('status', 'You unblocked '.$person->name.'.');
            $this->redirect($person->profileUrl());

            return;
        }

        $this->blocked = false;
        $this->toast('You unblocked '.$person->name.'.');
    }

    public function render(Network $network): View
    {
        $person = $this->person();

        return view('livewire.person-actions', [
            'person' => $person,
            'self' => auth()->user()->is($person),
            'canFollow' => $network->canFollow(auth()->user(), $person),
        ]);
    }

    private function person(): User
    {
        return User::findOrFail($this->userId);
    }

    private function toast(string $message): void
    {
        $this->dispatch('toast', message: $message);
    }
}
