<?php

namespace App\Livewire;

use App\Models\User;
use App\Services\Network;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Follow and unfollow a person (or ask to, if their account is private, and take the request back), and on
 * their profile block, unblock or report them. On your own followers list it can also remove a follower; in
 * Settings it's Unblock, or Accept and Decline for follow requests. Changes happen in place with a toast,
 * except blocking and unblocking on a profile, which reload it (what you may see changes).
 */
class PersonActions extends Component
{
    #[Locked]
    public int $userId;

    /** profile, list, followers (your own followers list), blocked or requests (Settings) */
    #[Locked]
    public string $context = 'list';

    public bool $following = false;

    public bool $blocked = false;

    /** You asked to follow them (a private account) and they haven't answered. */
    public bool $requested = false;

    public bool $removed = false;

    /** For requests: accepted or declined, once answered. */
    public ?string $answer = null;

    public function mount(): void
    {
        $person = $this->person();
        $this->following = auth()->user()->isFollowing($person);
        $this->requested = ! $this->following && auth()->user()->hasRequestedToFollow($person);
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

        $this->following = $user->isFollowing($person);
        $this->requested = ! $this->following;
        $this->toast($this->following
            ? 'Following '.$person->name.'.'
            : 'Asked to follow '.$person->name.'. You’ll see their profile once they accept.');
    }

    public function unfollow(Network $network): void
    {
        $person = $this->person();
        $network->unfollow(auth()->user(), $person);

        $this->toast($this->requested ? 'Took back your request to follow '.$person->name.'.' : 'Stopped following '.$person->name.'.');
        $this->following = $this->requested = false;
    }

    public function accept(Network $network): void
    {
        abort_unless($this->context === 'requests', 403);

        $person = $this->person();

        if ($network->accept(auth()->user(), $person)) {
            $this->answer = 'accepted';
            $this->toast($person->name.' follows you now.');
            $this->dispatch('follow-request-answered');
        }
    }

    public function decline(Network $network): void
    {
        abort_unless($this->context === 'requests', 403);

        $person = $this->person();
        $network->decline(auth()->user(), $person);

        $this->answer = 'declined';
        $this->toast('Declined '.$person->name.'’s request.');
        $this->dispatch('follow-request-answered');
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
            'reportUrl' => $this->reportUrl($person),
        ]);
    }

    /** A mail to the admin about this profile, with the link already in it. */
    private function reportUrl(User $person): ?string
    {
        $to = config('hoot.report_email');

        return $to ? 'mailto:'.$to.'?'.http_build_query([
            'subject' => 'Report: @'.$person->username,
            'body' => "Profile: {$person->profileUrl()}\n\nWhat's wrong:\n",
        ], '', '&', PHP_QUERY_RFC3986) : null;
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
