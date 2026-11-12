<?php

namespace Tests\Feature;

use Tests\Support\FakesStoryblok;
use Tests\TestCase;

class DraftModeTest extends TestCase
{
    use FakesStoryblok;

    private const SPACE = '340703';

    private const PREVIEW_TOKEN = 'test-preview-token';

    private const DELIVERY_TOKEN = 'test-delivery-token';

    private const POST = '/posts/typed-content-from-storyblok';

    /** The query string the Visual Editor adds to the preview URL. */
    private function editorQuery(?int $timestamp = null, ?string $token = null, string $space = self::SPACE): array
    {
        $timestamp ??= time();

        return [
            '_storyblok' => '12345',
            '_storyblok_tk' => [
                'space_id' => $space,
                'timestamp' => (string) $timestamp,
                'token' => $token ?? sha1("{$space}:".self::PREVIEW_TOKEN.":{$timestamp}"),
            ],
        ];
    }

    private function editorUrl(string $path = self::POST, array $query = []): string
    {
        return $path.'?'.http_build_query($query ?: $this->editorQuery());
    }

    public function test_a_signed_editor_request_reads_drafts_with_the_preview_token(): void
    {
        $network = $this->fakeStoryblokNetwork();

        $this->get($this->editorUrl())
            ->assertOk()
            ->assertSee('// draft preview', false)
            ->assertSee('Typed content from a headless CMS');

        $this->assertSame(['draft'], $network->versions());
        $this->assertSame([self::PREVIEW_TOKEN], $network->tokens());
    }

    public function test_draft_responses_are_private_unindexed_and_frameable_only_by_the_editor(): void
    {
        $this->fakeStoryblokNetwork();

        $response = $this->get($this->editorUrl())->assertOk();

        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $this->assertSame('noindex, nofollow', $response->headers->get('X-Robots-Tag'));
        $this->assertSame("frame-ancestors 'self' https://app.storyblok.com", $response->headers->get('Content-Security-Policy'));
    }

    public function test_the_visual_editor_bridge_is_loaded_in_draft_mode_only(): void
    {
        $this->fakeStoryblokNetwork();

        $this->get($this->editorUrl())
            ->assertSee('storyblok-v2-latest.js', false)
            ->assertSee('StoryblokBridge', false);

        $this->get(self::POST)
            ->assertDontSee('storyblok-v2-latest.js', false)
            ->assertDontSee('// draft preview', false);
    }

    public function test_ordinary_requests_read_published_content_with_the_delivery_token_and_are_cached(): void
    {
        $network = $this->fakeStoryblokNetwork();

        $this->get(self::POST)->assertOk();

        $this->assertSame(['published'], $network->versions());
        $this->assertSame([self::DELIVERY_TOKEN], $network->tokens());

        $callsAfterFirst = count($network->calls);
        $this->get(self::POST)->assertOk();

        $this->assertSame($callsAfterFirst, count($network->calls), 'The second published read should come from the cache.');
    }

    public function test_draft_reads_are_never_cached(): void
    {
        $network = $this->fakeStoryblokNetwork();

        $this->get($this->editorUrl())->assertOk();
        $callsAfterFirst = count($network->calls);

        $this->get($this->editorUrl())->assertOk();

        $this->assertSame($callsAfterFirst * 2, count($network->calls), 'Every draft read should reach Storyblok.');
    }

    public function test_draft_content_does_not_leak_into_the_published_cache(): void
    {
        $network = $this->fakeStoryblokNetwork();

        $this->get($this->editorUrl())->assertOk();
        $network->calls = [];

        // A visitor right after the editor: must read published with the delivery token, not reuse anything draft.
        $this->get(self::POST)->assertOk()->assertDontSee('// draft preview', false);

        $this->assertSame(['published'], $network->versions());
        $this->assertSame([self::DELIVERY_TOKEN], $network->tokens());
    }

    public function test_a_forged_token_is_ignored(): void
    {
        $network = $this->fakeStoryblokNetwork();

        $response = $this->get($this->editorUrl(query: $this->editorQuery(token: str_repeat('a', 40))))->assertOk();

        $response->assertDontSee('// draft preview', false);
        $response->assertCookieMissing('storyblok_draft');
        $this->assertSame(['published'], $network->versions());
    }

    public function test_a_token_made_with_the_wrong_preview_token_is_ignored(): void
    {
        $timestamp = time();
        $forged = sha1(self::SPACE.':guessed-token:'.$timestamp);

        $this->fakeStoryblokNetwork();

        $this->get($this->editorUrl(query: $this->editorQuery($timestamp, $forged)))
            ->assertOk()
            ->assertDontSee('// draft preview', false);
    }

    public function test_an_expired_or_future_timestamp_is_ignored(): void
    {
        $this->fakeStoryblokNetwork();

        $this->get($this->editorUrl(query: $this->editorQuery(time() - 7200)))->assertDontSee('// draft preview', false);
        $this->get($this->editorUrl(query: $this->editorQuery(time() + 3600)))->assertDontSee('// draft preview', false);
    }

    public function test_a_token_for_another_space_is_ignored(): void
    {
        $this->fakeStoryblokNetwork();

        $this->get($this->editorUrl(query: $this->editorQuery(space: '999')))->assertDontSee('// draft preview', false);
    }

    public function test_draft_mode_is_impossible_without_a_preview_token(): void
    {
        config(['storyblok.preview_token' => '']);
        $this->fakeStoryblokNetwork();

        // sha1 of an empty token is computable by anyone, so it must not unlock anything.
        $timestamp = time();
        $query = $this->editorQuery($timestamp, sha1(self::SPACE.'::'.$timestamp));

        $this->get($this->editorUrl(query: $query))->assertDontSee('// draft preview', false);
    }

    public function test_the_cookie_keeps_draft_mode_on_for_links_followed_inside_the_preview(): void
    {
        $network = $this->fakeStoryblokNetwork();

        $first = $this->get($this->editorUrl())->assertOk();
        $first->assertCookie('storyblok_draft');

        $cookie = $first->getCookie('storyblok_draft', false);
        $network->calls = [];

        $this->withCookie('storyblok_draft', (string) (time() + 600))
            ->get('/categories/laravel')
            ->assertOk()
            ->assertSee('// draft preview', false);

        $this->assertSame(['draft'], $network->versions());
        $this->assertNotNull($cookie);
    }

    public function test_an_expired_or_malformed_cookie_does_nothing(): void
    {
        $network = $this->fakeStoryblokNetwork();

        $this->withCookie('storyblok_draft', (string) (time() - 10))->get(self::POST)->assertDontSee('// draft preview', false);
        $this->withCookie('storyblok_draft', 'yes')->get(self::POST)->assertDontSee('// draft preview', false);

        $this->assertSame(['published'], $network->versions());
    }

    public function test_a_cookie_that_was_not_written_by_the_app_is_dropped_by_laravel(): void
    {
        $network = $this->fakeStoryblokNetwork();

        // Sent as-is (not encrypted): EncryptCookies cannot decrypt it, so it never reaches DraftMode.
        $this->call('GET', self::POST, [], ['storyblok_draft' => (string) (time() + 600)])
            ->assertDontSee('// draft preview', false);

        $this->assertSame(['published'], $network->versions());
    }

    public function test_a_preview_environment_reads_drafts_with_the_preview_token_everywhere(): void
    {
        config(['storyblok.version' => 'draft']);
        $network = $this->fakeStoryblokNetwork();

        $this->get(self::POST)->assertOk();

        $this->assertSame(['draft'], $network->versions());
        $this->assertSame([self::PREVIEW_TOKEN], $network->tokens());
    }

    public function test_the_feed_and_sitemap_ignore_draft_mode(): void
    {
        $network = $this->fakeStoryblokNetwork();

        $this->get('/feed.xml?'.http_build_query($this->editorQuery()))->assertOk();
        $this->get('/sitemap.xml?'.http_build_query($this->editorQuery()))->assertOk();

        $this->assertSame(['published'], $network->versions());
        $this->assertSame([self::DELIVERY_TOKEN], $network->tokens());
    }

    public function test_stories_without_a_page_redirect_home_keeping_the_editor_query(): void
    {
        $this->fakeStoryblokNetwork();
        $query = $this->editorQuery();

        foreach (['/home', '/settings'] as $path) {
            $response = $this->get($this->editorUrl($path, $query))->assertRedirect();

            parse_str((string) parse_url($response->headers->get('Location'), PHP_URL_QUERY), $kept);
            $this->assertSame($query['_storyblok_tk'], $kept['_storyblok_tk']);
        }
    }

    public function test_exiting_preview_clears_the_cookie(): void
    {
        $response = $this->get('/preview/exit')->assertRedirect(route('home'));

        $cookie = $response->getCookie('storyblok_draft', false);
        $this->assertNotNull($cookie);
        $this->assertLessThan(time(), $cookie->getExpiresTime());
    }
}
