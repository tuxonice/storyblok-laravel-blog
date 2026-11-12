<?php

namespace App\Http\Controllers;

use App\Blog\Blog;
use App\Blog\SeoMeta;
use Illuminate\Http\Request;

class AuthorController extends Controller
{
    public function show(Request $request, Blog $blog, string $slug)
    {
        $data = $blog->author($slug, max(1, $request->integer('page', 1))) ?? abort(404);
        $site = $blog->site();

        $canonical = route('authors.show', ['slug' => $slug] + ($data['page'] > 1 ? ['page' => $data['page']] : []));

        return view('authors.show', $data + [
            'categories' => $blog->categories(),
            'seo' => SeoMeta::forAuthor($data['author'], $canonical, $site['name'], $site['ogImage']),
        ]);
    }
}
