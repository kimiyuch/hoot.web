<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\PeopleNews;
use Illuminate\Support\Facades\DB;

/**
 * Following people, and blocking them. Everything that changes the follows and blocks tables goes
 * through here, so the rules live in one place. It also sends the in-app notifications about it
 * (PeopleNews) and takes them back when a follow or request is undone, so they never outlive what they say.
 */
class Network
{
    public function __construct(private Activities $activities) {}

    /**
     * Whether $follower may follow $user (or, if the account is private, ask to): not yourself, not across a
     * block, not a suspended account.
     */
    public function canFollow(User $follower, User $user): bool
    {
        return ! $follower->is($user)
            && $user->username !== null
            && ! $user->isSuspended()
            && ! $follower->blocksEitherWay($user);
    }

    /**
     * Follows $user, or asks to if their account is private (the follow waits, with accepted_at null, until
     * they accept). Through $user's own invite link it's accepted right away. False if that isn't allowed;
     * true if it worked or was already done.
     */
    public function follow(User $follower, User $user, bool $invited = false): bool
    {
        if ($follower->isFollowing($user) || $follower->hasRequestedToFollow($user)) {
            return true;
        }

        if (! $this->canFollow($follower, $user)) {
            return false;
        }

        $accepted = ! $user->is_private || $invited;

        $added = DB::table('follows')->insertOrIgnore([
            'follower_id' => $follower->id,
            'followed_id' => $user->id,
            'accepted_at' => $accepted ? now() : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($added && $accepted) {
            $this->activities->record($follower, 'followed_user', $user);
        }
        if ($added) {
            $user->notify(new PeopleNews($accepted ? 'followed' : 'requested', $follower, $invited));
        }

        return true;
    }

    /** Lets $follower follow you. False if there was no request from them. */
    public function accept(User $user, User $follower): bool
    {
        $accepted = DB::table('follows')->where('follower_id', $follower->id)->where('followed_id', $user->id)
            ->whereNull('accepted_at')->update(['accepted_at' => now(), 'updated_at' => now()]);

        if ($accepted) {
            $this->activities->record($follower, 'followed_user', $user);
            // The request is dealt with; the news is theirs now.
            $this->unsay($user, $follower, ['requested']);
            $follower->notify(new PeopleNews('accepted', $user));
        }

        return (bool) $accepted;
    }

    /** Says no to a request. They aren't told, and they can ask again. */
    public function decline(User $user, User $follower): void
    {
        DB::table('follows')->where('follower_id', $follower->id)->where('followed_id', $user->id)->whereNull('accepted_at')->delete();
        $this->unsay($user, $follower, ['requested']);
    }

    /** Accepts every waiting request, for when an account goes public. */
    public function acceptAll(User $user): void
    {
        foreach ($user->followRequests()->get() as $follower) {
            $this->accept($user, $follower);
        }
    }

    /** Stops following $user, or takes back the request. */
    public function unfollow(User $follower, User $user): void
    {
        $this->forget($follower, $user);
        $this->unsay($user, $follower, ['followed', 'requested']);
        $this->activities->retract($follower, ['followed_user'], $user);
    }

    /** Takes $follower off your followers, without blocking them. They can follow you again. */
    public function removeFollower(User $user, User $follower): void
    {
        $this->forget($follower, $user);
        $this->unsay($user, $follower, ['followed']);
        $this->activities->retract($follower, ['followed_user'], $user);
    }

    /** Blocks $user: the follows between you go, both ways, and you stop seeing each other. */
    public function block(User $blocker, User $user): void
    {
        if ($blocker->is($user)) {
            return;
        }

        DB::transaction(function () use ($blocker, $user) {
            $this->forget($blocker, $user);
            $this->forget($user, $blocker);

            DB::table('blocks')->insertOrIgnore([
                'blocker_id' => $blocker->id,
                'blocked_id' => $user->id,
                'created_at' => now(),
            ]);

            $this->activities->forgetBetween($blocker, $user);
            $this->unsay($blocker, $user, PeopleNews::KINDS);
            $this->unsay($user, $blocker, PeopleNews::KINDS);
        });
    }

    /** Lifts the block. Nobody follows anybody again until they choose to. */
    public function unblock(User $blocker, User $user): void
    {
        DB::table('blocks')->where('blocker_id', $blocker->id)->where('blocked_id', $user->id)->delete();
    }

    /** Takes back what $to was told about $about, of these kinds. */
    private function unsay(User $to, User $about, array $kinds): void
    {
        $to->notifications()->where('type', 'people')
            ->where('data->user_id', $about->id)->whereIn('data->kind', $kinds)->delete();
    }

    private function forget(User $follower, User $user): void
    {
        DB::table('follows')->where('follower_id', $follower->id)->where('followed_id', $user->id)->delete();
    }
}
