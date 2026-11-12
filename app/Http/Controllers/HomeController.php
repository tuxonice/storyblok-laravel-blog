<?php

namespace App\Http\Controllers;

use App\Support\SampleBlog;

class HomeController extends Controller
{
    public function __invoke()
    {
        $posts = SampleBlog::posts();

        return view('home.index', [
            'site' => SampleBlog::site(),
            'categories' => SampleBlog::categories(),
            'featured' => $posts[0],
            'posts' => $posts,
        ]);
    }
}
