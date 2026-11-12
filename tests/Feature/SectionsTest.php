<?php

namespace Tests\Feature;

use Tests\Support\FakesStoryblok;
use Tests\TestCase;

class SectionsTest extends TestCase
{
    use FakesStoryblok;

    private function xml(string $body): \SimpleXMLElement
    {
        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body);
        $errors = libxml_get_errors();
        libxml_use_internal_errors($previous);

        $this->assertNotFalse($xml, 'Not well-formed XML: '.json_encode(array_map(fn ($e) => trim($e->message), $errors)));

        return $xml;
    }

    public function test_categories_index_lists_every_category_with_its_description(): void
    {
        $this->fakeStoryblok();

        $this->get('/categories')
            ->assertOk()
            ->assertSee('~/categories')
            ->assertSeeInOrder(['Laravel', 'PHP', 'Storyblok', 'Tooling'])
            ->assertSee('Building the blog with Laravel, Blade and Vite.')
            ->assertSee('href="'.url('/categories/php').'"', false);
    }

    public function test_category_page_lists_only_posts_in_that_category(): void
    {
        $this->fakeStoryblok();

        $this->get('/categories/storyblok')
            ->assertOk()
            ->assertSee('// category', false)
            ->assertSee('2 posts')
            ->assertSee('Typed content from a headless CMS')
            ->assertSee('Rendering richtext with embedded blocks')
            ->assertDontSee('Caching published reads without surprises')
            ->assertSee('<title>Storyblok · your-blog</title>', false);
    }

    public function test_category_page_marks_the_current_category_and_singularises(): void
    {
        $this->fakeStoryblok();

        $this->get('/categories/php')
            ->assertOk()
            ->assertSee('1 post')
            ->assertDontSee('1 posts')
            ->assertSee('aria-current="page"', false);
    }

    public function test_category_pages_paginate_and_404_past_the_end(): void
    {
        $this->fakeStoryblok(['home' => ['posts_per_page' => 3]]);

        // Laravel has four posts: three on the first page, one on the second.
        $this->get('/categories/laravel')
            ->assertOk()
            ->assertSee('older →')
            ->assertDontSee('Sitemaps from the links endpoint');

        $this->get('/categories/laravel?page=2')
            ->assertOk()
            ->assertSee('Sitemaps from the links endpoint')
            ->assertSee('← newer');

        $this->get('/categories/laravel?page=3')->assertNotFound();
    }

    public function test_unknown_category_is_404(): void
    {
        $this->fakeStoryblok();

        $this->get('/categories/nope')->assertNotFound();
    }

    public function test_about_page_renders_the_richtext(): void
    {
        $this->fakeStoryblok();

        $this->get('/about')
            ->assertOk()
            ->assertSee('<h1 class="page-title mb-4">About</h1>', false)
            ->assertSee('This blog is a developer notebook')
            ->assertSee('<title>About · your-blog</title>', false);
    }

    public function test_navigation_marks_the_section_you_are_in(): void
    {
        $this->fakeStoryblok();

        $this->get('/categories/laravel')->assertSee('href="/categories" class="active"', false);
        $this->get('/about')->assertSee('href="/about" class="active"', false);
    }

    public function test_feed_is_valid_rss_with_newest_first_and_a_discoverable_link(): void
    {
        $this->fakeStoryblok();

        $response = $this->get('/feed.xml')->assertOk();
        $this->assertStringStartsWith('application/rss+xml', $response->headers->get('Content-Type'));

        $xml = $this->xml($response->getContent());
        $items = $xml->channel->item;

        $this->assertCount(5, $items);
        $this->assertSame('Typed content from a headless CMS', (string) $items[0]->title);
        $this->assertSame('Sitemaps from the links endpoint', (string) $items[4]->title);
        $this->assertSame(url('/posts/typed-content-from-storyblok'), (string) $items[0]->link);
        $this->assertSame('true', (string) $items[0]->guid['isPermaLink']);
        $this->assertStringContainsString('<h2 id="start-with-the-content-model">', (string) $items[0]->children('content', true)->encoded);

        $this->get('/')->assertSee('rel="alternate" type="application/rss+xml"', false);
    }

    public function test_feed_content_uses_absolute_urls_and_survives_cdata_terminators(): void
    {
        $link = ['type' => 'link', 'attrs' => ['href' => '/posts/other']];

        $paragraph = [
            'type' => 'paragraph',
            'content' => [
                ['type' => 'text', 'text' => 'see this', 'marks' => [$link]],
                ['type' => 'text', 'text' => ' and ]]> that'],
            ],
        ];

        $this->fakeStoryblok(['posts/typed-content-from-storyblok' => ['body' => ['type' => 'doc', 'content' => [$paragraph]]]]);

        $xml = $this->xml($this->get('/feed.xml')->assertOk()->getContent());
        $content = (string) $xml->channel->item[0]->children('content', true)->encoded;

        $this->assertStringContainsString('<a href="'.url('/posts/other').'">see this</a>', $content);
        $this->assertStringContainsString(']]&gt; that', $content);
    }

    public function test_sitemap_lists_public_pages_from_the_links_tree(): void
    {
        $this->fakeStoryblok();

        $response = $this->get('/sitemap.xml')->assertOk();
        $this->assertStringStartsWith('application/xml', $response->headers->get('Content-Type'));

        $xml = $this->xml($response->getContent());
        $locs = [];
        foreach ($xml->url as $url) {
            $locs[(string) $url->loc] = isset($url->lastmod) ? (string) $url->lastmod : null;
        }

        foreach (['/', '/categories', '/about', '/categories/laravel', '/posts/typed-content-from-storyblok', '/posts/sitemaps-from-the-links-endpoint'] as $path) {
            $this->assertArrayHasKey($path === '/' ? url('/') : url($path), $locs, "missing {$path}");
        }

        // Stories without a route are not listed.
        foreach ($locs as $loc => $_) {
            $this->assertStringNotContainsString('/settings', $loc);
            $this->assertStringNotContainsString('/authors', $loc);
            $this->assertStringNotContainsString('/home', $loc);
        }

        // Posts carry a last-modified date, other pages do not.
        $this->assertNotNull($locs[url('/posts/typed-content-from-storyblok')]);
        $this->assertNull($locs[url('/about')]);
    }
}
