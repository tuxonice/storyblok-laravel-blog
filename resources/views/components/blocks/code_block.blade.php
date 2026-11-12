@props(['filename' => '', 'html' => ''])
<figure class="code-panel my-4">
    <div class="strip">{{ $filename }}</div>
    <pre><code>{!! $html !!}</code></pre>
</figure>
