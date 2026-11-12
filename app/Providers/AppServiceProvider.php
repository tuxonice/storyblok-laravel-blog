<?php

namespace App\Providers;

use App\Blog\Blog;
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
        // A singleton: the package caches its reflection work per instance.
        $this->app->singleton(StoryblokContent::class, function () {
            $client = new CachingContentClient(
                new StoryblokContentClient(
                    config('storyblok.delivery_token'),
                    baseUri: config('storyblok.content_base_uri') ?? StoryblokContentClient::DEFAULT_BASE_URI,
                ),
                Cache::store(config('storyblok.cache_store')),
                freshFor: config('storyblok.cache_fresh_for'),
                keepStaleFor: config('storyblok.cache_keep_stale_for'),
                logger: Log::getLogger(), // a warning each time an old copy is served, so outages show up
            );

            return StoryblokContent::usingClient(
                $client,
                config('storyblok.namespace'),
                new ContentOptions(Version::from(config('storyblok.version'))),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->singleton(Blog::class);

        View::composer(['layouts.app', 'home.index', 'posts.show', 'categories.index', 'categories.show', 'pages.about'], fn ($view) => $view->with('site', app(Blog::class)->site()));
    }
}
