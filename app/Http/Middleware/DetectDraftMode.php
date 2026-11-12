<?php

namespace App\Http\Middleware;

use App\Blog\DraftMode;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Switches the request to draft content when it comes from the Storyblok
 * Visual Editor (or follows a link inside it), and keeps that content out of
 * caches and search engines. Applied to the HTML pages only, never to the
 * feed, the sitemap or the webhook.
 */
class DetectDraftMode
{
    public function __construct(private readonly DraftMode $draft) {}

    public function handle(Request $request, Closure $next): Response
    {
        $fromEditor = $this->draft->requestedByEditor($request);

        if ($fromEditor || $this->draft->hasValidCookie($request)) {
            // On the request, not in config or the view factory: it must not outlive this request.
            $request->attributes->set(DraftMode::ATTRIBUTE, true);
        }

        $response = $next($request);

        // Also true in a preview environment, where every response is a draft.
        if (! $this->draft->active()) {
            return $response;
        }

        if ($fromEditor) {
            $response->headers->setCookie($this->draft->cookie($request));
        }

        $origins = implode(' ', array_filter(array_map('trim', explode(',', (string) config('storyblok.editor_origins')))));

        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        $response->headers->set('Content-Security-Policy', "frame-ancestors 'self' {$origins}");

        return $response;
    }
}
