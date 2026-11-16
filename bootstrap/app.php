<?php

use App\Http\Middleware\RestrictToAllowedIps;
use App\Http\Middleware\SecurityHeaders;
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
        // Outermost, so every response gets them, including error and maintenance pages.
        $middleware->prepend(SecurityHeaders::class);
        // Before that, so a blocked visitor reaches nothing else (no session, no Storyblok read).
        $middleware->prepend(RestrictToAllowedIps::class);

        // Storyblok cannot send a CSRF token; the webhook is authenticated by its signature instead.
        $middleware->preventRequestForgery(except: ['webhooks/storyblok']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
