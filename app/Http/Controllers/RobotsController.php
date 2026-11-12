<?php

namespace App\Http\Controllers;

/**
 * robots.txt is generated so it can name the sitemap with the site's real host, and so
 * that anything other than production (staging, previews) asks crawlers to stay away.
 */
class RobotsController extends Controller
{
    public function __invoke()
    {
        $lines = app()->environment('production')
            ? ['User-agent: *', 'Disallow: /preview/', '', 'Sitemap: '.route('sitemap')]
            : ['User-agent: *', 'Disallow: /'];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }
}
