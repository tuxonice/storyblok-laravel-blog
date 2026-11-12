<?php

namespace App\Providers;

use App\Blog\Blog;
use App\Blog\DraftMode;
use App\Blog\ErrorPage;
use App\Blog\SeoMeta;
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
            ['layouts.app', 'home.index', 'posts.show', 'categories.index', 'categories.show', 'authors.show', 'pages.about'],
            function ($view) {
                // An error page supplies its own (failure-tolerant) site settings.
                $site = $view->getData()['site'] ?? app(Blog::class)->site();
                $view->with('site', $site)->with('draftMode', app(DraftMode::class)->active());

                // Pages that do not describe themselves still get a title and a canonical URL.
                if (! isset($view->getData()['seo'])) {
                    $view->with('seo', SeoMeta::fallback($site['name'], url()->current(), $site['ogImage']));
                }
            },
        );

        // Error pages. They must work when Storyblok is the thing that is broken.
        View::composer(['errors::404', 'errors::4xx', 'errors::5xx'], function ($view) {
            $error = ErrorPage::for($view->getData()['exception'] ?? null);
            $view->with('error', $error);

            // Client errors use the full layout; server errors are a standalone page.
            if ($error['code'] < 500) {
                $site = app(Blog::class)->siteOrFallback();
                $view->with('site', $site)->with('seo', SeoMeta::forError($error['title'], $site['name'], url()->current()));
            }
        });
    }
}
