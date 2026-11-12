@props(['tags' => []])
{{ collect($tags)->map(fn ($t) => '#'.$t)->implode(' ') }}
