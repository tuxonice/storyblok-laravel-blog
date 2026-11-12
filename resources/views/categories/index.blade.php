@extends('layouts.app')

@section('content')
<div class="container-xl pt-4 pb-4">
    <nav class="breadcrumb-mono mb-3" aria-label="Breadcrumb">~/categories</nav>
    <h1 class="page-title mb-4">Categories</h1>

    <div class="row g-3">
        @foreach ($categories as $category)
            <div class="col-md-6 col-lg-4">
                <a class="post-card" href="{{ route('categories.show', $category['slug']) }}">
                    <div class="meta-mono">// {{ $category['slug'] }}</div>
                    <h3>{{ $category['name'] }}</h3>
                    @if ($category['description'] !== '')
                        <p>{{ $category['description'] }}</p>
                    @endif
                </a>
            </div>
        @endforeach
    </div>
</div>
@endsection
