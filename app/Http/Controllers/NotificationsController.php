<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

/**
 * /notifications: who followed you, asked to, or accepted your request, newest first. Opening the page marks
 * everything read; what was new still shows as new this once.
 */
class NotificationsController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $notifications = $user->notifications()->where('type', 'people')->latest()->limit(50)->get();

        // Only people you may still see, with their current name.
        $people = User::query()->visibleTo($user)
            ->whereKey($notifications->pluck('data.user_id')->unique())
            ->get()->keyBy('id');
        $pending = $user->followRequests()->pluck('users.id')->flip();

        $user->unreadNotifications()->update(['read_at' => now()]);

        return view('notifications', [
            'notifications' => $notifications
                ->filter(fn (DatabaseNotification $notification) => $people->has($notification->data['user_id']))
                ->map(fn (DatabaseNotification $notification) => [
                    'id' => $notification->id,
                    'kind' => $notification->data['kind'],
                    'invited' => $notification->data['invited'] ?? false,
                    'person' => $people[$notification->data['user_id']],
                    'new' => $notification->read_at === null,
                    'at' => $notification->created_at,
                    'pending' => $pending->has($notification->data['user_id']),
                ])->values(),
        ]);
    }
}
