<?php

namespace App\Blog;

/**
 * Links that come from the CMS are editor input, so only a few shapes may reach an href:
 * relative paths, anchors and http(s), mailto and tel URLs. `javascript:`, `data:` and
 * protocol-relative `//host` URLs are dropped.
 */
final class SafeUrl
{
    public static function href(?string $href, string $linktype = ''): ?string
    {
        $href = trim((string) $href);

        if ($href === '') {
            return null;
        }

        if ($linktype === 'email' && ! str_starts_with($href, 'mailto:')) {
            $href = 'mailto:'.$href;
        }

        if (preg_match('~^(?:/(?!/)|#)~', $href) || preg_match('~^(?:https?|mailto|tel):~i', $href)) {
            return $href;
        }

        return null;
    }
}
