<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Notification;

/**
 * In-app news about people, shown on /notifications: someone followed you (perhaps through your invite link),
 * asked to follow you, or accepted your request. Only the other person's id is stored, so the page always shows
 * their current name, and leaves them out once they're blocked, suspended or gone. App\Services\Network sends
 * these and takes them back when the follow or request is undone.
 */
class PeopleNews extends Notification
{
    public const KINDS = ['followed', 'requested', 'accepted'];

    public function __construct(public string $kind, public User $user, public bool $invited = false) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function databaseType(object $notifiable): string
    {
        return 'people';
    }

    /** @return array{kind: string, user_id: int, invited?: bool} */
    public function toArray(object $notifiable): array
    {
        return ['kind' => $this->kind, 'user_id' => $this->user->id] + ($this->invited ? ['invited' => true] : []);
    }
}
