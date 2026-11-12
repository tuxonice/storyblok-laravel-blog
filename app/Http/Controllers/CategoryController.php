<?php

namespace App\Http\Controllers;

use App\Blog\Blog;
use App\Blog\SeoMeta;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Blog $blog)
    {
        $site = $blog->site();

        return view('categories.index', [
            'categories' => $blog->categories(),
            'seo' => SeoMeta::forPage('Categories', 'Browse the posts by category.', route('categories.index'), $site['name'], $site['ogImage']),
        ]);
    }

    public function show(Request $request, Blog $blog, string $slug)
    {
        $data = $blog->category($slug, max(1, $request->integer('page', 1))) ?? abort(404);
        $site = $blog->site();
        $category = $data['category'];

        $canonical = route('categories.show', ['slug' => $slug] + ($data['page'] > 1 ? ['page' => $data['page']] : []));

        return view('categories.show', $data + [
            'categories' => $blog->categories(),
            'seo' => SeoMeta::forPage($category['name'], $category['description'] ?: "Posts about {$category['name']}.", $canonical, $site['name'], $site['ogImage']),
        ]);
    }
}
