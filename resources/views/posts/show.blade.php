@extends('layouts.app')

@section('title', $post['title'].' · '.$site['name'])

@section('content')
<div class="container-xl pt-4">
    <nav class="breadcrumb-mono mb-3" aria-label="Breadcrumb">
        <a href="{{ route('home') }}">~/posts</a> / {{ $post['slug'] }}
    </nav>
    <h1 class="post-title mb-3">{{ $post['title'] }}</h1>
    <div class="meta-row meta-mono mb-4">
        <span class="avatar" aria-hidden="true">{{ $post['author']['initials'] }}</span>
        <span>{{ $post['author']['name'] }}</span>
        <span>{{ $post['date']->format('Y-m-d') }}</span>
        <span>{{ $post['minutes'] }} min read</span>
        <span><x-tags :tags="$post['tags']" /></span>
    </div>
    <div class="cover-placeholder mb-5" role="img" aria-label="Cover image placeholder">cover image</div>

    <div class="article-layout">
        <article class="article">
            @foreach ($post['body'] as $block)
                @switch($block['type'])
                    @case('paragraph')
                        <p>{{ $block['text'] }}</p>
                        @break
                    @case('heading')
                        <h2 id="{{ $block['id'] }}">{{ $block['text'] }}</h2>
                        @break
                    @case('code')
                        <x-blocks.code_block :filename="$block['filename']" :html="$block['html']" />
                        @break
                    @case('callout')
                        <x-blocks.callout :label="$block['label']" :text="$block['text']" />
                        @break
                    @case('quote')
                        <x-blocks.pull_quote :text="$block['text']" />
                        @break
                    @case('figure')
                        <x-blocks.image_figure :caption="$block['caption']" />
                        @break
                @endswitch
            @endforeach
        </article>

        <aside class="toc" aria-label="On this page">
            <div class="label">// on this page</div>
            <ol>
                @foreach ($headings as $h)
                    <li><a href="#{{ $h['id'] }}">{{ $h['text'] }}</a></li>
                @endforeach
            </ol>
        </aside>
    </div>

    <div class="author-card my-5">
        <span class="avatar lg" aria-hidden="true">{{ $post['author']['initials'] }}</span>
        <div>
            <div class="name">{{ $post['author']['name'] }}</div>
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
