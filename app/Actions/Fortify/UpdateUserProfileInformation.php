<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Services\Network;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    use ProfileValidationRules;

    /**
     * Validate and update the given user's profile information.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function update(User $user, array $input): void
    {
        $input['username'] = self::cleanUsername($input['username'] ?? '');

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'username' => $this->usernameRules($user),
            'bio' => ['nullable', 'string', 'max:160'],
            'visibility' => ['required', 'in:public,private'],
            'shares_activity' => ['sometimes', 'boolean'],

            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
        ], $this->usernameMessages())->validateWithBag('updateProfileInformation');

        $wasPrivate = $user->is_private;

        $user->forceFill([
            'username' => $input['username'],
            'bio' => filled($input['bio'] ?? null) ? trim($input['bio']) : null,
            'is_private' => $input['visibility'] === 'private',
            'shares_activity' => (bool) ($input['shares_activity'] ?? $user->shares_activity ?? true),
        ]);

        // Going public: whoever was waiting can follow now.
        if ($wasPrivate && ! $user->is_private) {
            app(Network::class)->acceptAll($user);
        }

        if ($input['email'] !== $user->email &&
            $user instanceof MustVerifyEmail) {
            $this->updateVerifiedUser($user, $input);
        } else {
            $user->forceFill([
                'name' => $input['name'],
                'email' => $input['email'],
            ])->save();
        }
    }

    /**
     * Update the given verified user's profile information.
     *
     * @param  array<string, string>  $input
     */
    protected function updateVerifiedUser(User $user, array $input): void
    {
        $user->forceFill([
            'name' => $input['name'],
            'email' => $input['email'],
            'email_verified_at' => null,
        ])->save();

        $user->sendEmailVerificationNotification();
    }
}
