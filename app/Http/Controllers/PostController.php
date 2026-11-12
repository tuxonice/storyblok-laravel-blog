<?php

namespace App\Http\Controllers;

use App\Blog\Blog;
use App\Blog\SeoMeta;

class PostController extends Controller
{
    public function show(Blog $blog, string $slug)
    {
        $post = $blog->findPost($slug) ?? abort(404);
        $site = $blog->site();

        return view('posts.show', [
            'post' => $post,
            'related' => $blog->related($slug),
            'seo' => SeoMeta::forPost($post, route('posts.show', $slug), $site['name'], $site['ogImage']),
        ]);
    }
}
