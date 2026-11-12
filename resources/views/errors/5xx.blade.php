{{--
    Server errors (500, 503, ...). Deliberately standalone: no layout, no Vite assets and no
    Storyblok, so this page cannot fail for the same reason the site just did. The colors are the
    approved design tokens: dark by default, light only when the visitor chose it with the theme toggle
    (the same rule as the rest of the site, which does not follow the operating system setting).
--}}
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $error['title'] }} · {{ config('app.name') }}</title>
    <script>try { var t = localStorage.getItem('theme'); if (t === 'light' || t === 'dark') document.documentElement.setAttribute('data-theme', t); } catch (e) {}</script>
    <style>
        :root { --bg: #0e1116; --surface: #151a21; --border: #262d37; --text: #e8ecf1; --muted: #9aa4b2; --accent: #f5a524; --ink: #0e1116; color-scheme: dark; }
        :root[data-theme="light"] { --bg: #ffffff; --surface: #f6f8fa; --border: #dfe3e9; --text: #0e1116; --muted: #566170; --accent: #8f5700; color-scheme: light; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: flex; flex-direction: column; background: var(--bg); color: var(--text); font-family: Inter, system-ui, -apple-system, "Segoe UI", sans-serif; line-height: 1.5; }
        .mono { font-family: "JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }
        header { border-bottom: 1px solid var(--border); }
        .wrap { width: 100%; max-width: 1140px; margin: 0 auto; padding: 0 24px; }
        .wordmark { display: inline-flex; align-items: center; min-height: 44px; padding: 8px 0; font-size: 1.125rem; font-weight: 500; color: var(--text); text-decoration: none; }
        .wordmark span { color: var(--accent); }
        main { flex: 1; }
        .eyebrow { margin: 0 0 1rem; font-size: .875rem; color: var(--accent); }
        h1 { margin: 0 0 1rem; font-size: clamp(2rem, 6vw, 3rem); line-height: 1.1; font-weight: 700; letter-spacing: -.02em; }
        p.lede { margin: 0 0 1.5rem; max-width: 36rem; font-size: 1.125rem; color: var(--muted); }
        .actions { display: flex; flex-wrap: wrap; gap: .75rem; margin-top: 2rem; }
        .btn, .chip { display: inline-flex; align-items: center; min-height: 44px; padding: 0 1.25rem; border-radius: 8px; font-size: .875rem; text-decoration: none; }
        .btn { background: var(--accent); color: var(--ink); font-weight: 600; font-family: inherit; }
        :root[data-theme="light"] .btn { background: #f5a524; }
        .chip { border: 1px solid var(--border); color: var(--text); }
        a:hover { opacity: .75; }
        a:focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }
        .page { padding-top: 4rem; padding-bottom: 5rem; }
    </style>
</head>
<body>
    <header>
        <div class="wrap"><a class="wordmark mono" href="{{ route('home') }}">{{ config('app.name') }}<span>_</span></a></div>
    </header>
    <main>
        <div class="wrap page">
            <p class="eyebrow mono">// {{ $error['code'] }}</p>
            <h1>{{ $error['title'] }}</h1>
            <p class="lede">{{ $error['text'] }}</p>
            <div class="actions">
                <a class="btn" href="{{ route('home') }}">Back to the blog</a>
                <a class="chip mono" href="{{ url()->current() }}">try again</a>
            </div>
        </div>
    </main>
</body>
</html>
