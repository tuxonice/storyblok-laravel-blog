<?php

namespace Tests\Support;

use App\Blog\Blog;
use GuzzleHttp\ClientInterface;
use Illuminate\Support\Facades\Cache;
use Tlab\StoryblokTransfers\Content\StoryblokContent;

trait FakesStoryblok
{
    /**
     * Every request starts with fresh request-scoped services, like a real request
     * (or a new Octane request). Without this, one request's Blog or draft state
     * would silently carry over to the next call in the same test.
     */
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $this->app->forgetScopedInstances();

        return parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
    }

    /** @param array<string, array<string, mixed>> $contentOverrides full_slug => fields merged into that story's content */
    protected function fakeStoryblok(array $contentOverrides = []): void
    {
        $client = new FakeContentClient($contentOverrides);

        // Scoped, like the real binding: rebuilt for every request, backed by the same in-memory client.
        $this->app->scoped(StoryblokContent::class, fn () => StoryblokContent::usingClient($client, config('storyblok.namespace')));
        $this->app->forgetInstance(StoryblokContent::class);
        $this->app->forgetInstance(Blog::class);
    }

    /**
     * Fakes only the network: the app's real client, cache and token logic run on
     * top of an in-memory Content Delivery API.
     *
     * @param  array<string, array<string, mixed>>  $contentOverrides
     */
    protected function fakeStoryblokNetwork(array $contentOverrides = []): FakeGuzzle
    {
        config(['cache.stores.storyblok' => ['driver' => 'array']]);
        Cache::purge('storyblok');

        $guzzle = new FakeGuzzle(new FakeContentClient($contentOverrides));
        $this->app->instance(ClientInterface::class, $guzzle->client());
        $this->app->forgetInstance(StoryblokContent::class);
        $this->app->forgetInstance(Blog::class);

        return $guzzle;
    }
}
