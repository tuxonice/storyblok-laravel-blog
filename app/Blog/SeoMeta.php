<?php

namespace App\Blog;

/**
 * What a page tells search engines and social networks about itself: the <title>,
 * description, canonical URL, Open Graph / Twitter card and JSON-LD. Built by the
 * controllers, rendered once by resources/views/partials/seo.blade.php.
 */
final readonly class SeoMeta
{
    /** Search engines show about 155-160 characters of a description. */
    public const DESCRIPTION_LIMIT = 160;

    private const IMAGE_WIDTH = 1200;

    private const IMAGE_HEIGHT = 630;

    /**
     * @param  string  $title  The full <title>, site name included.
     * @param  array<int, string>  $tags
     * @param  array<string, mixed>|null  $jsonLd
     */
    public function __construct(
        public string $title,
        public string $description,
        public string $canonical,
        public string $siteName,
        public string $type = 'website',
        public ?string $image = null,
        public ?string $imageAlt = null,
        public ?string $publishedTime = null,
        public ?string $author = null,
        public array $tags = [],
        public bool $noindex = false,
        public ?array $jsonLd = null,
    ) {}

    /** "Page title · Site name", or just the site name when the page has no title of its own. */
    public static function title(?string $pageTitle, string $siteName): string
    {
        $pageTitle = trim((string) $pageTitle);

        return $pageTitle === '' ? $siteName : "{$pageTitle} · {$siteName}";
    }

    /**
     * Plain text for a description: tags removed, whitespace collapsed, cut at a word
     * boundary with an ellipsis when longer than the limit.
     */
    public static function describe(?string $text, int $limit = self::DESCRIPTION_LIMIT): string
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $text), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        $cut = mb_substr($text, 0, $limit - 1);
        $lastSpace = mb_strrpos($cut, ' ');

        // Cut at a word boundary unless that would throw away most of the text.
        if ($lastSpace !== false && $lastSpace > $limit * 0.6) {
            $cut = mb_substr($cut, 0, $lastSpace);
        }

        return rtrim($cut, " \t,.;:-").'…';
    }

    /**
     * An absolute image URL sized for social cards. Storyblok assets are resized by its
     * image service (`/m/1200x630`); anything else, SVGs and already-resized URLs are
     * left alone. Returns null for anything that is not an absolute http(s) URL.
     */
    public static function image(?string $url): ?string
    {
        $url = trim((string) $url);

        if (! preg_match('~^https?://~i', $url)) {
            return null;
        }

        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');
        $path = $parts['path'] ?? '';

        $onStoryblok = $host === 'storyblok.com' || str_ends_with($host, '.storyblok.com');

        if (! $onStoryblok || str_contains($path, '/m/') || str_ends_with(strtolower($path), '.svg')) {
            return $url;
        }

        return $parts['scheme'].'://'.$host.$path.'/m/'.self::IMAGE_WIDTH.'x'.self::IMAGE_HEIGHT.(isset($parts['query']) ? '?'.$parts['query'] : '');
    }

    /**
     * @param  array<string, mixed>  $post  As shaped by Blog::post().
     */
    public static function forPost(array $post, string $canonical, string $siteName, ?string $fallbackImage): self
    {
        // The SEO title field is the complete <title>: an editor who fills it in expects it verbatim.
        $seoTitle = trim((string) ($post['seoTitle'] ?? ''));
        $description = self::describe($post['seoDescription'] ?: $post['excerpt']);
        $image = self::image($post['ogImage'] ?? null) ?? self::image($fallbackImage);
        $published = $post['date']->toAtomString();

        return new self(
            title: $seoTitle !== '' ? $seoTitle : self::title($post['title'], $siteName),
            description: $description,
            canonical: $canonical,
            siteName: $siteName,
            type: 'article',
            image: $image,
            imageAlt: ($post['ogImageAlt'] ?? '') !== '' ? $post['ogImageAlt'] : $post['title'],
            publishedTime: $published,
            author: $post['author']['name'] !== '' ? $post['author']['name'] : null,
            tags: $post['tags'],
            jsonLd: array_filter([
                '@context' => 'https://schema.org',
                '@type' => 'BlogPosting',
                'headline' => $post['title'],
                'description' => $description,
                'datePublished' => $published,
                'image' => $image,
                'keywords' => $post['tags'] === [] ? null : implode(', ', $post['tags']),
                'author' => $post['author']['name'] !== '' ? ['@type' => 'Person', 'name' => $post['author']['name']] : null,
                'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $canonical],
            ], fn ($v) => $v !== null),
        );
    }

    /** A plain page: title, description and the site's default image. */
    public static function forPage(?string $pageTitle, ?string $description, string $canonical, string $siteName, ?string $image): self
    {
        return new self(
            title: self::title($pageTitle, $siteName),
            description: self::describe($description),
            canonical: $canonical,
            siteName: $siteName,
            image: self::image($image),
            imageAlt: $siteName,
        );
    }

    /** An error page: a title only, never indexed. */
    public static function forError(string $title, string $siteName, string $url): self
    {
        return new self(
            title: self::title($title, $siteName),
            description: '',
            canonical: $url,
            siteName: $siteName,
            noindex: true,
        );
    }

    /** Used by any view that does not supply its own. */
    public static function fallback(string $siteName, string $canonical, ?string $image): self
    {
        return self::forPage(null, null, $canonical, $siteName, $image);
    }

    public function twitterCard(): string
    {
        return $this->image ? 'summary_large_image' : 'summary';
    }

    /** JSON-LD for a script tag: `<`, `>` and `&` are escaped, so content can never close the tag. */
    public function jsonLdScript(): ?string
    {
        return $this->jsonLd === null
            ? null
            : json_encode($this->jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }
}
