<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/posts/{slug}', [PostController::class, 'show'])->name('posts.show');
Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
Route::get('/categories/{slug}', [CategoryController::class, 'show'])->name('categories.show');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/feed.xml', FeedController::class)->name('feed');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
