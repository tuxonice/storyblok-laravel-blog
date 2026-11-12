<?php

namespace App\Blog;

use App\Storyblok\Transfers\AuthorTransfer;
use App\Storyblok\Transfers\BlogIndexTransfer;
use App\Storyblok\Transfers\CategoryTransfer;
use App\Storyblok\Transfers\PageTransfer;
use App\Storyblok\Transfers\PostTransfer;
use App\Storyblok\Transfers\SiteSettingsTransfer;
use Carbon\CarbonImmutable;
use Tlab\StoryblokTransfers\Content\ContentOptions;
use Tlab\StoryblokTransfers\Content\StoryblokContent;
use Tlab\StoryblokTransfers\Content\StoryQuery;
use Tlab\StoryblokTransfers\Content\StoryTransfer;
use Tlab\StoryblokTransfers\Transfers\LinkTransfer;

/**
 * Reads the blog from Storyblok and shapes it for the Blade views.
 */
class Blog
{
    private ?array $site = null;

    private ?BlogIndexTransfer $index = null;

    private bool $indexLoaded = false;

    public function __construct(
        private readonly StoryblokContent $storyblok,
        private readonly RichtextRenderer $richtext,
        private readonly DraftMode $draft,
    ) {}

    /** @return array{name: string, ogImage: ?string, nav: array, footer: array} */
    public function site(): array
    {
        return $this->site ??= $this->loadSite();
    }

    /**
     * Site settings for an error page. Storyblok may be the very reason the site is failing,
     * so a failed read falls back to static settings instead of failing the error page too.
     *
     * @return array{name: string, ogImage: ?string, nav: array, footer: array}
     */
    public function siteOrFallback(): array
    {
        try {
            return $this->site();
        } catch (\Throwable $e) {
            report($e);

            return self::fallbackSite();
        }
    }

    /** @return array{name: string, ogImage: ?string, nav: array, footer: array} */
    public static function fallbackSite(): array
    {
        return [
            'name' => (string) config('app.name'),
            'ogImage' => null,
            'nav' => [
                ['label' => 'posts', 'href' => '/'],
                ['label' => 'categories', 'href' => '/categories'],
                ['label' => 'about', 'href' => '/about'],
                ['label' => 'rss', 'href' => '/feed.xml'],
            ],
            'footer' => [
                ['label' => 'rss', 'href' => '/feed.xml'],
                ['label' => 'sitemap', 'href' => '/sitemap.xml'],
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function home(int $page): array
    {
        $index = $this->index();
        $perPage = $this->postsPerPage();

        $list = $this->storyblok->stories()->findBy(new StoryQuery(
            startsWith: 'posts/',
            sortBy: 'content.published_at:desc',
            page: max(1, $page),
            perPage: $perPage,
            options: $this->options(),
        ), PostTransfer::class);

        $posts = array_map(fn (StoryTransfer $s) => $this->post($s), $list->getStories());

        $featured = null;
        if ($index?->getFeaturedPost()) {
            $story = $this->storyblok->stories()->byUuid($index->getFeaturedPost(), PostTransfer::class, $this->options());
            $featured = $story ? $this->post($story) : null;
        }

        return [
            'intro' => (string) $index?->getIntro(),
            'featured' => $featured ?? $posts[0] ?? null,
            'posts' => $posts,
            'categories' => $this->categories(),
            'page' => $list->getPage(),
            'hasNewer' => $list->getPage() > 1,
            'hasOlder' => $list->getPage() * $perPage < $list->getTotal(),
        ];
    }

    /** @return array<string, mixed>|null */
    public function author(string $slug, int $page): ?array
    {
        $story = $this->storyblok->stories()->bySlug('authors/'.$slug, AuthorTransfer::class, $this->options());

        if ($story === null) {
            return null;
        }

        $perPage = $this->postsPerPage();
        $list = $this->storyblok->stories()->findBy(new StoryQuery(
            startsWith: 'posts/',
            sortBy: 'content.published_at:desc',
            // A single reference field: `in`. (The multi-reference categories need `any_in_array`.)
            filterQuery: ['author' => ['in' => $story->getUuid()]],
            page: max(1, $page),
            perPage: $perPage,
            options: $this->options(),
        ), PostTransfer::class);

        $posts = array_map(fn (StoryTransfer $s) => $this->post($s), $list->getStories());

        // A page past the end is a 404; an author with no posts yet is still a valid page.
        if ($posts === [] && $page > 1) {
            return null;
        }

        return [
            'author' => $this->authorView($story->getContent(), $story->getSlug(), $story->getName()),
            'posts' => $posts,
            'total' => $list->getTotal(),
            'page' => $list->getPage(),
            'hasNewer' => $list->getPage() > 1,
            'hasOlder' => $list->getPage() * $perPage < $list->getTotal(),
        ];
    }

    /** @return array<string, mixed>|null */
    public function category(string $slug, int $page): ?array
    {
        $story = $this->storyblok->stories()->bySlug('categories/'.$slug, CategoryTransfer::class, $this->options());

        if ($story === null) {
            return null;
        }

        $perPage = $this->postsPerPage();
        $list = $this->storyblok->stories()->findBy(new StoryQuery(
            startsWith: 'posts/',
            sortBy: 'content.published_at:desc',
            filterQuery: ['categories' => ['any_in_array' => $story->getUuid()]],
            page: max(1, $page),
            perPage: $perPage,
            options: $this->options(),
        ), PostTransfer::class);

        $posts = array_map(fn (StoryTransfer $s) => $this->post($s), $list->getStories());

        // A page past the end is a 404, but an empty first page is a valid empty category.
        if ($posts === [] && $page > 1) {
            return null;
        }

        return [
            'category' => [
                'name' => (string) ($story->getContent()->getName() ?: $story->getName()),
                'slug' => $story->getSlug(),
                'description' => (string) $story->getContent()->getDescription(),
            ],
            'posts' => $posts,
            'total' => $list->getTotal(),
            'page' => $list->getPage(),
            'hasNewer' => $list->getPage() > 1,
            'hasOlder' => $list->getPage() * $perPage < $list->getTotal(),
        ];
    }

    /** @return array{title: string, body: string}|null */
    public function about(): ?array
    {
        $story = $this->storyblok->stories()->bySlug('about', PageTransfer::class, $this->options());

        if ($story === null) {
            return null;
        }

        /** @var PageTransfer $page */
        $page = $story->getContent();

        $html = $this->richtext->render($page->getBody())->html;

        return [
            'title' => (string) ($page->getTitle() ?: $story->getName()),
            'body' => $html,
            'summary' => SeoMeta::describe($html),
        ];
    }

    /**
     * The newest posts with their full rendered bodies, for the RSS feed.
     *
     * @return array<int, array<string, mixed>>
     */
    public function latest(int $limit = 20): array
    {
        $list = $this->storyblok->stories()->findBy(new StoryQuery(
            startsWith: 'posts/',
            sortBy: 'content.published_at:desc',
            perPage: min(100, $limit),
            options: $this->options(),
        ), PostTransfer::class);

        return array_map(fn (StoryTransfer $s) => $this->post($s), $list->getStories());
    }

    /**
     * Every public URL for the sitemap, taken from the links tree (one request),
     * plus one body-less listing for the posts' last-modified dates, since the
     * links endpoint carries no dates.
     *
     * @return array<int, array{path: string, lastmod: ?string}>
     */
    public function sitemap(): array
    {
        $lastmod = [];
        $posts = $this->storyblok->stories()->findBy(new StoryQuery(
            startsWith: 'posts/',
            perPage: 100,
            excludingFields: ['body'],
            options: $this->options(),
        ), PostTransfer::class);

        foreach ($posts->getStories() as $story) {
            $lastmod['/'.$story->getFullSlug()] = $story->getPublishedAt();
        }

        $entries = [['path' => '/', 'lastmod' => null], ['path' => '/categories', 'lastmod' => null]];

        foreach ($this->storyblok->links()->all(null, $this->options()) as $link) {
            if ($link->isFolder() || ! $link->isPublished()) {
                continue;
            }

            $path = '/'.ltrim($link->getSlug(), '/');

            // Only slugs the app has a route for: posts, categories, authors and the about page.
            if ($path === '/about' || str_starts_with($path, '/posts/') || str_starts_with($path, '/categories/') || str_starts_with($path, '/authors/')) {
                $entries[] = ['path' => $path, 'lastmod' => $lastmod[$path] ?? null];
            }
        }

        return $entries;
    }

    /** @return array<string, mixed>|null */
    public function findPost(string $slug): ?array
    {
        $story = $this->storyblok->stories()->bySlug(
            'posts/'.$slug,
            PostTransfer::class,
            $this->options()->withResolveRelations(['post.author', 'post.categories']),
        );

        return $story ? $this->post($story) : null;
    }

    /** @return array<int, array<string, mixed>> */
    public function related(string $exceptSlug, int $limit = 3): array
    {
        $list = $this->storyblok->stories()->findBy(new StoryQuery(
            startsWith: 'posts/',
            sortBy: 'content.published_at:desc',
            perPage: $limit + 1,
            options: $this->options(),
        ), PostTransfer::class);

        $others = array_filter($list->getStories(), fn (StoryTransfer $s) => $s->getSlug() !== $exceptSlug);

        return array_map(fn (StoryTransfer $s) => $this->post($s), array_slice(array_values($others), 0, $limit));
    }

    /** @return array<int, array{name: string, slug: string}> */
    public function categories(): array
    {
        $list = $this->storyblok->stories()->findBy(new StoryQuery(
            startsWith: 'categories/',
            sortBy: 'content.name:asc',
            perPage: 100,
            options: $this->options(),
        ), CategoryTransfer::class);

        return array_map(fn (StoryTransfer $s) => [
            'name' => (string) ($s->getContent()->getName() ?: $s->getName()),
            'slug' => $s->getSlug(),
            'description' => (string) $s->getContent()->getDescription(),
        ], $list->getStories());
    }

    private function index(): ?BlogIndexTransfer
    {
        if (! $this->indexLoaded) {
            $this->index = $this->storyblok->stories()->bySlug('home', BlogIndexTransfer::class, $this->options())?->getContent();
            $this->indexLoaded = true;
        }

        return $this->index;
    }

    private function postsPerPage(): int
    {
        return max(1, (int) ($this->index()?->getPostsPerPage() ?: 10));
    }

    /**
     * Per-call options replace the configured defaults as a whole, so the
     * configured version has to be passed again (see FRICTION.md).
     */
    private function options(): ContentOptions
    {
        return new ContentOptions($this->draft->version());
    }

    /** @return array<string, mixed> */
    private function post(StoryTransfer $story): array
    {
        /** @var PostTransfer $post */
        $post = $story->getContent();
        $body = $this->richtext->render($post->getBody());

        $author = $story->getRelation($post->getAuthor());
        $authorContent = $author instanceof StoryTransfer && $author->getContent() instanceof AuthorTransfer ? $author->getContent() : null;

        $categories = [];
        foreach ($post->getCategories() as $uuid) {
            $category = $story->getRelation($uuid);
            if ($category instanceof StoryTransfer) {
                $categories[] = ['name' => (string) $category->getContent()->getName(), 'slug' => $category->getSlug()];
            }
        }

        return [
            'slug' => $story->getSlug(),
            'title' => (string) $post->getTitle(),
            'excerpt' => (string) $post->getExcerpt(),
            'date' => CarbonImmutable::parse($post->getPublishedAt() ?? $story->getFirstPublishedAt() ?? 'now'),
            'minutes' => $body->readingMinutes(),
            'tags' => $post->getTags(),
            'categories' => $categories,
            'cover' => $post->getCoverImage()?->getFilename() ?: null,
            'author' => $this->authorView($authorContent, $author instanceof StoryTransfer ? $author->getSlug() : null, $author instanceof StoryTransfer ? $author->getName() : null),
            'body' => $body->html,
            'headings' => $body->headings,
            'seoTitle' => (string) $post->getSeoTitle(),
            'seoDescription' => (string) $post->getSeoDescription(),
            // The dedicated social image, falling back to the cover.
            'ogImage' => $post->getOgImage()?->getFilename() ?: $post->getCoverImage()?->getFilename() ?: null,
            'ogImageAlt' => (string) ($post->getOgImage()?->getAlt() ?: $post->getCoverImage()?->getAlt()),
        ];
    }

    private function loadSite(): array
    {
        $settings = $this->storyblok->stories()->bySlug('settings', SiteSettingsTransfer::class, $this->options())?->getContent();

        return [
            'name' => (string) ($settings?->getSiteName() ?: config('app.name')),
            'ogImage' => $settings?->getDefaultOgImage()?->getFilename() ?: null,
            'nav' => $this->links($settings?->getNav() ?? []),
            'footer' => $this->links($settings?->getFooterLinks() ?? []),
        ];
    }

    /** @return array<int, array{label: string, href: string}> */
    private function links(array $navLinks): array
    {
        $links = [];

        foreach ($navLinks as $link) {
            $href = $this->href($link->getLink());
            if ($href !== null) {
                $links[] = ['label' => (string) $link->getLabel(), 'href' => $href];
            }
        }

        return $links;
    }

    private function href(?LinkTransfer $link): ?string
    {
        return match ($link?->getLinktype()) {
            'story' => '/'.ltrim((string) $link->getCachedUrl(), '/'),
            'url' => SafeUrl::href($link->getUrl() ?: $link->getCachedUrl(), 'url'),
            'email' => SafeUrl::href($link->getUrl(), 'email'),
            default => null,
        };
    }

    /**
     * @return array{slug: ?string, name: string, initials: string, role: string, bio: string, avatar: ?string, website: ?array{url: string, label: string}, github: ?array{url: string, label: string}}
     */
    private function authorView(?AuthorTransfer $author, ?string $slug, ?string $storyName = null): array
    {
        $name = (string) ($author?->getName() ?: $storyName);

        return [
            'slug' => $slug,
            'name' => $name,
            'initials' => $this->initials($name),
            'role' => (string) $author?->getRole(),
            'bio' => (string) $author?->getBio(),
            'avatar' => Image::url($author?->getAvatar()?->getFilename()),
            'website' => $this->website($author),
            'github' => $this->github($author),
        ];
    }

    /** @return array{url: string, label: string}|null */
    private function website(?AuthorTransfer $author): ?array
    {
        $url = $this->href($author?->getWebsite());

        if ($url === null || ! str_starts_with($url, 'http')) {
            return null;
        }

        return ['url' => $url, 'label' => preg_replace('/^www\./', '', (string) parse_url($url, PHP_URL_HOST)) ?: $url];
    }

    /** The GitHub field is free text: a profile URL or just a handle. */
    private function github(?AuthorTransfer $author): ?array
    {
        $value = trim((string) $author?->getGithub());

        if (preg_match('~^https://github\.com/([A-Za-z0-9][A-Za-z0-9-]{0,38})/?$~i', $value, $m) || preg_match('~^@?([A-Za-z0-9][A-Za-z0-9-]{0,38})$~', $value, $m)) {
            return ['url' => 'https://github.com/'.$m[1], 'label' => 'github.com/'.$m[1]];
        }

        return null;
    }

    private function initials(string $name): string
    {
        $initials = array_map(fn (string $w) => mb_strtoupper(mb_substr($w, 0, 1)), preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY));

        return implode('', array_slice($initials, 0, 2));
    }
}
