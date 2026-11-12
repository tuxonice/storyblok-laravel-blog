<?php

namespace App\Http\Controllers;

use App\Blog\Blog;
use Carbon\CarbonImmutable;

class FeedController extends Controller
{
    public function __invoke(Blog $blog)
    {
        $posts = $blog->latest(20);

        return response()
            ->view('feed', [
                'site' => $blog->site(),
                'posts' => $posts,
                'updated' => $posts[0]['date'] ?? CarbonImmutable::now(),
            ])
            ->header('Content-Type', 'application/rss+xml; charset=utf-8');
    }
}
