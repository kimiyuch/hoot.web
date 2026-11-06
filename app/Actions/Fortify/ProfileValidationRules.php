<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Validation\Rule;

trait ProfileValidationRules
{
    /**
     * A username: 3–20 lowercase letters, digits or underscores, not taken and not reserved.
     *
     * @return array<int, mixed>
     */
    protected function usernameRules(?User $ignore = null): array
    {
        return [
            'required', 'string', 'min:3', 'max:20', 'regex:/^[a-z0-9_]+$/',
            Rule::notIn(config('hoot.reserved_usernames')),
            Rule::unique('users', 'username')->ignore($ignore?->id),
        ];
    }

    /** Usernames are stored lowercase, without a leading @. */
    protected static function cleanUsername(mixed $value): string
    {
        return mb_strtolower(ltrim(trim((string) $value), '@'));
    }

    /** @return array<string, string> */
    protected function usernameMessages(): array
    {
        return [
            'username.regex' => 'Use only lowercase letters, numbers and underscores.',
            'username.not_in' => 'That username is reserved. Pick another one.',
            'username.unique' => 'That username is taken. Pick another one.',
        ];
    }
}
