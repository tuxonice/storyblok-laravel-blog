<?php

namespace App\Http\Controllers;

use App\Blog\Blog;
use App\Blog\SeoMeta;

class PageController extends Controller
{
    public function about(Blog $blog)
    {
        $page = $blog->about() ?? abort(404);
        $site = $blog->site();

        return view('pages.about', $page + [
            'seo' => SeoMeta::forPage($page['title'], $page['summary'], route('about'), $site['name'], $site['ogImage']),
        ]);
    }
}
