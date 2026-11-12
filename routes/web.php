<?php

use App\Blog\DraftMode;
use App\Http\Controllers\AuthorController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\StoryblokWebhookController;
use App\Http\Middleware\DetectDraftMode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// HTML pages: draft mode (Storyblok Visual Editor) can apply here, never to the feed, sitemap or webhook.
Route::middleware(DetectDraftMode::class)->group(function () {
    Route::get('/', HomeController::class)->name('home');
    Route::get('/posts/{slug}', [PostController::class, 'show'])->name('posts.show');
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('/categories/{slug}', [CategoryController::class, 'show'])->name('categories.show');
    Route::get('/authors/{slug}', [AuthorController::class, 'show'])->name('authors.show');
    Route::get('/about', [PageController::class, 'about'])->name('about');

    // The Visual Editor previews a story at its slug: send the stories without a page of their own to the home page,
    // keeping the editor's query string so draft mode survives the redirect.
    Route::get('/home', fn (Request $request) => redirect()->route('home', $request->query()));
    Route::get('/settings', fn (Request $request) => redirect()->route('home', $request->query()));
});

Route::get('/preview/exit', fn () => redirect()->route('home')->withCookie(app(DraftMode::class)->forgetCookie()))->name('preview.exit');

Route::get('/robots.txt', RobotsController::class)->name('robots');
Route::get('/feed.xml', FeedController::class)->name('feed');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::post('/webhooks/storyblok', StoryblokWebhookController::class)->middleware('throttle:60,1')->name('webhooks.storyblok');
