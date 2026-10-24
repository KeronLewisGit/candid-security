@props(['title', 'lead' => null, 'eyebrow' => null, 'code' => null, 'photo' => null])
{{--
    The one hero band every inner page uses, so titles start at the same height
    and left edge site-wide. Charcoal, with an optional photo behind a scrim, and
    the patrol-route line along the bottom edge. Extra content goes in the slot.
--}}
<section {{ $attributes->merge(['class' => 'relative isolate overflow-hidden bg-char-900 text-white on-dark']) }}>
    @if ($photo)
        <div class="absolute inset-0 -z-10">
            <x-photo :key="$photo" alt="" :eager="true" sizes="100vw" class="hero-photo h-full w-full" />
            <div class="hero-scrim absolute inset-0"></div>
        </div>
    @endif
    <div class="wrap pb-12 pt-10 md:pb-20 md:pt-20">
        @if ($eyebrow)<x-eyebrow :code="$code">{{ $eyebrow }}</x-eyebrow>@endif
        <h1 class="display-2 mt-4 max-w-4xl">{{ $title }}</h1>
        @if ($lead)<p class="lead mt-4 text-white/85 md:mt-5">{{ $lead }}</p>@endif
        {{ $slot }}
    </div>
    <div class="absolute inset-x-0 bottom-0 flex items-center" aria-hidden="true">
        <div class="wrap flex items-center gap-0"><span class="checkpoint -mb-px h-3.5 w-3.5 border-2"></span><span class="route flex-1"></span></div>
    </div>
</section>
