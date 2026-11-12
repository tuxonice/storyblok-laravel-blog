<?php

namespace App\Http\Controllers;

use App\Blog\Blog;
use App\Blog\SeoMeta;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __invoke(Request $request, Blog $blog)
    {
        $data = $blog->home(max(1, $request->integer('page', 1)));
        $site = $blog->site();

        // Page 1 is the home page itself; later pages are distinct URLs.
        $canonical = route('home', $data['page'] > 1 ? ['page' => $data['page']] : []);

        return view('home.index', $data + [
            'seo' => SeoMeta::forPage(null, $data['intro'], $canonical, $site['name'], $site['ogImage']),
        ]);
    }
}
