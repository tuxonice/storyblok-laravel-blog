<?php

namespace App\Http\Controllers;

use App\Blog\Blog;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __invoke(Request $request, Blog $blog)
    {
        return view('home.index', $blog->home(max(1, $request->integer('page', 1))));
    }
}
