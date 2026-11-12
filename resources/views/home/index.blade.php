@extends('layouts.app')

@section('content')
<div class="container-xl">
    @if ($featured)
    <section class="featured row g-5 align-items-center py-5">
        <div class="col-lg-7">
            <p class="eyebrow">// featured</p>
            <h1>{{ $featured['title'] }}</h1>
            <p class="excerpt">{{ $featured['excerpt'] }}</p>
            <p class="meta-mono mb-4">{{ $featured['date']->format('Y-m-d') }} · {{ $featured['minutes'] }} min read · <x-tags :tags="$featured['tags']" /></p>
            <a class="btn btn-primary" href="{{ route('posts.show', $featured['slug']) }}">Read the post <x-arrow /></a>
        </div>
        <div class="col-lg-5">
            <figure class="code-panel">
                <div class="strip">app/Http/Controllers/PostController.php</div>
                <pre><code><span class="tk-var">$post</span> = <span class="tk-var">$storyblok</span>->stories()
    ->bySlug(
        <span class="tk-str">'posts/'</span>.<span class="tk-var">$slug</span>,
        <span class="tk-class">PostTransfer</span>::class,
    );

<span class="tk-var">$title</span> = <span class="tk-var">$post</span>?->getContent()->getTitle();</code></pre>
            </figure>
        </div>
    </section>
    @endif

    <div class="with-sidebar pb-4">
        <section aria-labelledby="latest">
            <div class="section-head">
                <h2 id="latest">Latest posts</h2>
                <a href="#">all posts</a>
            </div>
            @foreach ($posts as $post)
                <x-post-row :post="$post" />
            @endforeach
            <nav class="pager" aria-label="Pagination">
                @if ($hasNewer)
                    <a href="{{ $page > 2 ? route('home', ['page' => $page - 1]) : route('home') }}">← newer</a>
                @else
                    <span class="disabled">← newer</span>
                @endif
                @if ($hasOlder)
                    <a href="{{ route('home', ['page' => $page + 1]) }}">older →</a>
                @else
                    <span class="disabled">older →</span>
                @endif
            </nav>
        </section>

        <aside aria-label="Sidebar">
            <div class="sidebar-block">
                <p class="eyebrow">// about</p>
                <p>{{ $intro }}</p>
            </div>
            <div class="sidebar-block">
                <p class="eyebrow">// categories</p>
                <div class="chips">
                    @foreach ($categories as $category)
                        <a class="chip" href="/categories/{{ $category['slug'] }}">{{ $category['name'] }}</a>
                    @endforeach
                </div>
            </div>
            <div class="sidebar-block">
                <p class="eyebrow">// subscribe</p>
                <a class="mono" href="/feed.xml">rss feed</a>
            </div>
        </aside>
    </div>
</div>
@endsection
