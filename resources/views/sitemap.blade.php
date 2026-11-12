{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($entries as $entry)
    <url>
        <loc>{{ url($entry['path']) }}</loc>
@if ($entry['lastmod'])
        <lastmod>{{ \Carbon\CarbonImmutable::parse($entry['lastmod'])->toAtomString() }}</lastmod>
@endif
    </url>
@endforeach
</urlset>
