<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class IpAllowlistTest extends TestCase
{
    private function fromIp(string $ip): static
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip]);
    }

    private function allow(string ...$entries): void
    {
        config(['app.allowed_ips' => $entries]);
    }

    public function test_an_empty_list_lets_everyone_in(): void
    {
        $this->allow();

        $this->fromIp('198.51.100.9')->get('/robots.txt')->assertOk();
    }

    public function test_a_listed_address_gets_in(): void
    {
        $this->allow('203.0.113.7');

        $this->fromIp('203.0.113.7')->get('/robots.txt')->assertOk();
    }

    public function test_an_unlisted_address_is_refused_with_a_plain_403(): void
    {
        $this->allow('203.0.113.7');

        $response = $this->fromIp('198.51.100.9')->get('/robots.txt')->assertForbidden();

        $this->assertSame('Forbidden', $response->getContent());
        $this->assertStringStartsWith('text/plain', $response->headers->get('Content-Type'));
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_every_page_is_refused_not_only_known_routes(): void
    {
        $this->allow('203.0.113.7');

        foreach (['/', '/posts/anything', '/feed.xml', '/sitemap.xml', '/no-such-page'] as $path) {
            $this->fromIp('198.51.100.9')->get($path)->assertForbidden();
        }
    }

    public function test_cidr_ranges_work_for_ipv4_and_ipv6(): void
    {
        $this->allow('203.0.113.0/24', '2001:db8::/32');

        $this->fromIp('203.0.113.200')->get('/robots.txt')->assertOk();
        $this->fromIp('2001:db8:1::5')->get('/robots.txt')->assertOk();
        $this->fromIp('203.0.114.1')->get('/robots.txt')->assertForbidden();
        $this->fromIp('2001:db9::1')->get('/robots.txt')->assertForbidden();
    }

    public function test_the_signed_webhook_stays_reachable(): void
    {
        $this->allow('203.0.113.7');
        config(['cache.stores.storyblok' => ['driver' => 'array']]);
        Cache::purge('storyblok');

        $body = json_encode(['action' => 'published']);

        $this->fromIp('198.51.100.9')->call('POST', '/webhooks/storyblok', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_WEBHOOK_SIGNATURE' => hash_hmac('sha1', $body, 'test-webhook-secret'),
        ], $body)->assertOk();
    }

    public function test_the_health_check_stays_reachable(): void
    {
        $this->allow('203.0.113.7');

        $this->fromIp('198.51.100.9')->get('/up')->assertOk();
    }

    public function test_the_list_is_read_from_a_comma_separated_env_value(): void
    {
        $load = function (string $value): array {
            putenv("ALLOWED_IPS={$value}");
            $_ENV['ALLOWED_IPS'] = $_SERVER['ALLOWED_IPS'] = $value;

            return (require config_path('app.php'))['allowed_ips'];
        };

        try {
            $this->assertSame(['203.0.113.7', '10.0.0.0/8'], $load(' 203.0.113.7 , 10.0.0.0/8, '));
            $this->assertSame([], $load(''));
        } finally {
            putenv('ALLOWED_IPS');
            unset($_ENV['ALLOWED_IPS'], $_SERVER['ALLOWED_IPS']);
        }
    }
}
