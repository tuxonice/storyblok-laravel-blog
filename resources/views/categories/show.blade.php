@extends('layouts.app')

@section('content')
<div class="container-xl pt-4">
    <nav class="breadcrumb-mono mb-3" aria-label="Breadcrumb">
        <a href="{{ route('categories.index') }}">~/categories</a> / {{ $category['slug'] }}
    </nav>

    <div class="with-sidebar pb-4">
        <section aria-labelledby="category-title">
            <p class="eyebrow">// category</p>
            <h1 class="page-title" id="category-title">{{ $category['name'] }}</h1>
            @if ($category['description'] !== '')
                <p class="page-lede">{{ $category['description'] }}</p>
            @endif
            <p class="meta-mono mb-4">{{ $total }} {{ Str::plural('post', $total) }}</p>

            @forelse ($posts as $post)
                <x-post-row :post="$post" />
            @empty
                <p class="text-muted-tb hairline-top py-4 mb-0">No posts in this category yet.</p>
            @endforelse

            @if ($hasNewer || $hasOlder)
                <x-pager :page="$page" :has-newer="$hasNewer" :has-older="$hasOlder" route="categories.show" :params="['slug' => $category['slug']]" />
            @endif
        </section>

        <x-sidebar :categories="$categories" :current="$category['slug']" />
    </div>
</div>
@endsection
