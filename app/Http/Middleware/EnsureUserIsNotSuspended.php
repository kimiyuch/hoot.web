<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * A suspended account (hoot:suspend) is signed out on its next request, even with "Keep me logged in".
 * Logging in again is refused in FortifyServiceProvider.
 */
class EnsureUserIsNotSuspended
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isSuspended()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $request->expectsJson()
                ? abort(403, 'This account is suspended.')
                : redirect()->route('login')->withErrors(['email' => self::message()]);
        }

        return $next($request);
    }

    public static function message(): string
    {
        $contact = config('hoot.report_email');

        return 'This account is suspended.'.($contact ? " If you think that’s a mistake, write to {$contact}." : '');
    }
}
