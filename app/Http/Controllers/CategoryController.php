<?php

namespace App\Http\Controllers;

use App\Blog\Blog;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Blog $blog)
    {
        return view('categories.index', ['categories' => $blog->categories()]);
    }

    public function show(Request $request, Blog $blog, string $slug)
    {
        $data = $blog->category($slug, max(1, $request->integer('page', 1))) ?? abort(404);

        return view('categories.show', $data + ['categories' => $blog->categories()]);
    }
}
