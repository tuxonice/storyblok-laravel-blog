@extends('layouts.app')

@section('title', $title.' · '.$site['name'])

@section('content')
<div class="container-xl pt-4 pb-4">
    <nav class="breadcrumb-mono mb-3" aria-label="Breadcrumb">~/about</nav>
    <h1 class="page-title mb-4">{{ $title }}</h1>
    <article class="article page-narrow">
        {!! $body !!}
    </article>
</div>
@endsection
