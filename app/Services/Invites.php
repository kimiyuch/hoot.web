<?php

namespace App\Services;

use App\Models\User;

/**
 * Invite links (/invite/{username}). A guest who opens one is sent to sign up with the inviter remembered in
 * the session; the new account notes who invited it, and once its email is confirmed it follows them. That's
 * accepted straight away even on a private account, since the inviter handed out the link.
 */
class Invites
{
    private const SESSION = 'invited_by';

    public function __construct(private Network $network) {}

    /** Remembers who invited this visitor, until they sign up. */
    public function remember(User $inviter): void
    {
        session([self::SESSION => $inviter->id]);
    }

    /** Who invited this visitor, if they came through a link that still works. */
    public function inviter(): ?User
    {
        $id = session(self::SESSION);

        return $id ? User::whereKey($id)->whereNotNull('username')->whereNull('suspended_at')->first() : null;
    }

    /** Notes on a new account who invited it. */
    public function attach(User $user): void
    {
        if ($inviter = $this->inviter()) {
            $user->forceFill(['invited_by_id' => $inviter->id])->save();
        }

        session()->forget(self::SESSION);
    }

    /** Once the email is confirmed: follow the inviter. */
    public function follow(User $user): void
    {
        $inviter = $user->invitedBy;

        if ($inviter) {
            $this->network->follow($user, $inviter, invited: true);
        }
    }
}
