@props(['caption' => '', 'src' => null])
<figure>
    @if ($src)
        <img class="img-fluid rounded" src="{{ $src }}" alt="{{ $caption }}">
    @else
        <div class="cover-placeholder">image</div>
    @endif
    @if ($caption)
        <figcaption>{{ $caption }}</figcaption>
    @endif
</figure>
