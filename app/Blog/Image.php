<?php

namespace App\Blog;

/** Image URLs that come from the CMS. */
final class Image
{
    /** The URL if it is an absolute http(s) URL, otherwise null. */
    public static function url(?string $url): ?string
    {
        $url = trim((string) $url);

        return preg_match('~^https?://~i', $url) ? $url : null;
    }

    /**
     * A Storyblok asset resized by its image service (`/m/{width}x{height}`). Anything else,
     * SVGs and URLs that are already resized are returned untouched.
     */
    public static function resize(?string $url, int $width, int $height): ?string
    {
        $url = self::url($url);

        if ($url === null) {
            return null;
        }

        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');
        $path = $parts['path'] ?? '';

        $onStoryblok = $host === 'storyblok.com' || str_ends_with($host, '.storyblok.com');

        if (! $onStoryblok || str_contains($path, '/m/') || str_ends_with(strtolower($path), '.svg')) {
            return $url;
        }

        return $parts['scheme'].'://'.$host.$path.'/m/'.$width.'x'.$height.(isset($parts['query']) ? '?'.$parts['query'] : '');
    }
}
