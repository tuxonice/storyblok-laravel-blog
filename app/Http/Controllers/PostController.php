<?php

namespace App\Http\Controllers;

use App\Blog\Blog;
use App\Blog\SeoMeta;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index(Request $request, Blog $blog)
    {
        $data = $blog->posts(max(1, $request->integer('page', 1))) ?? abort(404);
        $site = $blog->site();

        $canonical = route('posts.index', $data['page'] > 1 ? ['page' => $data['page']] : []);

        return view('posts.index', $data + [
            'seo' => SeoMeta::forPage('All posts', 'Every post on the blog, newest first.', $canonical, $site['name'], $site['ogImage']),
        ]);
    }

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
