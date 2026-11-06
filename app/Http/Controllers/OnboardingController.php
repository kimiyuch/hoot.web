<?php

namespace App\Http\Controllers;

use App\Actions\Fortify\ProfileValidationRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/**
 * The one-time screen for accounts from before profiles: Hoot is social now, pick a username.
 */
class OnboardingController extends Controller
{
    use ProfileValidationRules;

    public function edit(Request $request): View|RedirectResponse
    {
        return $request->user()->username ? redirect()->route('for-you') : view('auth.onboarding');
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $input = ['username' => self::cleanUsername($request->input('username')), 'visibility' => $request->input('visibility')];

        Validator::make($input, [
            'username' => $this->usernameRules($user),
            'visibility' => ['required', 'in:public,private'],
        ], $this->usernameMessages())->validate();

        $user->forceFill(['username' => $input['username'], 'is_private' => $input['visibility'] === 'private'])->save();

        return redirect()->intended(route('for-you'))->with('status', "You’re @{$user->username} now.");
    }
}
