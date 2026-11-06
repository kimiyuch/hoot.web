<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Accounts from before profiles have no username. They pick one (and public or private) before anything else.
 */
class EnsureUserHasUsername
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->username === null) {
            return $request->expectsJson()
                ? abort(403, 'Pick a username first.')
                : redirect()->guest(route('onboarding'));
        }

        return $next($request);
    }
}
