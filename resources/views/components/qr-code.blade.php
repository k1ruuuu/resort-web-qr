@props(['url', 'size' => 220, 'alt' => 'QR Code'])

<img
    src="{{ $url }}"
    alt="{{ $alt }}"
    width="{{ $size }}"
    height="{{ (int) round($size * 1.4125) }}"
    class="img-fluid d-block mx-auto border bg-white p-1"
    {{ $attributes }}
>
