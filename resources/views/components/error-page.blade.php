@props(['code', 'title', 'text', 'showPath' => false])
<div class="container-xl error-page">
    <p class="eyebrow">// {{ $code }}</p>
    <h1 class="page-title">{{ $title }}</h1>
    <p class="page-lede">{{ $text }}</p>
    @if ($showPath)
        <p class="meta-mono">GET /{{ request()->path() === '/' ? '' : request()->path() }}</p>
    @endif
    <div class="error-actions">
        <a class="btn btn-primary" href="{{ route('home') }}">Back to the blog <x-arrow /></a>
        <a class="chip" href="{{ route('categories.index') }}">categories</a>
        <a class="chip" href="{{ route('feed') }}">rss</a>
    </div>
</div>
