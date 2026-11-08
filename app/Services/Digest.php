<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\WeeklyDigest;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * The weekly mail (hoot:digest, Monday mornings): for everyone who switched it on, what comes out in the next
 * seven days and who followed them in the last seven. Nothing to say, no mail.
 */
class Digest
{
    public function __construct(private Timeline $timeline) {}

    /** @return Builder<User> who gets it: switched on, email confirmed, not suspended */
    public function recipients(): Builder
    {
        return User::query()->where('weekly_digest', true)->whereNotNull('email_verified_at')
            ->whereNotNull('username')->whereNull('suspended_at');
    }

    /** Sends $user their digest; false if there was nothing to tell. */
    public function send(User $user): bool
    {
        $timezone = config('hoot.timezone');
        $from = CarbonImmutable::now($timezone)->startOfDay();

        $days = $this->timeline->releases($user, $from, $from->addDays(6)->endOfDay(), $timezone)
            ->groupBy(fn (array $release) => $release['at']->toDateString());

        $ids = $user->notifications()->where('type', 'people')->where('data->kind', 'followed')
            ->where('created_at', '>=', now()->subWeek())->get()->pluck('data.user_id');
        $followers = $ids->isEmpty() ? collect() : User::query()->visibleTo($user)->whereKey($ids)->orderBy('name')->get();

        if ($days->isEmpty() && $followers->isEmpty()) {
            return false;
        }

        $user->notify(new WeeklyDigest($from, $days, $followers));

        return true;
    }
}
