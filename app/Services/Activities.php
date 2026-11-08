<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Movie;
use App\Models\Show;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The thread: what the people you follow do. Only Library and Network write to it, so controllers and
 * components never create entries themselves. To keep it calm, entries collapse (following five shows in
 * a few minutes is one entry), changing your mind within a few minutes takes the entry back, unfollowing is
 * never news, and nothing from before the thread existed is backfilled.
 */
class Activities
{
    /** Minutes within which entries collapse, and an undo takes an entry back. */
    public const WINDOW = 10;

    /**
     * Notes that $user did $type to $subject. Collapsing types join the user's latest entry of that type if
     * it's recent; the rest replace a recent entry about the same subject (so re-rating doesn't stack up).
     */
    public function record(User $user, string $type, Show|Movie|User $subject, array $extra = []): void
    {
        // Not sharing: nothing is noted, so turning it back on doesn't reveal what happened meanwhile.
        // (A fresh model hasn't loaded the column's default, so only an explicit false counts.)
        if ($user->shares_activity === false) {
            return;
        }

        $item = $this->snapshot($subject) + $extra;

        if (in_array($type, Activity::COLLAPSING, true)
            && $recent = $this->recent($user, [$type])->latest('updated_at')->latest('id')->first()) {
            $items = collect($recent->items())->reject(fn (array $other) => $this->same($other, $item))->push($item)->values()->all();
            $recent->forceFill(['data' => ['items' => $items], 'updated_at' => now()])->save();

            return;
        }

        $this->retract($user, [$type], $subject);
        $user->hasMany(Activity::class)->create(['type' => $type, 'data' => ['items' => [$item]]]);
    }

    /** Takes $subject out of $user's recent entries of these types (an undo), dropping entries left empty. */
    public function retract(User $user, array $types, Show|Movie|User $subject): void
    {
        $this->remove($this->recent($user, $types)->get(), $this->snapshot($subject));
    }

    /**
     * A rating. Right after finishing it, the rating goes on the "finished" entry instead of a new one;
     * taking the rating away takes back a recent "rated" entry.
     */
    public function rated(User $user, Show|Movie $item, ?int $rating): void
    {
        $finished = $this->recentAbout($user, 'finished', $item);

        if ($finished) {
            $this->setRating($finished, $rating);
            $this->retract($user, ['rated'], $item);

            return;
        }

        $rating === null
            ? $this->retract($user, ['rated'], $item)
            : $this->record($user, 'rated', $item, ['rating' => $rating]);
    }

    /** Finished it. A rating given just before joins the entry. */
    public function finished(User $user, Show|Movie $item, ?int $rating): void
    {
        $rated = $this->recentAbout($user, 'rated', $item);
        $this->retract($user, ['rated'], $item);
        $this->record($user, 'finished', $item, $rated && $rating ? ['rating' => $rating] : []);
    }

    /** Takes every "followed" mention between the two out of both threads, whenever it was (blocking). */
    public function forgetBetween(User $one, User $other): void
    {
        $this->remove(Activity::where('user_id', $one->id)->where('type', 'followed_user')->get(), $this->snapshot($other));
        $this->remove(Activity::where('user_id', $other->id)->where('type', 'followed_user')->get(), $this->snapshot($one));
    }

    /**
     * What the people $viewer follows did, newest first: entries from accepted follows only, nobody across a
     * block, suspended or not sharing their activity, and "followed someone" only where $viewer may see that someone (not blocked, not a private
     * account they don't follow). Those people are shown with their current name.
     *
     * @return array{entries: Collection<int, Activity>, more: bool}
     */
    public function thread(User $viewer, int $limit = 10): array
    {
        return $this->page(Activity::query()
            ->whereIn('user_id', DB::table('follows')->select('followed_id')->where('follower_id', $viewer->id)->whereNotNull('accepted_at'))
            ->whereHas('user', fn (Builder $users) => $users->visibleTo($viewer)->where('shares_activity', true)), $viewer, $limit);
    }

    /**
     * What one person did, for "Lately" on their profile: only where $viewer may see the profile, and only while
     * they share their activity (your own too, so the profile shows what others see). Same rules as the thread
     * for "followed someone".
     *
     * @return array{entries: Collection<int, Activity>, more: bool}
     */
    public function of(User $owner, User $viewer, int $limit = 10): array
    {
        if (! $owner->shares_activity || ! $owner->profileVisibleTo($viewer)) {
            return ['entries' => collect(), 'more' => false];
        }

        return $this->page(Activity::where('user_id', $owner->id), $viewer, $limit);
    }

    /**
     * The newest $limit entries of $query, with "followed someone" cut down to people $viewer may see.
     *
     * @param  Builder<Activity>  $query
     * @return array{entries: Collection<int, Activity>, more: bool}
     */
    private function page(Builder $query, User $viewer, int $limit): array
    {
        $rows = $query
            ->with('user')
            ->latest('updated_at')->latest('id')
            ->limit($limit + 1)
            ->get();

        $more = $rows->count() > $limit;
        $rows = $rows->take($limit);

        $ids = $rows->where('type', 'followed_user')->flatMap(fn (Activity $activity) => array_column($activity->items(), 'id'))->unique();
        $people = $ids->isEmpty() ? collect() : User::query()->whereKey($ids)->visibleTo($viewer)->get()
            ->filter(fn (User $person) => $person->profileVisibleTo($viewer))
            ->keyBy('id');

        $entries = $rows->map(function (Activity $activity) use ($people) {
            if ($activity->type !== 'followed_user') {
                return $activity;
            }

            $items = collect($activity->items())
                ->filter(fn (array $item) => $people->has($item['id']))
                ->map(fn (array $item) => ['title' => $people[$item['id']]->name, 'username' => $people[$item['id']]->username, 'person' => $people[$item['id']]] + $item)
                ->values()->all();

            return $items ? $activity->setAttribute('data', ['items' => $items]) : null;
        })->filter()->values();

        return ['entries' => $entries, 'more' => $more];
    }

    /** @return Builder<Activity> */
    private function recent(User $user, array $types): Builder
    {
        return Activity::where('user_id', $user->id)->whereIn('type', $types)->where('updated_at', '>=', now()->subMinutes(self::WINDOW));
    }

    private function recentAbout(User $user, string $type, Show|Movie $item): ?Activity
    {
        $snapshot = $this->snapshot($item);

        return $this->recent($user, [$type])->latest('updated_at')->get()
            ->first(fn (Activity $activity) => collect($activity->items())->contains(fn (array $other) => $this->same($other, $snapshot)));
    }

    private function setRating(Activity $activity, ?int $rating): void
    {
        $items = $activity->items();
        $items[0] = array_filter(['rating' => $rating] + $items[0], fn ($value) => $value !== null);
        $activity->forceFill(['data' => ['items' => $items], 'updated_at' => now()])->save();
    }

    /** @param  Collection<int, Activity>  $activities */
    private function remove(Collection $activities, array $snapshot): void
    {
        foreach ($activities as $activity) {
            $items = collect($activity->items())->reject(fn (array $other) => $this->same($other, $snapshot))->values()->all();

            if (count($items) === count($activity->items())) {
                continue;
            }

            // Removing isn't news: the entry keeps its place in the thread.
            if (! $items) {
                $activity->delete();

                continue;
            }

            $activity->timestamps = false;
            $activity->forceFill(['data' => ['items' => $items]])->save();
        }
    }

    private function same(array $one, array $other): bool
    {
        return $one['kind'] === $other['kind'] && $one['id'] === $other['id'];
    }

    /** What the entry needs to show it even after the show or movie is gone from the catalog. */
    private function snapshot(Show|Movie|User $subject): array
    {
        return match (true) {
            $subject instanceof Show => ['kind' => 'show', 'id' => $subject->tvmaze_id, 'title' => $subject->name, 'poster' => $subject->image_url, 'url' => $subject->url],
            $subject instanceof Movie => ['kind' => 'movie', 'id' => $subject->tmdb_id, 'title' => $subject->title, 'poster' => $subject->posterUrl(), 'url' => $subject->url()],
            default => ['kind' => 'user', 'id' => $subject->id, 'title' => $subject->name],
        };
    }
}
