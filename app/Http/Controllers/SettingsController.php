<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Movie;
use App\Models\Show;
use App\Models\User;
use App\Services\EpisodeSearch;
use App\Services\Hidden;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Your account. Fortify handles the name, email and password forms; this is the page and the rest.
 */
class SettingsController extends Controller
{
    public function edit(Request $request): View
    {
        return view('settings', [
            'user' => $request->user(),
            'blocked' => $request->user()->blocked()->orderByPivot('created_at', 'desc')->get(),
            'requests' => $request->user()->followRequests()->whereNull('users.suspended_at')->orderByPivot('created_at', 'desc')->get(),
        ]);
    }

    /** Everything stored about you, as a JSON download. The calendar token and password stay out. */
    public function export(Request $request): JsonResponse
    {
        $user = $request->user();
        $person = fn (User $other) => ['name' => $other->name, 'username' => $other->username];
        $entry = fn ($item, string $kind) => [
            'kind' => $kind,
            'title' => $kind === 'show' ? $item->name : $item->title,
            'tvmaze_id' => $item->tvmaze_id ?? null,
            'tmdb_id' => $item->tmdb_id,
            ...collect($item->pivot->only(User::LIBRARY_PIVOT))->map(fn ($value) => $value instanceof \DateTimeInterface ? $value->format(DATE_ATOM) : $value)->all(),
        ];

        $data = [
            'exported_at' => now()->toIso8601String(),
            'account' => $user->only(['name', 'username', 'email', 'bio', 'is_private', 'shares_activity', 'episode_search_url', 'discover_hidden', 'created_at', 'email_verified_at']),
            'invited_by' => $user->invitedBy ? $person($user->invitedBy) : null,
            'library' => $user->libraryShows()->get()->map(fn ($show) => $entry($show, 'show'))
                ->concat($user->libraryMovies()->get()->map(fn ($movie) => $entry($movie, 'movie')))->values(),
            'following' => $user->following()->get()->map($person)->values(),
            'followers' => $user->followers()->get()->map($person)->values(),
            'follow_requests' => $user->followRequests()->get()->map($person)->values(),
            'blocked' => $user->blocked()->get()->map($person)->values(),
            'activity' => Activity::where('user_id', $user->id)->orderBy('created_at')->get()
                ->map(fn (Activity $activity) => ['type' => $activity->type, 'items' => $activity->items(), 'created_at' => $activity->created_at->toIso8601String(), 'updated_at' => $activity->updated_at->toIso8601String()]),
        ];

        return response()->json($data, 200, [
            'Content-Disposition' => 'attachment; filename="hoot-'.$user->username.'-'.now()->format('Y-m-d').'.json"',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /** What to leave out of Discover: keys of hoot.hideable, none ticked means nothing. */
    public function updateHidden(Request $request): RedirectResponse
    {
        $input = $request->validate([
            'hidden' => ['array'],
            'hidden.*' => [Rule::in(array_keys(config('hoot.hideable')))],
        ]);

        $hidden = array_values(array_intersect(array_keys(config('hoot.hideable')), $input['hidden'] ?? []));
        $request->user()->forceFill(['discover_hidden' => $hidden ?: null])->save();

        return back()->with('status', $hidden ? 'Discover now hides '.Hidden::describe($hidden).'.' : 'Discover shows everything again.');
    }

    /** The extras, off unless you set them: for now, your own search link next to episodes that are out. */
    public function updateExtras(Request $request): RedirectResponse
    {
        $input = $request->validateWithBag('extras', [
            'episode_search_url' => ['nullable', 'string', 'max:500', function (string $attribute, mixed $value, \Closure $fail) {
                // With a search filled in, it has to be a plain web address.
                $filled = str_replace(EpisodeSearch::PLACEHOLDER, 'query', trim($value));
                if (! preg_match('#^https?://#i', $filled) || filter_var($filled, FILTER_VALIDATE_URL) === false) {
                    $fail('That doesn’t look like a web address. It should start with https://.');
                } elseif (! str_contains($value, EpisodeSearch::PLACEHOLDER)) {
                    $fail('Put {query} where the search goes, like https://example.org/search?q={query}.');
                }
            }],
        ]);

        $request->user()->forceFill(['episode_search_url' => filled($input['episode_search_url'] ?? null) ? trim($input['episode_search_url']) : null])->save();

        return back()->with('status', filled($input['episode_search_url'] ?? null) ? 'Search link saved.' : 'Search link removed.');
    }

    public function resetCalendar(Request $request): RedirectResponse
    {
        $request->user()->resetCalendarToken();

        return back()->with('status', 'New calendar link made. Subscribe again with it; the old one has stopped working.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('deleteAccount', ['password' => ['required', 'current_password']]);

        $user = $request->user();
        $shows = $user->libraryShows()->pluck('shows.id');
        $movies = $user->libraryMovies()->pluck('movies.id');

        Auth::logout();
        $user->delete();

        // What only this user had in their library leaves the catalog with them.
        Show::whereKey($shows)->doesntHave('users')->delete();
        Movie::whereKey($movies)->doesntHave('users')->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('status', 'Your account is deleted.');
    }
}
