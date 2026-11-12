<?php

namespace App\Http\Controllers;

use App\Blog\Blog;

class SitemapController extends Controller
{
    public function __invoke(Blog $blog)
    {
        return response()
            ->view('sitemap', ['entries' => $blog->sitemap()])
            ->header('Content-Type', 'application/xml; charset=utf-8');
    }
}
