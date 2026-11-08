<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Services\Invites;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        $input['username'] = self::cleanUsername($input['username'] ?? '');

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'username' => $this->usernameRules(),
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
            'visibility' => ['nullable', 'in:public,private'],
        ], $this->usernameMessages())->validate();

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
            'username' => $input['username'],
            'is_private' => ($input['visibility'] ?? 'public') === 'private',
        ]);

        // Came through an invite link: noted now, followed once the email is confirmed.
        app(Invites::class)->attach($user);

        return $user;
    }
}
