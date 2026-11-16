<?php

namespace App\Http\Middleware;

use App\Blog\DraftMode;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security headers for every response, including error pages.
 *
 * The Content Security Policy allows scripts only from this site and inline
 * scripts carrying the per-request nonce (`Vite::cspNonce()`), so injected
 * markup cannot run code. Styles allow 'unsafe-inline' because the code
 * highlighter colors tokens with style attributes. Images may come from the
 * Storyblok asset CDN.
 *
 * In draft mode the Visual Editor must be able to frame the page and load its
 * bridge script, so its origins are added there and nowhere else.
 */
class SecurityHeaders
{
    private const STORYBLOK_ASSETS = 'https://*.storyblok.com';

    private const STORYBLOK_BRIDGE = 'https://app.storyblok.com';

    public function __construct(private readonly DraftMode $draft) {}

    public function handle(Request $request, Closure $next): Response
    {
        // Before the view renders, so @vite tags and inline scripts get the nonce.
        Vite::useCspNonce();

        $response = $next($request);

        // Read after the request: DetectDraftMode decides during it.
        $draft = $this->draft->active();

        $headers = $response->headers;
        $headers->set('Content-Security-Policy', $this->policy(Vite::cspNonce(), $draft));
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        if ($draft) {
            // frame-ancestors in the policy decides; the legacy header would block the editor.
            $headers->remove('X-Frame-Options');
        } else {
            $headers->set('X-Frame-Options', 'SAMEORIGIN');
        }

        if ($request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        return $response;
    }

    private function policy(string $nonce, bool $draft): string
    {
        $scripts = ["'self'", "'nonce-{$nonce}'"];
        $styles = ["'self'", "'unsafe-inline'"];
        $connect = ["'self'"];
        $frameAncestors = ["'self'"];

        if ($draft) {
            $scripts[] = self::STORYBLOK_BRIDGE;
            $connect[] = self::STORYBLOK_BRIDGE;
            array_push($frameAncestors, ...$this->editorOrigins());
        }

        // `npm run dev`: assets and hot reload come from the Vite server.
        if (Vite::isRunningHot()) {
            $hot = rtrim((string) file_get_contents(Vite::hotFile()));
            $scripts[] = $hot;
            $styles[] = $hot;
            $connect[] = $hot;
            $connect[] = preg_replace('#^http#', 'ws', $hot);
        }

        $directives = [
            'default-src' => ["'self'"],
            'script-src' => $scripts,
            'style-src' => $styles,
            'img-src' => ["'self'", 'data:', self::STORYBLOK_ASSETS],
            'font-src' => ["'self'"],
            'connect-src' => $connect,
            'object-src' => ["'none'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'"],
            'frame-ancestors' => $frameAncestors,
        ];

        return implode('; ', array_map(
            fn (string $name, array $sources) => $name.' '.implode(' ', array_unique($sources)),
            array_keys($directives),
            $directives,
        ));
    }

    /** @return list<string> */
    private function editorOrigins(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) config('storyblok.editor_origins')))));
    }
}
