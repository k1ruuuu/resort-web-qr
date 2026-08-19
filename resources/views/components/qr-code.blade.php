@props(['url', 'size' => 220, 'alt' => 'QR Code', 'square' => false])

@php
    // template PNG is 1414x2000 (portrait); plain QR SVG is square
    $height = $square ? $size : (int) round($size * 1.4125);
@endphp

<img
    src="{{ $url }}"
    alt="{{ $alt }}"
    width="{{ $size }}"
    height="{{ $height }}"
    class="img-fluid d-block mx-auto border bg-white p-1"
    {{ $attributes }}
>
