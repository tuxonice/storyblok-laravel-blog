@props(['tags' => []])
{!! collect($tags)->map(fn ($tag) => '<span class="tag">#'.e($tag).'</span>')->implode(' ') !!}
