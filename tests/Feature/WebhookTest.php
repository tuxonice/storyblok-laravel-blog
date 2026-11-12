<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    private const SECRET = 'test-webhook-secret';

    protected function setUp(): void
    {
        parent::setUp();

        // The dedicated Storyblok store is a file store in the app; use memory here.
        config(['cache.stores.storyblok' => ['driver' => 'array']]);
        Cache::purge('storyblok');

        Cache::store('storyblok')->put('probe', 'cached');
    }

    private function cached(): bool
    {
        return Cache::store('storyblok')->has('probe');
    }

    /** @param array<string, mixed> $payload */
    private function webhook(array $payload, ?string $signWith = self::SECRET, ?string $signature = null)
    {
        $body = json_encode($payload);
        $headers = ['CONTENT_TYPE' => 'application/json'];

        $signature ??= $signWith === null ? null : hash_hmac('sha1', $body, $signWith);

        if ($signature !== null) {
            $headers['HTTP_WEBHOOK_SIGNATURE'] = $signature;
        }

        return $this->call('POST', '/webhooks/storyblok', [], [], [], $headers, $body);
    }

    public function test_a_correctly_signed_event_clears_the_storyblok_cache(): void
    {
        $this->webhook(['action' => 'published', 'space_id' => 340703, 'full_slug' => 'posts/x'])
            ->assertOk()
            ->assertExactJson(['cleared' => true]);

        $this->assertFalse($this->cached());
    }

    public function test_only_the_storyblok_store_is_cleared(): void
    {
        Cache::put('unrelated', 'kept');

        $this->webhook(['action' => 'published'])->assertOk();

        $this->assertSame('kept', Cache::get('unrelated'));
    }

    public function test_a_wrong_signature_is_refused_and_nothing_is_cleared(): void
    {
        $this->webhook(['action' => 'published'], signature: str_repeat('0', 40))->assertForbidden();
        $this->webhook(['action' => 'published'], signWith: 'another-secret')->assertForbidden();

        $this->assertTrue($this->cached());
    }

    public function test_a_missing_signature_is_refused(): void
    {
        $this->webhook(['action' => 'published'], signWith: null)->assertForbidden();

        $this->assertTrue($this->cached());
    }

    public function test_a_signature_for_a_different_body_is_refused(): void
    {
        $signatureOfAnotherBody = hash_hmac('sha1', json_encode(['action' => 'unpublished']), self::SECRET);

        $this->webhook(['action' => 'published'], signature: $signatureOfAnotherBody)->assertForbidden();

        $this->assertTrue($this->cached());
    }

    public function test_the_endpoint_refuses_everything_while_no_secret_is_configured(): void
    {
        config(['storyblok.webhook_secret' => '']);

        // Even a signature made with an empty secret must not work.
        $this->webhook(['action' => 'published'], signWith: '')->assertStatus(503);
        $this->webhook(['action' => 'published'])->assertStatus(503);

        $this->assertTrue($this->cached());
    }

    public function test_an_event_for_another_space_is_acknowledged_but_ignored(): void
    {
        $this->webhook(['action' => 'published', 'space_id' => 999])
            ->assertOk()
            ->assertExactJson(['ignored' => true]);

        $this->assertTrue($this->cached());
    }

    public function test_only_post_is_allowed(): void
    {
        $this->get('/webhooks/storyblok')->assertStatus(405);
    }

    public function test_the_clear_cache_command_empties_the_store(): void
    {
        $this->artisan('storyblok:clear-cache')->expectsOutput('Storyblok cache cleared.')->assertExitCode(0);

        $this->assertFalse($this->cached());
    }
}
