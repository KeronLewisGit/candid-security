@props(['key', 'alt', 'sizes' => '100vw', 'plate' => null, 'eager' => false, 'imgClass' => ''])
@php($p = photo($key))
<figure {{ $attributes->merge(['class' => 'photo']) }}>
    <img src="{{ $p['src'] }}" srcset="{{ $p['srcset'] }}" sizes="{{ $sizes }}" width="{{ $p['width'] }}" height="{{ $p['height'] }}" alt="{{ $alt }}" class="{{ $imgClass }}" @if($eager) fetchpriority="high" @else loading="lazy" decoding="async" @endif>
    @if ($plate)<figcaption class="plate">{!! $plate !!}</figcaption>@endif
</figure>
