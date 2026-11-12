<?php

namespace App\Providers;

use App\Blog\Blog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
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
        $this->app->singleton(StoryblokContent::class, fn () => StoryblokContent::create(
            deliveryToken: config('storyblok.delivery_token'),
            namespace: config('storyblok.namespace'),
            defaults: new ContentOptions(Version::from(config('storyblok.version'))),
            baseUri: config('storyblok.content_base_uri'),
            cache: Cache::store(config('storyblok.cache_store')),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->singleton(Blog::class);

        View::composer(['layouts.app', 'home.index', 'posts.show'], fn ($view) => $view->with('site', app(Blog::class)->site()));
    }
}
