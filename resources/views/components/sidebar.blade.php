@props(['intro' => '', 'categories' => [], 'current' => null])
<aside aria-label="Sidebar">
    @if ($intro !== '')
        <div class="sidebar-block">
            <p class="eyebrow">// about</p>
            <p>{{ $intro }}</p>
        </div>
    @endif
    <div class="sidebar-block">
        <p class="eyebrow">// categories</p>
        <div class="chips">
            @foreach ($categories as $category)
                <a class="chip @if ($current === $category['slug']) active @endif" href="{{ route('categories.show', $category['slug']) }}" @if ($current === $category['slug']) aria-current="page" @endif>{{ $category['name'] }}</a>
            @endforeach
        </div>
    </div>
    <div class="sidebar-block">
        <p class="eyebrow">// subscribe</p>
        <a class="mono" href="{{ route('feed') }}">rss feed</a>
    </div>
</aside>
