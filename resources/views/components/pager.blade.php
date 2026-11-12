@props(['page', 'hasNewer', 'hasOlder', 'route', 'params' => []])
<nav class="pager" aria-label="Pagination">
    @if ($hasNewer)
        <a href="{{ route($route, $page > 2 ? $params + ['page' => $page - 1] : $params) }}" rel="prev">← newer</a>
    @else
        <span class="disabled">← newer</span>
    @endif
    @if ($hasOlder)
        <a href="{{ route($route, $params + ['page' => $page + 1]) }}" rel="next">older →</a>
    @else
        <span class="disabled">older →</span>
    @endif
</nav>
