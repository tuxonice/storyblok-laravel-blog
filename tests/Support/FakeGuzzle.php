<?php

namespace Tests\Support;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Tlab\StoryblokTransfers\Client\ResourceNotFoundException;

/**
 * A real Guzzle client whose "network" is the in-memory FakeContentClient. The
 * package's own StoryblokContentClient and CachingContentClient run on top of
 * it, so token choice, draft/published version and caching are tested for real.
 */
class FakeGuzzle
{
    /** @var array<int, array{path: string, query: array<string, string>, token: string}> */
    public array $calls = [];

    public function __construct(private readonly FakeContentClient $fake) {}

    public function client(): Client
    {
        return new Client(['handler' => HandlerStack::create(function (RequestInterface $request) {
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $query);
            $token = (string) ($query['token'] ?? '');
            unset($query['token']);

            $path = ltrim((string) preg_replace('#^.*?/v2/#', '', $uri->getPath()), '/');
            $this->calls[] = ['path' => $path, 'query' => $query, 'token' => $token];

            try {
                $response = $this->fake->get($path, $query);
            } catch (ResourceNotFoundException) {
                return Create::promiseFor(new Response(404, [], '{}'));
            }

            $headers = array_map('strval', array_filter([
                'Total' => $response->total,
                'Per-Page' => $response->perPage,
            ], fn ($v) => $v !== null));

            return Create::promiseFor(new Response(200, $headers, json_encode($response->body)));
        })]);
    }

    /** @return array<int, string> the distinct tokens used so far */
    public function tokens(): array
    {
        return array_values(array_unique(array_column($this->calls, 'token')));
    }

    /** @return array<int, string> the distinct versions requested so far */
    public function versions(): array
    {
        return array_values(array_unique(array_map(fn ($c) => $c['query']['version'] ?? '', $this->calls)));
    }
}
