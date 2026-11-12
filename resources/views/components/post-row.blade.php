@props(['post'])
<a class="post-row" href="{{ route('posts.show', $post['slug']) }}">
    <div class="date">{{ $post['date']->format('Y-m-d') }}</div>
    <div>
        <h3>{{ $post['title'] }}</h3>
        <p>{{ $post['excerpt'] }}</p>
        <div class="meta-mono"><x-tags :tags="$post['tags']" /> · {{ $post['minutes'] }} min read</div>
    </div>
</a>
