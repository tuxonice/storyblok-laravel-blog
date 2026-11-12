<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * TEMPORARY hard-coded sample content for step 2 (static layout).
 * Replaced by Storyblok data in step 4. All text here is placeholder.
 */
class SampleBlog
{
    public static function site(): array
    {
        return [
            'name' => 'your-blog',
            'nav' => [
                ['label' => 'posts', 'href' => '/'],
                ['label' => 'categories', 'href' => '/categories/laravel'],
                ['label' => 'about', 'href' => '/about'],
                ['label' => 'rss', 'href' => '/feed.xml'],
            ],
            'about' => 'Notes on building things with Laravel, a headless CMS and a few honest mistakes along the way.',
        ];
    }

    public static function categories(): array
    {
        return [
            ['name' => 'Laravel', 'slug' => 'laravel'],
            ['name' => 'Storyblok', 'slug' => 'storyblok'],
            ['name' => 'PHP', 'slug' => 'php'],
            ['name' => 'Tooling', 'slug' => 'tooling'],
        ];
    }

    public static function author(): array
    {
        return [
            'name' => 'Sample Author',
            'initials' => 'SA',
            'bio' => 'Backend developer who writes about PHP, content modelling and the small details that make a codebase pleasant.',
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function posts(): array
    {
        $author = self::author();
        $posts = [
            ['slug' => 'typed-content-from-storyblok', 'title' => 'Typed content from a headless CMS', 'excerpt' => 'Why generating one PHP class per component beats passing arrays around, and what it costs you.', 'date' => '2026-11-09', 'minutes' => 7, 'tags' => ['php', 'storyblok']],
            ['slug' => 'caching-published-reads', 'title' => 'Caching published reads without surprises', 'excerpt' => 'Fresh windows, stale fallbacks and when a webhook should purge what.', 'date' => '2026-11-02', 'minutes' => 6, 'tags' => ['laravel', 'cache']],
            ['slug' => 'richtext-with-embedded-blocks', 'title' => 'Rendering richtext with embedded blocks', 'excerpt' => 'Walking the richtext tree, rendering nested components and deriving heading anchors.', 'date' => '2026-10-24', 'minutes' => 9, 'tags' => ['blade', 'storyblok']],
            ['slug' => 'blade-components-for-blocks', 'title' => 'One Blade component per block', 'excerpt' => 'Mapping CMS block names to anonymous components and keeping the mapping honest.', 'date' => '2026-10-12', 'minutes' => 5, 'tags' => ['blade']],
            ['slug' => 'sitemaps-from-the-links-endpoint', 'title' => 'Sitemaps from the links endpoint', 'excerpt' => 'A sitemap that costs one request instead of one per story.', 'date' => '2026-10-03', 'minutes' => 4, 'tags' => ['seo', 'laravel']],
        ];

        return array_map(fn (array $p) => [
            ...$p,
            'date' => CarbonImmutable::parse($p['date']),
            'author' => $author,
            'categories' => array_slice(self::categories(), 0, 2),
        ], $posts);
    }

    public static function post(string $slug): ?array
    {
        foreach (self::posts() as $post) {
            if ($post['slug'] === $slug) {
                return $post + ['body' => self::body()];
            }
        }

        return null;
    }

    /** Sample body in a simplified block shape (the real one is richtext JSON). */
    public static function body(): array
    {
        return [
            ['type' => 'paragraph', 'text' => 'Most headless CMS integrations start the same way: a decoded JSON array, a few null checks and a template that quietly knows too much about the shape of the data. It works until the content model changes.'],
            ['type' => 'heading', 'text' => 'Start with the content model', 'id' => 'start-with-the-content-model'],
            ['type' => 'paragraph', 'text' => 'Components in the CMS are the contract. If each one becomes a class, the contract is visible to your editor, to static analysis and to the next person reading the template.'],
            ['type' => 'code', 'filename' => 'resources/views/posts/show.blade.php', 'html' => '<span class="tk-var">@foreach</span> (<span class="tk-var">$post</span>->getContent()->getTags() <span class="tk-var">as</span> <span class="tk-var">$tag</span>)' . "\n" . '    <span class="tk-class">x-tag</span> :name="<span class="tk-var">$tag</span>" />' . "\n" . '<span class="tk-var">@endforeach</span>'],
            ['type' => 'callout', 'label' => '// note', 'text' => 'Everything except arrays is nullable, so templates need the nullsafe operator or a fallback.'],
            ['type' => 'heading', 'text' => 'Resolve relations once', 'id' => 'resolve-relations-once'],
            ['type' => 'paragraph', 'text' => 'Relations arrive next to the content, not inside it. Look them up once per request and pass the result down, instead of asking in every partial.'],
            ['type' => 'quote', 'text' => 'The template should read like a description of the page, not a negotiation with the API.'],
            ['type' => 'figure', 'caption' => 'Placeholder figure caption'],
            ['type' => 'heading', 'text' => 'Keep the glue small', 'id' => 'keep-the-glue-small'],
            ['type' => 'paragraph', 'text' => 'Every workaround you write is a suggestion for the package. Write it down while it is still annoying.'],
        ];
    }
}
