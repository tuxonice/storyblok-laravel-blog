<?php

namespace App\Providers;

use App\Blog\Blog;
use App\Blog\DraftMode;
use GuzzleHttp\ClientInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Tlab\StoryblokTransfers\Client\CachingContentClient;
use Tlab\StoryblokTransfers\Client\StoryblokContentClient;
use Tlab\StoryblokTransfers\Content\ContentOptions;
use Tlab\StoryblokTransfers\Content\StoryblokContent;
use Tlab\StoryblokTransfers\Content\Version;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Scoped, not a singleton: it is built once per request (the package caches its
        // reflection work per instance) and rebuilt for the next, so a draft-mode request
        // can never leave a preview token or draft version behind in a long-lived worker.
        $this->app->scoped(StoryblokContent::class, function ($app) {
            $draft = $app->make(DraftMode::class)->active();

            $client = new CachingContentClient(
                new StoryblokContentClient(
                    // Drafts need the preview token; published content uses the public delivery token.
                    $draft ? config('storyblok.preview_token') : config('storyblok.delivery_token'),
                    // A seam for tests: bind a Guzzle client to keep the real client and cache, minus the network.
                    $app->bound(ClientInterface::class) ? $app->make(ClientInterface::class) : null,
                    config('storyblok.content_base_uri') ?? StoryblokContentClient::DEFAULT_BASE_URI,
                ),
                Cache::store(config('storyblok.cache_store')),
                freshFor: config('storyblok.cache_fresh_for'),
                keepStaleFor: config('storyblok.cache_keep_stale_for'),
                logger: Log::getLogger(), // a warning each time an old copy is served, so outages show up
            );

            return StoryblokContent::usingClient(
                $client,
                config('storyblok.namespace'),
                new ContentOptions($app->make(DraftMode::class)->version()),
            );
        });

        $this->app->scoped(DraftMode::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->scoped(Blog::class);

        View::composer(
            ['layouts.app', 'home.index', 'posts.show', 'categories.index', 'categories.show', 'pages.about'],
            fn ($view) => $view->with('site', app(Blog::class)->site())->with('draftMode', app(DraftMode::class)->active()),
        );
    }
}
