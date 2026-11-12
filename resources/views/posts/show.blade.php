@extends('layouts.app')

@section('content')
<div class="container-xl pt-4">
    <nav class="breadcrumb-mono mb-3" aria-label="Breadcrumb">
        <a href="{{ route('home') }}">~/posts</a> / {{ $post['slug'] }}
    </nav>
    <h1 class="post-title mb-3">{{ $post['title'] }}</h1>
    <div class="meta-row meta-mono mb-4">
        <x-avatar :author="$post['author']" />
        @if ($post['author']['slug'])
            <a href="{{ route('authors.show', $post['author']['slug']) }}">{{ $post['author']['name'] }}</a>
        @else
            <span>{{ $post['author']['name'] }}</span>
        @endif
        <span>{{ $post['date']->format('Y-m-d') }}</span>
        <span>{{ $post['minutes'] }} min read</span>
        <span><x-tags :tags="$post['tags']" /></span>
    </div>
    @if ($post['cover'])
        <img class="cover-image mb-5" src="{{ $post['cover'] }}" alt="">
    @else
        <div class="cover-placeholder mb-5" role="img" aria-label="Cover image placeholder">cover image</div>
    @endif

    <div class="article-layout">
        <article class="article">
            {!! $post['body'] !!}
        </article>

        <aside class="toc" aria-label="On this page">
            <div class="label">// on this page</div>
            <ol>
                @foreach ($post['headings'] as $h)
                    <li><a href="#{{ $h['id'] }}">{{ $h['text'] }}</a></li>
                @endforeach
            </ol>
        </aside>
    </div>

    <div class="author-card my-5">
        <x-avatar :author="$post['author']" size="lg" />
        <div>
            <div class="name">
                @if ($post['author']['slug'])
                    <a href="{{ route('authors.show', $post['author']['slug']) }}">{{ $post['author']['name'] }}</a>
                @else
                    {{ $post['author']['name'] }}
                @endif
            </div>
            @if ($post['author']['role'] !== '')
                <div class="meta-mono">{{ $post['author']['role'] }}</div>
            @endif
            <p>{{ $post['author']['bio'] }}</p>
        </div>
    </div>

    <section aria-labelledby="keep-reading" class="pb-4">
        <p class="eyebrow" id="keep-reading">// keep reading</p>
        <div class="row g-3">
            @foreach ($related as $r)
                <div class="col-md-4"><x-post-card :post="$r" /></div>
            @endforeach
        </div>
    </section>
</div>
@endsection
