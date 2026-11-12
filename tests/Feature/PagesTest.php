<?php

namespace Tests\Feature;

use App\Blog\Blog;
use Tests\Support\FakeContentClient;
use Tests\TestCase;
use Tlab\StoryblokTransfers\Content\StoryblokContent;

class PagesTest extends TestCase
{
    private function fakeStoryblok(array $contentOverrides = []): void
    {
        $this->app->instance(StoryblokContent::class, StoryblokContent::usingClient(
            new FakeContentClient($contentOverrides),
            config('storyblok.namespace'),
        ));
        $this->app->forgetInstance(Blog::class);
    }

    public function test_home_shows_featured_post_latest_posts_and_settings(): void
    {
        $this->fakeStoryblok();

        $this->get('/')
            ->assertOk()
            ->assertSee('// featured', false)
            ->assertSeeInOrder(['Typed content from a headless CMS', 'Latest posts', 'Caching published reads without surprises'])
            ->assertSee('your-blog')                    // site_settings.site_name
            ->assertSee('sitemap')                      // footer link from site_settings
            ->assertSee('Notes on building things')     // blog_index.intro in the sidebar
            ->assertSee('Laravel');                     // a category chip
    }

    public function test_home_lists_newest_posts_first(): void
    {
        $this->fakeStoryblok();

        $this->get('/')->assertSeeInOrder([
            'Typed content from a headless CMS',
            'Caching published reads without surprises',
            'Rendering richtext with embedded blocks',
            'One Blade component per block',
            'Sitemaps from the links endpoint',
        ]);
    }

    public function test_home_paginates_with_posts_per_page(): void
    {
        $this->fakeStoryblok(['home' => ['posts_per_page' => 2]]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Caching published reads without surprises')
            ->assertDontSee('Rendering richtext with embedded blocks')
            ->assertSee('older →');

        $this->get('/?page=2')
            ->assertOk()
            ->assertSee('Rendering richtext with embedded blocks')
            ->assertSee('← newer');
    }

    public function test_post_resolves_author_categories_tags_and_headings(): void
    {
        $this->fakeStoryblok();

        $this->get('/posts/typed-content-from-storyblok')
            ->assertOk()
            ->assertSee('Sample Author')                         // author relation
            ->assertSee('Backend developer who writes about')    // author bio
            ->assertSee('#php #storyblok', false)                // tags
            ->assertSee('// on this page', false)
            ->assertSee('id="start-with-the-content-model"', false)
            ->assertSee('// note', false)                        // embedded callout
            ->assertSee('$story = $storyblok-&gt;stories()', false) // embedded code block, HTML-escaped
            ->assertSee('// keep reading', false);
    }

    public function test_post_page_title_uses_site_name(): void
    {
        $this->fakeStoryblok();

        $this->get('/posts/caching-published-reads')
            ->assertOk()
            ->assertSee('<title>Caching published reads without surprises · your-blog</title>', false);
    }

    public function test_unknown_post_is_404(): void
    {
        $this->fakeStoryblok();

        $this->get('/posts/nope')->assertNotFound();
    }
}
