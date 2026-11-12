<?php

namespace Tests\Feature;

use Tests\Support\FakesStoryblok;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use FakesStoryblok;

    private const POST = 'posts/typed-content-from-storyblok';

    private function asset(string $url, string $alt = ''): array
    {
        return ['id' => 1, 'filename' => $url, 'alt' => $alt, 'fieldtype' => 'asset'];
    }

    /** @return array<string, string> meta name/property => content, for assertions on the rendered head */
    private function meta(string $html): array
    {
        preg_match_all('/<meta\s+(?:name|property)="([^"]+)"\s+content="([^"]*)"/', $html, $m, PREG_SET_ORDER);

        $meta = [];
        foreach ($m as [, $key, $value]) {
            $meta[$key] = html_entity_decode($value, ENT_QUOTES);
        }

        return $meta;
    }

    public function test_a_post_describes_itself_to_search_engines_and_social_networks(): void
    {
        $this->fakeStoryblok();

        $html = $this->get('/'.self::POST)->assertOk()->getContent();
        $meta = $this->meta($html);

        $this->assertStringContainsString('<title>Typed content from a headless CMS · your-blog</title>', $html);
        $this->assertStringContainsString('<link rel="canonical" href="'.url('/'.self::POST).'">', $html);
        $this->assertSame('Why generating one PHP class per component beats passing arrays around, and what it costs you.', $meta['description']);
        $this->assertSame('article', $meta['og:type']);
        $this->assertSame('your-blog', $meta['og:site_name']);
        $this->assertSame(url('/'.self::POST), $meta['og:url']);
        $this->assertSame('2026-11-09T08:00:00+00:00', $meta['article:published_time']);
        $this->assertSame('Sample Author', $meta['article:author']);
        $this->assertStringContainsString('<meta property="article:tag" content="PHP">', $html);
        $this->assertSame('summary', $meta['twitter:card'], 'No image anywhere, so no large card.');
        $this->assertArrayNotHasKey('og:image', $meta);
    }

    public function test_editor_set_seo_fields_win_over_the_post_fields(): void
    {
        $this->fakeStoryblok([self::POST => [
            'seo_title' => 'A custom SEO title',
            'seo_description' => 'A custom SEO description.',
            'og_image' => $this->asset('https://a.storyblok.com/f/9/800x600/abc/social.jpg', 'A diagram'),
        ]]);

        $html = $this->get('/'.self::POST)->assertOk()->getContent();
        $meta = $this->meta($html);

        $this->assertStringContainsString('<title>A custom SEO title</title>', $html, 'seo_title is the complete title, used verbatim.');
        $this->assertSame('A custom SEO description.', $meta['description']);
        $this->assertSame('A custom SEO description.', $meta['og:description']);
        $this->assertSame('https://a.storyblok.com/f/9/800x600/abc/social.jpg/m/1200x630', $meta['og:image']);
        $this->assertSame('A diagram', $meta['og:image:alt']);
        $this->assertSame('summary_large_image', $meta['twitter:card']);
        $this->assertSame($meta['og:image'], $meta['twitter:image']);
    }

    public function test_the_cover_is_the_social_image_when_there_is_no_dedicated_one(): void
    {
        $this->fakeStoryblok([self::POST => ['cover_image' => $this->asset('https://a.storyblok.com/f/9/1600x900/abc/cover.jpg')]]);

        $meta = $this->meta($this->get('/'.self::POST)->assertOk()->getContent());

        $this->assertSame('https://a.storyblok.com/f/9/1600x900/abc/cover.jpg/m/1200x630', $meta['og:image']);
        $this->assertSame('Typed content from a headless CMS', $meta['og:image:alt'], 'Alt falls back to the post title.');
    }

    public function test_the_site_default_image_is_the_last_fallback_and_is_used_by_other_pages(): void
    {
        $this->fakeStoryblok(['settings' => ['default_og_image' => $this->asset('https://a.storyblok.com/f/1/1200x630/abc/site.jpg')]]);

        foreach (['/', '/about', '/categories', '/categories/laravel', '/'.self::POST] as $path) {
            $this->assertSame(
                'https://a.storyblok.com/f/1/1200x630/abc/site.jpg/m/1200x630',
                $this->meta($this->get($path)->assertOk()->getContent())['og:image'] ?? null,
                "No default image on {$path}",
            );
        }
    }

    public function test_user_content_is_escaped_in_attributes_and_cannot_break_out_of_the_json_ld(): void
    {
        $this->fakeStoryblok([self::POST => [
            'title' => '</script><script>alert(1)</script>',
            'seo_title' => '',
            'excerpt' => 'He said "hi" & <b>left</b>',
            'seo_description' => '',
        ]]);

        $html = $this->get('/'.self::POST)->assertOk()->getContent();
        $head = substr($html, 0, strpos($html, '</head>'));

        $this->assertStringNotContainsString('</script><script>alert(1)', $head);
        $this->assertStringContainsString('content="He said &quot;hi&quot; &amp; left"', $head);

        preg_match('~<script type="application/ld\+json">(.*?)</script>~s', $head, $m);
        $this->assertSame('</script><script>alert(1)</script>', json_decode($m[1], true)['headline']);
    }

    public function test_a_post_has_valid_blog_posting_json_ld(): void
    {
        $this->fakeStoryblok();

        $html = $this->get('/'.self::POST)->assertOk()->getContent();
        preg_match('~<script type="application/ld\+json">(.*?)</script>~s', $html, $m);
        $data = json_decode($m[1], true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('https://schema.org', $data['@context']);
        $this->assertSame('BlogPosting', $data['@type']);
        $this->assertSame('Typed content from a headless CMS', $data['headline']);
        $this->assertSame('2026-11-09T08:00:00+00:00', $data['datePublished']);
        $this->assertSame(['@type' => 'Person', 'name' => 'Sample Author'], $data['author']);
        $this->assertSame(url('/'.self::POST), $data['mainEntityOfPage']['@id']);
        $this->assertSame('PHP, Storyblok', $data['keywords']);
        $this->assertArrayNotHasKey('image', $data, 'No image, so the key is left out rather than null.');
    }

    public function test_home_uses_the_intro_and_paginated_pages_have_their_own_canonical(): void
    {
        $this->fakeStoryblok(['home' => ['posts_per_page' => 2]]);

        $first = $this->get('/')->assertOk()->getContent();
        $second = $this->get('/?page=2')->assertOk()->getContent();

        $this->assertStringContainsString('<link rel="canonical" href="'.url('/').'">', $first);
        $this->assertStringContainsString('<link rel="canonical" href="'.url('/').'?page=2">', $second);
        $this->assertStringContainsString('<title>your-blog</title>', $first);
        $this->assertStringContainsString('content="Notes on building things with Laravel', $first);
        $this->assertSame('website', $this->meta($first)['og:type']);
    }

    public function test_category_about_and_categories_pages_have_their_own_metadata(): void
    {
        $this->fakeStoryblok();

        $category = $this->get('/categories/laravel')->assertOk()->getContent();
        $this->assertStringContainsString('<title>Laravel · your-blog</title>', $category);
        $this->assertSame('Building the blog with Laravel, Blade and Vite.', $this->meta($category)['description']);
        $this->assertStringContainsString('<link rel="canonical" href="'.url('/categories/laravel').'">', $category);

        $about = $this->get('/about')->assertOk()->getContent();
        $this->assertStringContainsString('<title>About · your-blog</title>', $about);
        $this->assertStringStartsWith('This blog is a developer notebook', $this->meta($about)['description']);

        $index = $this->get('/categories')->assertOk()->getContent();
        $this->assertStringContainsString('<title>Categories · your-blog</title>', $index);
        $this->assertSame('Browse the posts by category.', $this->meta($index)['description']);
    }

    public function test_a_category_page_past_the_first_has_its_own_canonical(): void
    {
        $this->fakeStoryblok(['home' => ['posts_per_page' => 3]]);

        $this->assertStringContainsString(
            '<link rel="canonical" href="'.url('/categories/laravel').'?page=2">',
            $this->get('/categories/laravel?page=2')->assertOk()->getContent(),
        );
    }

    public function test_draft_pages_are_marked_noindex_in_the_markup_as_well_as_the_header(): void
    {
        $this->fakeStoryblok();
        $timestamp = time();
        $query = http_build_query(['_storyblok_tk' => ['space_id' => '340703', 'timestamp' => (string) $timestamp, 'token' => sha1("340703:test-preview-token:{$timestamp}")]]);

        $this->get('/'.self::POST.'?'.$query)->assertOk()->assertSee('<meta name="robots" content="noindex, nofollow">', false);
        $this->get('/'.self::POST)->assertDontSee('name="robots"', false);
    }

    public function test_robots_txt_names_the_sitemap_in_production(): void
    {
        $this->app['env'] = 'production';

        $response = $this->get('/robots.txt')->assertOk();

        $this->assertStringStartsWith('text/plain', $response->headers->get('Content-Type'));
        $this->assertSame("User-agent: *\nDisallow: /preview/\n\nSitemap: ".url('/sitemap.xml')."\n", $response->getContent());
    }

    public function test_robots_txt_keeps_crawlers_out_of_everything_but_production(): void
    {
        foreach (['local', 'staging', 'testing'] as $environment) {
            $this->app['env'] = $environment;

            $this->assertSame("User-agent: *\nDisallow: /\n", $this->get('/robots.txt')->assertOk()->getContent(), $environment);
        }
    }

    public function test_there_is_no_static_robots_txt_shadowing_the_route(): void
    {
        $this->assertFileDoesNotExist(public_path('robots.txt'));
    }
}
