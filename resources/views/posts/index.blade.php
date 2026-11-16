@extends('layouts.app')

@section('content')
<div class="container-xl pt-4">
    <nav class="breadcrumb-mono mb-3" aria-label="Breadcrumb">~/posts</nav>

    <div class="with-sidebar pb-4">
        <section aria-labelledby="posts-title">
            <h1 class="page-title" id="posts-title">All posts</h1>
            <p class="meta-mono mb-4">{{ $total }} {{ Str::plural('post', $total) }}, newest first</p>

            @forelse ($posts as $post)
                <x-post-row :post="$post" />
            @empty
                <p class="text-muted-tb hairline-top py-4 mb-0">No posts yet.</p>
            @endforelse

            @if ($hasNewer || $hasOlder)
                <x-pager :page="$page" :has-newer="$hasNewer" :has-older="$hasOlder" route="posts.index" />
            @endif
        </section>

        <x-sidebar :intro="$intro" :categories="$categories" />
    </div>
</div>
@endsection
