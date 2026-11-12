@extends('layouts.app')

@section('content')
<div class="container-xl pt-4">
    <nav class="breadcrumb-mono mb-3" aria-label="Breadcrumb">
        <span>~/authors</span> / {{ $author['slug'] }}
    </nav>

    <div class="with-sidebar pb-4">
        <section aria-labelledby="author-name">
            <header class="author-head">
                <x-avatar :author="$author" size="xl" />
                <div>
                    <p class="eyebrow">// author</p>
                    <h1 class="page-title" id="author-name">{{ $author['name'] }}</h1>
                    @if ($author['role'] !== '')
                        <p class="meta-mono mb-0">{{ $author['role'] }}</p>
                    @endif
                </div>
            </header>

            @if ($author['bio'] !== '')
                <p class="page-lede">{{ $author['bio'] }}</p>
            @endif

            @if ($author['website'] || $author['github'])
                <div class="author-links">
                    @if ($author['website'])
                        <a class="chip" href="{{ $author['website']['url'] }}" rel="me noopener">{{ $author['website']['label'] }}</a>
                    @endif
                    @if ($author['github'])
                        <a class="chip" href="{{ $author['github']['url'] }}" rel="me noopener">{{ $author['github']['label'] }}</a>
                    @endif
                </div>
            @endif

            <div class="section-head mt-5">
                <h2>{{ $total }} {{ Str::plural('post', $total) }}</h2>
            </div>

            @forelse ($posts as $post)
                <x-post-row :post="$post" />
            @empty
                <p class="text-muted-tb hairline-top py-4 mb-0">No posts by {{ $author['name'] }} yet.</p>
            @endforelse

            @if ($hasNewer || $hasOlder)
                <x-pager :page="$page" :has-newer="$hasNewer" :has-older="$hasOlder" route="authors.show" :params="['slug' => $author['slug']]" />
            @endif
        </section>

        <x-sidebar :categories="$categories" />
    </div>
</div>
@endsection
