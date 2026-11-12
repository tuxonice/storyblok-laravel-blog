@props(['author', 'size' => 'sm'])
@php
    // sm 36px, lg 56px, xl 96px. Photos are requested at twice the size for sharp high-density screens.
    $px = ['sm' => 36, 'lg' => 56, 'xl' => 96][$size] ?? 36;
    $src = \App\Blog\Image::resize($author['avatar'] ?? null, $px * 2, $px * 2);
@endphp
@if ($src)
    <img class="avatar {{ $size }}" src="{{ $src }}" width="{{ $px }}" height="{{ $px }}" alt="" loading="lazy">
@else
    <span class="avatar {{ $size }}" aria-hidden="true">{{ $author['initials'] }}</span>
@endif
