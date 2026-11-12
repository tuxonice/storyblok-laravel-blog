<?php

namespace App\Http\Controllers;

use App\Blog\Blog;

class PostController extends Controller
{
    public function show(Blog $blog, string $slug)
    {
        $post = $blog->findPost($slug) ?? abort(404);

        return view('posts.show', [
            'post' => $post,
            'related' => $blog->related($slug),
        ]);
    }
}
