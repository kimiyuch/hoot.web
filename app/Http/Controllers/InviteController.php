<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Invites;
use Illuminate\Http\RedirectResponse;

class InviteController extends Controller
{
    /**
     * An invite link. Signed in, it's just the profile. Otherwise the inviter is remembered and it's on to sign
     * up; logging in instead also ends on the profile.
     */
    public function __invoke(User $user, Invites $invites): RedirectResponse
    {
        abort_if($user->isSuspended(), 404);

        if (auth()->check()) {
            return redirect($user->profileUrl());
        }

        $invites->remember($user);
        redirect()->setIntendedUrl($user->profileUrl());

        return redirect()->route('register');
    }
}
