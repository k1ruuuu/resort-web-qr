@props(['url', 'size' => 220, 'alt' => 'QR Code'])

<img
    src="{{ $url }}"
    alt="{{ $alt }}"
    width="{{ $size }}"
    height="{{ $size }}"
    class="d-block mx-auto border bg-white p-1"
    {{ $attributes }}
>
