<?php

namespace App\Blog;

use App\Storyblok\Transfers\AuthorTransfer;
use App\Storyblok\Transfers\BlogIndexTransfer;
use App\Storyblok\Transfers\CategoryTransfer;
use App\Storyblok\Transfers\PostTransfer;
use App\Storyblok\Transfers\SiteSettingsTransfer;
use Carbon\CarbonImmutable;
use Tlab\StoryblokTransfers\Content\ContentOptions;
use Tlab\StoryblokTransfers\Content\StoryblokContent;
use Tlab\StoryblokTransfers\Content\StoryQuery;
use Tlab\StoryblokTransfers\Content\StoryTransfer;
use Tlab\StoryblokTransfers\Content\Version;
use Tlab\StoryblokTransfers\Transfers\LinkTransfer;

/**
 * Reads the blog from Storyblok and shapes it for the Blade views.
 */
class Blog
{
    private ?array $site = null;

    public function __construct(
        private readonly StoryblokContent $storyblok,
        private readonly RichtextRenderer $richtext,
    ) {}

    /** @return array{name: string, nav: array, footer: array} */
    public function site(): array
    {
        return $this->site ??= $this->loadSite();
    }

    /** @return array<string, mixed> */
    public function home(int $page): array
    {
        $index = $this->storyblok->stories()->bySlug('home', BlogIndexTransfer::class, $this->options())?->getContent();
        $perPage = max(1, (int) ($index?->getPostsPerPage() ?: 10));

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
        ], $list->getStories());
    }

    /**
     * Per-call options replace the configured defaults as a whole, so the
     * configured version has to be passed again (see FRICTION.md).
     */
    private function options(): ContentOptions
    {
        return new ContentOptions(Version::from(config('storyblok.version')));
    }

    /** @return array<string, mixed> */
    private function post(StoryTransfer $story): array
    {
        /** @var PostTransfer $post */
        $post = $story->getContent();
        $body = $this->richtext->render($post->getBody());

        $author = $story->getRelation($post->getAuthor());
        $authorContent = $author instanceof StoryTransfer ? $author->getContent() : null;
        $name = $authorContent instanceof AuthorTransfer ? (string) $authorContent->getName() : '';

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
            'author' => [
                'name' => $name,
                'initials' => $this->initials($name),
                'role' => (string) ($authorContent?->getRole()),
                'bio' => (string) ($authorContent?->getBio()),
            ],
            'body' => $body->html,
            'headings' => $body->headings,
        ];
    }

    private function loadSite(): array
    {
        $settings = $this->storyblok->stories()->bySlug('settings', SiteSettingsTransfer::class, $this->options())?->getContent();

        return [
            'name' => (string) ($settings?->getSiteName() ?: config('app.name')),
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
            'url' => $link->getUrl() ?: $link->getCachedUrl() ?: null,
            'email' => $link->getUrl() ? 'mailto:'.$link->getUrl() : null,
            default => null,
        };
    }

    private function initials(string $name): string
    {
        $initials = array_map(fn (string $w) => mb_strtoupper(mb_substr($w, 0, 1)), preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY));

        return implode('', array_slice($initials, 0, 2));
    }
}
