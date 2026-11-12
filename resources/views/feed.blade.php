{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/">
    <channel>
        <title>{{ $site['name'] }}</title>
        <link>{{ route('home') }}</link>
        <description>{{ $site['name'] }}: latest posts</description>
        <language>en</language>
        <lastBuildDate>{{ $updated->toRfc7231String() }}</lastBuildDate>
        <atom:link href="{{ route('feed') }}" rel="self" type="application/rss+xml" />
@foreach ($posts as $post)
        <item>
            <title>{{ $post['title'] }}</title>
            <link>{{ route('posts.show', $post['slug']) }}</link>
            <guid isPermaLink="true">{{ route('posts.show', $post['slug']) }}</guid>
            <pubDate>{{ $post['date']->toRfc7231String() }}</pubDate>
            <description>{{ $post['excerpt'] }}</description>
@foreach ($post['categories'] as $category)
            <category>{{ $category['name'] }}</category>
@endforeach
            <content:encoded><![CDATA[{!! str_replace(']]>', ']]]]><![CDATA[>', str_replace(['href="/', 'src="/'], ['href="'.url('/').'/', 'src="'.url('/').'/'], $post['body'])) !!}]]></content:encoded>
        </item>
@endforeach
    </channel>
</rss>
