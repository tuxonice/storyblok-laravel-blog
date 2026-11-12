<?php

namespace App\Http\Controllers;

use App\Support\SampleBlog;

class PostController extends Controller
{
    public function show(string $slug)
    {
        $post = SampleBlog::post($slug) ?? abort(404);

        return view('posts.show', [
            'site' => SampleBlog::site(),
            'post' => $post,
            'related' => array_slice(array_filter(SampleBlog::posts(), fn ($p) => $p['slug'] !== $slug), 0, 3),
            'headings' => array_values(array_filter($post['body'], fn ($b) => $b['type'] === 'heading')),
        ]);
    }
}
