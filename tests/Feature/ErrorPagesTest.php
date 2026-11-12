<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\Support\FailingContentClient;
use Tests\Support\FakesStoryblok;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use FakesStoryblok;

    protected function setUp(): void
    {
        parent::setUp();

        // Errors are shown as pages, not as debug output.
        config(['app.debug' => false, 'app.name' => 'fallback-blog']);

        Route::get('/_test/403', fn () => abort(403, 'the secret reason'));
        Route::get('/_test/503', fn () => abort(503));
        Route::get('/_test/boom', fn () => throw new \RuntimeException('database password is hunter2'));
    }

    public function test_an_unknown_url_gets_the_designed_404_page_with_the_site_chrome(): void
    {
        $this->fakeStoryblok();

        $this->get('/nope')
            ->assertNotFound()
            ->assertSee('<h1 class="page-title">Page not found</h1>', false)
            ->assertSee('// 404', false)
            ->assertSee('GET /nope')
            ->assertSee('<title>Page not found · your-blog</title>', false)   // site name from Storyblok
            ->assertSee('Built with Storyblok')                               // the layout's footer
            ->assertSee('Back to the blog');
    }

    public function test_a_missing_post_or_category_gets_the_same_page(): void
    {
        $this->fakeStoryblok();

        foreach (['/posts/nope', '/categories/nope', '/categories/laravel?page=99'] as $url) {
            $this->get($url)->assertNotFound()->assertSee('Page not found');
        }
    }

    public function test_error_pages_are_not_indexed_and_have_no_canonical_url(): void
    {
        $this->fakeStoryblok();

        $this->get('/nope')
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertDontSee('rel="canonical"', false)
            ->assertDontSee('og:url', false);
    }

    public function test_the_requested_path_is_escaped(): void
    {
        $this->fakeStoryblok();

        $this->get('/'.rawurlencode('"><img src=x onerror=alert(1)>'))
            ->assertNotFound()
            ->assertDontSee('<img src=x onerror', false);
    }

    public function test_the_404_page_still_renders_when_storyblok_is_down(): void
    {
        $storyblok = $this->storyblokIsDown();

        $this->get('/nope')
            ->assertNotFound()
            ->assertSee('Page not found')
            ->assertSee('<title>Page not found · fallback-blog</title>', false)  // static settings, not Storyblok's
            ->assertSee('href="/categories"', false)                              // fallback navigation
            ->assertDontSee(FailingContentClient::MESSAGE);

        $this->assertGreaterThan(0, $storyblok->requests, 'It should have tried Storyblok first.');
    }

    public function test_other_client_errors_use_the_layout_and_never_show_the_exception_message(): void
    {
        $this->fakeStoryblok();

        $this->get('/_test/403')
            ->assertForbidden()
            ->assertSee('<h1 class="page-title">Forbidden</h1>', false)
            ->assertDontSee('the secret reason');

        $this->get('/webhooks/storyblok')
            ->assertStatus(405)
            ->assertSee('Method not allowed')
            ->assertSee('Built with Storyblok');
    }

    public function test_a_server_error_is_a_standalone_page_that_leaks_nothing(): void
    {
        $this->fakeStoryblok();

        $response = $this->get('/_test/boom')->assertStatus(500);
        $html = $response->getContent();

        $this->assertStringContainsString('Something went wrong', $html);
        $this->assertStringContainsString('<meta name="robots" content="noindex, nofollow">', $html);
        $this->assertStringNotContainsString('hunter2', $html);
        $this->assertStringNotContainsString('RuntimeException', $html);
        $this->assertStringNotContainsString('build/assets', $html, 'The 500 page must not depend on built assets.');
        $this->assertStringNotContainsString('Built with Storyblok', $html, 'No layout, so nothing that reads Storyblok.');
    }

    public function test_the_site_shows_a_friendly_500_when_storyblok_is_down_and_nothing_is_cached(): void
    {
        $this->storyblokIsDown();

        $response = $this->get('/')->assertStatus(500);

        $response->assertSee('Something went wrong');
        $response->assertDontSee(FailingContentClient::MESSAGE);
        $response->assertDontSee('StoryblokApiException');
    }

    public function test_a_503_says_so(): void
    {
        $this->fakeStoryblok();

        $this->get('/_test/503')->assertStatus(503)->assertSee('Be right back')->assertSee('briefly unavailable');
    }
}
