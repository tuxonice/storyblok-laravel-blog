<title>{{ $seo->title }}</title>
@if ($seo->description !== '')
    <meta name="description" content="{{ $seo->description }}">
@endif
@unless ($seo->noindex)
    <link rel="canonical" href="{{ $seo->canonical }}">
@endunless
@if ($seo->noindex || ($draftMode ?? false))
    <meta name="robots" content="noindex, nofollow">
@endif

<meta property="og:site_name" content="{{ $seo->siteName }}">
<meta property="og:type" content="{{ $seo->type }}">
<meta property="og:title" content="{{ $seo->title }}">
@if ($seo->description !== '')
    <meta property="og:description" content="{{ $seo->description }}">
@endif
@unless ($seo->noindex)
    <meta property="og:url" content="{{ $seo->canonical }}">
@endunless
@if ($seo->image)
    <meta property="og:image" content="{{ $seo->image }}">
    <meta property="og:image:alt" content="{{ $seo->imageAlt }}">
@endif
@if ($seo->publishedTime)
    <meta property="article:published_time" content="{{ $seo->publishedTime }}">
@endif
@if ($seo->author)
    <meta property="article:author" content="{{ $seo->author }}">
@endif
@foreach ($seo->tags as $tag)
    <meta property="article:tag" content="{{ $tag }}">
@endforeach

<meta name="twitter:card" content="{{ $seo->twitterCard() }}">
<meta name="twitter:title" content="{{ $seo->title }}">
@if ($seo->description !== '')
    <meta name="twitter:description" content="{{ $seo->description }}">
@endif
@if ($seo->image)
    <meta name="twitter:image" content="{{ $seo->image }}">
    <meta name="twitter:image:alt" content="{{ $seo->imageAlt }}">
@endif
@if ($json = $seo->jsonLdScript())
    <script type="application/ld+json">{!! $json !!}</script>
@endif
