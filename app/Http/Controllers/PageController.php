<?php

namespace App\Http\Controllers;

use App\Blog\Blog;

class PageController extends Controller
{
    public function about(Blog $blog)
    {
        return view('pages.about', $blog->about() ?? abort(404));
    }
}
