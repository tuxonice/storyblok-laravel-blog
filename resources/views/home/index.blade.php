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
                <a href="{{ route('home') }}">all posts</a>
            </div>
            @foreach ($posts as $post)
                <x-post-row :post="$post" />
            @endforeach
            <x-pager :page="$page" :has-newer="$hasNewer" :has-older="$hasOlder" route="home" />
        </section>

        <x-sidebar :intro="$intro" :categories="$categories" />
    </div>
</div>
@endsection
