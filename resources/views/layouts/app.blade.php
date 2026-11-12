<!doctype html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', $site['name'])</title>
    <link rel="preload" href="{{ Vite::asset('resources/fonts/inter-latin-400-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body>
    <a class="skip-link" href="#main">Skip to content</a>

    <header class="site-header">
        <div class="container-xl d-flex flex-wrap align-items-center justify-content-between gap-2 py-2">
            <a class="wordmark" href="{{ route('home') }}">{{ $site['name'] }}<span>_</span></a>
            <div class="d-flex align-items-center gap-3">
                <nav aria-label="Main">
                    <ul class="nav-mono">
                        @foreach ($site['nav'] as $item)
                            <li><a href="{{ $item['href'] }}" @class(['active' => request()->is(ltrim($item['href'], '/') ?: '/')])>{{ $item['label'] }}</a></li>
                        @endforeach
                    </ul>
                </nav>
                <button class="theme-toggle" type="button" disabled title="Light theme not designed yet" aria-label="Toggle theme">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
                </button>
            </div>
        </div>
    </header>

    <main id="main">
        @yield('content')
    </main>

    <footer class="site-footer mt-5">
        <div class="container-xl d-flex flex-wrap align-items-center justify-content-between gap-2 py-3">
            <span>Built with Storyblok, Laravel and tuxonice/storyblok-transfers</span>
            <nav aria-label="Footer" class="d-flex gap-3">
                @foreach ($site['footer'] as $item)
                    <a href="{{ $item['href'] }}" @if (str_starts_with($item['href'], 'http')) rel="noopener" @endif>{{ $item['label'] }}</a>
                @endforeach
            </nav>
        </div>
    </footer>
</body>
</html>
