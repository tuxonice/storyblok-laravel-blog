<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Tests\Support\FakesStoryblok;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use FakesStoryblok;

    private const POST = '/posts/typed-content-from-storyblok';

    private const PREVIEW_TOKEN = 'test-preview-token';

    /** @return array<string, string> directive name => sources */
    private function policy(TestResponse $response): array
    {
        $policy = [];

        foreach (explode(';', (string) $response->headers->get('Content-Security-Policy')) as $directive) {
            [$name, $sources] = array_pad(explode(' ', trim($directive), 2), 2, '');
            $policy[$name] = $sources;
        }

        return $policy;
    }

    private function nonce(TestResponse $response): string
    {
        $this->assertMatchesRegularExpression("/'nonce-([^']+)'/", $this->policy($response)['script-src']);
        preg_match("/'nonce-([^']+)'/", $this->policy($response)['script-src'], $match);

        return $match[1];
    }

    /**
     * Every inline <script> (no src) must carry the response's nonce, or the browser blocks it.
     * JSON-LD is a data block, never executed, so CSP does not apply to it.
     */
    private function assertInlineScriptsHaveNonce(TestResponse $response): void
    {
        $nonce = $this->nonce($response);

        preg_match_all('/<script\b(?![^>]*\bsrc=)(?![^>]*application\/ld\+json)[^>]*>/i', $response->getContent(), $tags);

        $this->assertNotEmpty($tags[0], 'The page should have inline scripts to check.');

        foreach ($tags[0] as $tag) {
            $this->assertStringContainsString('nonce="'.$nonce.'"', $tag);
        }
    }

    private function editorUrl(string $path = self::POST): string
    {
        $space = '340703';
        $timestamp = (string) time();

        return $path.'?'.http_build_query([
            '_storyblok' => '12345',
            '_storyblok_tk' => [
                'space_id' => $space,
                'timestamp' => $timestamp,
                'token' => sha1("{$space}:".self::PREVIEW_TOKEN.":{$timestamp}"),
            ],
        ]);
    }

    public function test_pages_get_the_security_headers(): void
    {
        $this->fakeStoryblok();

        $response = $this->get('/')->assertOk();

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Cross-Origin-Opener-Policy', 'same-origin');
        $this->assertStringContainsString('camera=()', $response->headers->get('Permissions-Policy'));
    }

    public function test_the_policy_allows_only_this_site_and_storyblok_images(): void
    {
        $this->fakeStoryblok();

        $policy = $this->policy($this->get('/')->assertOk());

        $this->assertSame("'self'", $policy['default-src']);
        $this->assertStringNotContainsString('unsafe-inline', $policy['script-src']);
        $this->assertStringNotContainsString('unsafe-eval', $policy['script-src']);
        $this->assertStringNotContainsString('storyblok', $policy['script-src'], 'The bridge is for draft mode only.');
        $this->assertSame("'self' data: https://*.storyblok.com", $policy['img-src']);
        $this->assertSame("'none'", $policy['object-src']);
        $this->assertSame("'self'", $policy['base-uri']);
        $this->assertSame("'self'", $policy['form-action']);
        $this->assertSame("'self'", $policy['frame-ancestors']);
    }

    public function test_every_inline_script_carries_the_nonce(): void
    {
        $this->fakeStoryblok();

        $this->assertInlineScriptsHaveNonce($this->get('/')->assertOk());
        $this->assertInlineScriptsHaveNonce($this->get(self::POST)->assertOk());
    }

    public function test_the_nonce_changes_on_every_request(): void
    {
        $this->fakeStoryblok();

        $this->assertNotSame($this->nonce($this->get('/')), $this->nonce($this->get('/')));
    }

    public function test_vite_tags_carry_the_nonce(): void
    {
        $this->fakeStoryblok();

        $response = $this->get('/')->assertOk();

        $this->assertMatchesRegularExpression(
            '/<script type="module" src="[^"]*build\/assets\/[^"]+" nonce="'.preg_quote($this->nonce($response), '/').'"/',
            $response->getContent(),
        );
    }

    public function test_draft_mode_lets_the_editor_frame_the_page_and_load_the_bridge(): void
    {
        $this->fakeStoryblokNetwork();

        $response = $this->get($this->editorUrl())->assertOk();
        $policy = $this->policy($response);

        $this->assertSame("'self' https://app.storyblok.com", $policy['frame-ancestors']);
        $this->assertStringContainsString('https://app.storyblok.com', $policy['script-src']);
        $response->assertHeaderMissing('X-Frame-Options');
        $this->assertInlineScriptsHaveNonce($response);
    }

    public function test_hsts_is_sent_over_https_only(): void
    {
        $this->fakeStoryblok();

        $this->get('/')->assertHeaderMissing('Strict-Transport-Security');
        $this->get('https://localhost/')->assertHeader('Strict-Transport-Security', 'max-age=31536000');
    }

    public function test_error_pages_and_feeds_get_the_headers_too(): void
    {
        config(['app.debug' => false]);
        Route::get('/_test/boom', fn () => throw new \RuntimeException('boom'));
        $this->fakeStoryblok();

        $this->get('/nope')->assertNotFound()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get('/feed.xml')->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');

        $serverError = $this->get('/_test/boom')->assertStatus(500);
        $serverError->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $this->assertInlineScriptsHaveNonce($serverError);
    }
}
