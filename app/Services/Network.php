<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Following people, and blocking them. Everything that changes the follows and blocks tables goes
 * through here, so the rules live in one place.
 */
class Network
{
    /**
     * Whether $follower may start following $user: not yourself, not across a block, and not a private
     * account yet (follow requests come with step 5).
     */
    public function canFollow(User $follower, User $user): bool
    {
        return ! $follower->is($user)
            && $user->username !== null
            && ! $user->is_private
            && ! $follower->blocksEitherWay($user);
    }

    /** Follows $user. False if that isn't allowed; true if it worked or you already did. */
    public function follow(User $follower, User $user): bool
    {
        if ($follower->isFollowing($user)) {
            return true;
        }

        if (! $this->canFollow($follower, $user)) {
            return false;
        }

        DB::table('follows')->insertOrIgnore([
            'follower_id' => $follower->id,
            'followed_id' => $user->id,
            'accepted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return true;
    }

    public function unfollow(User $follower, User $user): void
    {
        $this->forget($follower, $user);
    }

    /** Takes $follower off your followers, without blocking them. They can follow you again. */
    public function removeFollower(User $user, User $follower): void
    {
        $this->forget($follower, $user);
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
        });
    }

    /** Lifts the block. Nobody follows anybody again until they choose to. */
    public function unblock(User $blocker, User $user): void
    {
        DB::table('blocks')->where('blocker_id', $blocker->id)->where('blocked_id', $user->id)->delete();
    }

    private function forget(User $follower, User $user): void
    {
        DB::table('follows')->where('follower_id', $follower->id)->where('followed_id', $user->id)->delete();
    }
}
