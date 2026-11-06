<?php

use App\Http\Middleware\EnsureUserHasUsername;
use App\Timezone;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // The browser writes its timezone into this cookie itself, so it can't be encrypted.
        $middleware->encryptCookies(except: [Timezone::COOKIE]);

        // Accounts without a username (from before profiles) are sent to pick one first.
        $middleware->alias(['username' => EnsureUserHasUsername::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
