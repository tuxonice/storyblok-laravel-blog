<?php

namespace App\Blog;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie as CookieFactory;
use Symfony\Component\HttpFoundation\Cookie;
use Tlab\StoryblokTransfers\Content\Version;

/**
 * Decides whether a request may see unpublished (draft) content.
 *
 * The Storyblok Visual Editor loads the site in an iframe with
 * `_storyblok_tk[space_id|timestamp|token]`, where token is
 * sha1("{space_id}:{preview_token}:{timestamp}"). Only someone holding the
 * preview token can forge it, and it expires after an hour. A valid one also
 * sets a short-lived cookie (encrypted by Laravel) so links followed inside the preview
 * stay in draft mode.
 */
class DraftMode
{
    public const COOKIE = 'storyblok_draft';

    /** Request attribute set by the middleware; state lives on the request, never in global config. */
    public const ATTRIBUTE = 'storyblok.draft';

    public const MAX_AGE = 3600;

    /** Clock skew tolerated on a timestamp from the future. */
    private const SKEW = 60;

    /**
     * True for a request carrying a valid editor token or cookie, and in a preview
     * environment (STORYBLOK_DEFAULT_VERSION=draft), where every request is a draft read.
     */
    public function active(): bool
    {
        return config('storyblok.version') === 'draft' || (bool) request()->attributes->get(self::ATTRIBUTE, false);
    }

    /** The version every read should ask Storyblok for. */
    public function version(): Version
    {
        return $this->active() ? Version::Draft : Version::from(config('storyblok.version'));
    }

    public function requestedByEditor(Request $request): bool
    {
        $tk = $request->query('_storyblok_tk');
        $previewToken = (string) config('storyblok.preview_token');

        if (! is_array($tk) || $previewToken === '') {
            return false;
        }

        $spaceId = (string) ($tk['space_id'] ?? '');
        $timestamp = (string) ($tk['timestamp'] ?? '');
        $token = (string) ($tk['token'] ?? '');

        if ($spaceId !== (string) config('storyblok.space_id') || ! ctype_digit($timestamp) || $token === '') {
            return false;
        }

        $age = time() - (int) $timestamp;

        if ($age > self::MAX_AGE || $age < -self::SKEW) {
            return false;
        }

        return hash_equals(sha1("{$spaceId}:{$previewToken}:{$timestamp}"), $token);
    }

    /**
     * The cookie holds its own expiry. Laravel's EncryptCookies middleware encrypts it
     * on the way out and drops it on the way in if it was altered, so a value that
     * arrives here was written by this app.
     */
    public function hasValidCookie(Request $request): bool
    {
        $value = $request->cookies->get(self::COOKIE);

        return is_string($value) && ctype_digit($value) && (int) $value > time();
    }

    /** A cookie that keeps draft mode on for the next hour. SameSite=None so it works inside the editor's iframe. */
    public function cookie(Request $request): Cookie
    {
        $secure = $request->isSecure();

        return CookieFactory::make(self::COOKIE, (string) (time() + self::MAX_AGE), self::MAX_AGE / 60, '/', null, $secure, true, false, $secure ? 'none' : 'lax');
    }

    public function forgetCookie(): Cookie
    {
        return CookieFactory::forget(self::COOKIE);
    }
}
