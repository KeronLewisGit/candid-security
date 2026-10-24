<x-layouts.site title="Services" description="Armed and unarmed security officers, armoured transport (cash-in-transit), mobile patrol, aviation security and lifeguards from Candid Security in Freeport and Nassau, The Bahamas.">
    <x-page-header title="Security services for Bahamian businesses, homes and events." eyebrow="Services" code="CSL" photo="nassau" lead="Five services run from our Freeport and Nassau offices. Each one can be booked on its own or combined on the same site." />

    <section class="wrap mt-16 space-y-6 md:mt-20">
        @foreach ($services as $key => $s)
            <article class="panel grid overflow-hidden md:grid-cols-[minmax(0,2fr)_minmax(0,3fr)]" data-reveal>
                <x-photo :key="$s['card'] ?? $s['photo']" :alt="candid('photos.'.($s['card'] ?? $s['photo']).'.alt')" sizes="(min-width: 768px) 40vw, 100vw" class="aspect-[16/10] md:aspect-auto md:min-h-72" />
                <div class="flex flex-col p-6 md:p-9">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="badge-id text-ink-3">Post {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="mono-label text-gold-text">{{ $s['eyebrow'] }}@if($s['since']) · since {{ $s['since'] }}@endif</span>
                    </div>
                    <h2 class="display-3 mt-4"><a href="{{ route('services.show', $key) }}" class="-my-2 inline-block py-2 hover:text-aqua-text">{{ $s['name'] }}</a></h2>
                    <p class="measure mt-3 text-ink-2">{{ $s['summary'] }}</p>
                    <ul class="mt-5 grid gap-x-6 gap-y-2 sm:grid-cols-2">
                        @foreach ($s['points'] as $point)
                            <li class="flex gap-2.5"><x-icon name="check" class="mt-1 h-4 w-4 flex-none text-aqua-text" />{{ $point }}</li>
                        @endforeach
                    </ul>
                    <div class="mt-auto flex flex-wrap gap-3 pt-7">
                        <a href="{{ route('services.show', $key) }}" class="btn btn-outline btn-sm">About {{ strtolower($s['name']) }}</a>
                        <a href="{{ $key === 'armored-transport' ? route('pickup') : route('quote', ['service' => $key]) }}" class="btn btn-primary btn-sm">{{ $key === 'armored-transport' ? 'Request a pickup' : 'Request a quote' }}<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
                    </div>
                </div>
            </article>
        @endforeach
    </section>
</x-layouts.site>
