@props(['src'])
@php
    $label = ucwords(str_replace(['-', '#'], ' ', pathinfo($src, PATHINFO_FILENAME)));
@endphp
<figure class="docs-screenshot mb-3">
    <a href="{{ asset('img/dokumentasi@chanaya/' . $src) }}" target="_blank" rel="noopener" title="Klik untuk memperbesar">
        <img src="{{ asset('img/dokumentasi@chanaya/' . $src) }}" alt="{{ $label }}" class="img-fluid border rounded shadow-sm bg-white w-100">
    </a>
    <figcaption class="text-muted small text-center mt-1">{{ $label }}</figcaption>
</figure>
