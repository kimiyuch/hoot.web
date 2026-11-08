<?php

namespace App\Notifications;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * The Monday mail (hoot:digest, opt-in in Settings): what comes out this week for what you follow, day by day,
 * and who followed you last week. Times are in hoot.timezone, since the browser's timezone isn't stored.
 */
class WeeklyDigest extends Notification
{
    /**
     * @param  Collection<string, Collection>  $days  Y-m-d => releases (Timeline::releases)
     * @param  Collection<int, User>  $followers  who followed you in the last week
     */
    public function __construct(public CarbonImmutable $from, public Collection $days, public Collection $followers) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $count = $this->days->flatten(1)->count();

        return (new MailMessage)
            ->subject($count ? 'Your week on Hoot: '.trans_choice(':count release|:count releases', $count) : 'Your week on Hoot')
            ->markdown('mail.weekly-digest', [
                'user' => $notifiable,
                'from' => $this->from,
                'days' => $this->days,
                'followers' => $this->followers,
            ]);
    }

    /** "Severance S02E05 · Apple TV", "The Bear, season 4 (10 episodes)", "Dune: Part Three in cinemas". */
    public static function describe(array $release): string
    {
        if ($release['kind'] !== 'episodes') {
            return $release['movie']->title.($release['kind'] === 'cinema' ? ' in cinemas' : ' to stream');
        }

        $show = $release['show'];
        $episodes = $release['episodes'];
        $what = $episodes->count() > 1
            ? $show->name.', season '.$episodes->first()->season.' ('.$episodes->count().' episodes)'
            : $show->name.' '.$episodes->first()->code();

        return $what.($show->channel ? ' · '.$show->channel : '');
    }
}
