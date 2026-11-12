<?php

namespace Tests\Feature;

use Tests\Support\FakesStoryblok;
use Tests\TestCase;

class AuthorPageTest extends TestCase
{
    use FakesStoryblok;

    private const PAGE = '/authors/sample-author';

    private const NOBODY = '00000000-0000-0000-0000-000000000000';

    private function asset(string $url): array
    {
        return ['id' => 1, 'filename' => $url, 'alt' => '', 'fieldtype' => 'asset'];
    }

    private function link(string $url): array
    {
        return ['fieldtype' => 'multilink', 'linktype' => 'url', 'url' => $url, 'cached_url' => $url];
    }

    /** @return array<string, array<string, mixed>> every post except the named ones belongs to somebody else */
    private function postsBy(string $author, array $slugs): array
    {
        $others = [];
        foreach (['typed-content-from-storyblok', 'caching-published-reads', 'richtext-with-embedded-blocks', 'blade-components-for-blocks', 'sitemaps-from-the-links-endpoint'] as $slug) {
            $others["posts/{$slug}"] = ['author' => in_array($slug, $slugs, true) ? $author : self::NOBODY];
        }

        return $others;
    }

    public function test_the_page_shows_the_author_and_their_posts(): void
    {
        $this->fakeStoryblok();

        $this->get(self::PAGE)
            ->assertOk()
            ->assertSee('// author', false)
            ->assertSee('<h1 class="page-title" id="author-name">Sample Author</h1>', false)
            ->assertSee('Backend developer')
            ->assertSee('Backend developer who writes about PHP, content modelling')
            ->assertSee('~/authors')
            ->assertSee('5 posts')
            ->assertSee('Typed content from a headless CMS')
            ->assertSee('Sitemaps from the links endpoint');
    }

    public function test_only_posts_by_that_author_are_listed(): void
    {
        $this->fakeStoryblok($this->postsBy('{{uuid:authors/sample-author}}', ['typed-content-from-storyblok', 'caching-published-reads']));

        $this->get(self::PAGE)
            ->assertOk()
            ->assertSee('2 posts')
            ->assertSee('Typed content from a headless CMS')
            ->assertSee('Caching published reads without surprises')
            ->assertDontSee('Rendering richtext with embedded blocks')
            ->assertDontSee('Sitemaps from the links endpoint');
    }

    public function test_an_author_without_posts_is_still_a_page(): void
    {
        $this->fakeStoryblok($this->postsBy(self::NOBODY, []));

        $this->get(self::PAGE)->assertOk()->assertSee('0 posts')->assertSee('No posts by Sample Author yet.');
    }

    public function test_the_author_page_paginates_and_404s_past_the_end(): void
    {
        $this->fakeStoryblok(['home' => ['posts_per_page' => 2]]);

        $this->get(self::PAGE)->assertOk()->assertSee('older →')->assertDontSee('Rendering richtext with embedded blocks');
        $this->get(self::PAGE.'?page=3')->assertOk()->assertSee('Sitemaps from the links endpoint')->assertSee('← newer');
        $this->get(self::PAGE.'?page=4')->assertNotFound();
        $this->get(self::PAGE.'?page=2')->assertSee('<link rel="canonical" href="'.url(self::PAGE).'?page=2">', false);
    }

    public function test_an_unknown_author_is_a_404(): void
    {
        $this->fakeStoryblok();

        $this->get('/authors/nobody')->assertNotFound()->assertSee('Page not found');
    }

    public function test_links_a_github_handle_or_url_and_the_website(): void
    {
        foreach (['@octocat', 'octocat', 'https://github.com/octocat', 'https://github.com/octocat/'] as $github) {
            $this->fakeStoryblok(['authors/sample-author' => ['github' => $github, 'website' => $this->link('https://www.example.com/me')]]);

            $this->get(self::PAGE)
                ->assertOk()
                ->assertSee('href="https://github.com/octocat"', false)
                ->assertSee('>github.com/octocat</a>', false)
                ->assertSee('href="https://www.example.com/me"', false)
                ->assertSee('>example.com</a>', false);
        }
    }

    public function test_unsafe_or_malformed_links_are_not_rendered(): void
    {
        $this->fakeStoryblok(['authors/sample-author' => [
            'github' => 'not a handle; <script>',
            'website' => $this->link('javascript:alert(1)'),
        ]]);

        $this->get(self::PAGE)
            ->assertOk()
            ->assertDontSee('javascript:', false)
            ->assertDontSee('rel="me noopener"', false) // no author link at all: only those carry rel="me"
            ->assertDontSee('<script>alert', false);
    }

    public function test_an_avatar_photo_is_resized_and_initials_are_the_fallback(): void
    {
        $this->fakeStoryblok(['authors/sample-author' => ['avatar' => $this->asset('https://a.storyblok.com/f/9/2400x2400/abc/me.jpg')]]);

        $this->get(self::PAGE)
            ->assertOk()
            ->assertSee('<img class="avatar xl" src="https://a.storyblok.com/f/9/2400x2400/abc/me.jpg/m/192x192" width="96" height="96" alt="" loading="lazy">', false);

        $this->fakeStoryblok();

        $this->get(self::PAGE)->assertOk()->assertSee('<span class="avatar xl" aria-hidden="true">SA</span>', false)->assertDontSee('<img class="avatar', false);
    }

    public function test_the_page_describes_itself_as_a_profile(): void
    {
        $this->fakeStoryblok(['authors/sample-author' => ['github' => 'octocat', 'website' => $this->link('https://example.com')]]);

        $html = $this->get(self::PAGE)->assertOk()->getContent();

        $this->assertStringContainsString('<title>Sample Author · your-blog</title>', $html);
        $this->assertStringContainsString('<link rel="canonical" href="'.url(self::PAGE).'">', $html);
        $this->assertStringContainsString('<meta property="og:type" content="profile">', $html);
        $this->assertStringContainsString('<meta name="description" content="Backend developer who writes about PHP', $html);

        preg_match('~<script type="application/ld\+json">(.*?)</script>~s', $html, $m);
        $data = json_decode($m[1], true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('Person', $data['@type']);
        $this->assertSame('Sample Author', $data['name']);
        $this->assertSame('Backend developer', $data['jobTitle']);
        $this->assertSame(url(self::PAGE), $data['url']);
        $this->assertSame(['https://example.com', 'https://github.com/octocat'], $data['sameAs']);
    }

    public function test_the_author_page_is_in_the_sitemap(): void
    {
        $this->fakeStoryblok();

        $this->get('/sitemap.xml')->assertOk()->assertSee('<loc>'.url(self::PAGE).'</loc>', false);
    }

    public function test_a_post_links_to_its_author_from_the_meta_row_and_the_author_card(): void
    {
        $this->fakeStoryblok();

        $html = $this->get('/posts/typed-content-from-storyblok')->assertOk()->getContent();

        $this->assertSame(2, substr_count($html, 'href="'.url(self::PAGE).'"'));
        $this->assertStringContainsString('<div class="meta-mono">Backend developer</div>', $html, 'The role shows on the author card.');
    }

    public function test_editor_supplied_navigation_links_are_sanitised(): void
    {
        $unsafe = ['component' => 'nav_link', 'label' => 'evil', 'link' => $this->link('javascript:alert(1)')];
        $safe = ['component' => 'nav_link', 'label' => 'friend', 'link' => $this->link('https://friend.example')];
        $this->fakeStoryblok(['settings' => ['nav' => [$unsafe, $safe], 'footer_links' => [$unsafe]]]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('javascript:', false)
            ->assertDontSee('>evil<', false)
            ->assertSee('href="https://friend.example"', false);
    }
}
