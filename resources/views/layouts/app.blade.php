<!doctype html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.seo')
    {{-- Apply a saved theme before first paint so there is no flash. Dark is the default. --}}
    <script>try { var t = localStorage.getItem('theme'); if (t === 'light' || t === 'dark') document.documentElement.setAttribute('data-bs-theme', t); } catch (e) {}</script>
    <link rel="preload" href="{{ Vite::asset('resources/fonts/inter-latin-400-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="alternate" type="application/rss+xml" title="{{ $site['name'] }}" href="{{ route('feed') }}">
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body>
    <a class="skip-link" href="#main">Skip to content</a>

    @if ($draftMode ?? false)
        <div class="draft-bar" role="status">
            <div class="container-xl">
                <span>// draft preview: showing unpublished content</span>
                <a href="{{ route('preview.exit') }}">exit</a>
            </div>
        </div>
    @endif

    <header class="site-header">
        <div class="container-xl site-header-inner py-2">
            <a class="wordmark" href="{{ route('home') }}">{{ $site['name'] }}<span>_</span></a>
            <nav class="nav-main" aria-label="Main">
                <ul class="nav-mono">
                    @foreach ($site['nav'] as $item)
                        <li><a href="{{ $item['href'] }}" @class(['active' => request()->is($p = ltrim($item['href'], '/') ?: '/') || ($p !== '/' && request()->is($p.'/*'))])>{{ $item['label'] }}</a></li>
                    @endforeach
                </ul>
            </nav>
            <button class="theme-toggle" type="button" data-theme-toggle aria-label="Switch to light theme">
                <svg class="icon-sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
                <svg class="icon-moon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
            </button>
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
    @if ($draftMode ?? false)
        {{-- Inside the Visual Editor's iframe only: reload on save/publish so the editor sees the change. --}}
        <script>
            if (window.self !== window.top) {
                var bridge = document.createElement('script');
                bridge.src = 'https://app.storyblok.com/f/storyblok-v2-latest.js';
                bridge.onload = function () {
                    new window.StoryblokBridge().on(['change', 'published'], function () { window.location.reload(); });
                };
                document.head.appendChild(bridge);
            }
        </script>
    @endif
</body>
</html>
