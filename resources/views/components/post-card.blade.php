@props(['post'])
<a class="post-card" href="{{ route('posts.show', $post['slug']) }}">
    <div class="meta-mono">{{ $post['date']->format('Y-m-d') }} · {{ $post['minutes'] }} min read</div>
    <h3>{{ $post['title'] }}</h3>
    <p>{{ $post['excerpt'] }}</p>
</a>
