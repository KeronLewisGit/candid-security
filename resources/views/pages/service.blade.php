@php($isTransport = $key === 'armored-transport')
<x-layouts.site :title="$service['name']" :description="$service['summary']" :og-image="$service['photo']" :json-ld="[[
    '@context' => 'https://schema.org', '@type' => 'Service', 'name' => $service['name'], 'description' => $service['summary'],
    'provider' => ['@type' => 'Organization', 'name' => candid('name')], 'areaServed' => 'The Bahamas',
]]">
    <x-page-header :title="$service['name']" :eyebrow="$service['eyebrow']" :code="$service['since'] ? 'SINCE '.$service['since'] : 'CSL'" :photo="$service['photo']" :lead="$service['summary']">
        <nav aria-label="Breadcrumb" class="mt-6 text-sm text-white/70"><a href="{{ route('services') }}" class="inline-flex min-h-11 items-center underline-offset-4 hover:underline">Services</a> <span aria-hidden="true">/</span> <span aria-current="page">{{ $service['name'] }}</span></nav>
    </x-page-header>

    <div class="wrap mt-16 grid gap-12 md:mt-20 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <div>
            <div class="prose-copy measure text-lg text-ink-2">
                @foreach ($service['body'] as $paragraph)<p>{{ $paragraph }}</p>@endforeach
            </div>
            <h2 class="display-3 mt-12">What's included</h2>
            <ul class="mt-6 grid gap-4 sm:grid-cols-2">
                @foreach ($service['points'] as $point)
                    <li class="panel flex items-start gap-3 p-5"><span class="chev mt-1" aria-hidden="true"></span><span class="font-medium">{{ $point }}</span></li>
                @endforeach
            </ul>
            @if ($service['body_photo'])
                @php($meta = candid('photos.'.$service['body_photo']))
                <figure class="mt-12 max-w-2xl">
                    <x-photo :key="$service['body_photo']" :alt="$meta['alt']" sizes="(min-width: 768px) 640px, 100vw" class="aspect-[4/3] rounded-xl" />
                    <figcaption class="mt-2 text-sm text-ink-3">{{ $meta['ours'] ? 'Candid officers on duty (photo from candidbahamas.com).' : $meta['alt'].'.' }}</figcaption>
                </figure>
            @endif
            <h2 class="display-3 mt-12">Who it's for</h2>
            <ul class="mt-5 flex flex-wrap gap-2">
                @foreach ($service['industries'] as $industry)<li class="rounded-full border border-line-strong px-4 py-2 text-[0.9375rem] font-medium">{{ $industry }}</li>@endforeach
            </ul>
            <h2 class="display-3 mt-12">Where</h2>
            <p class="measure mt-3 text-ink-2">Run from our offices in Freeport (Grand Bahama) and Nassau (New Providence), with officers deployed to client sites in Abaco and Bimini. <a href="{{ route('locations') }}" class="text-link">See offices and coverage</a>.</p>
        </div>
        <aside class="lg:sticky lg:top-28 lg:self-start">
            <div class="rounded-xl bg-char-900 p-6 text-white on-dark">
                <x-icon :name="$service['icon']" class="h-8 w-8 text-aqua" />
                <h2 class="mt-4 text-xl font-bold">{{ $isTransport ? 'Book a pickup' : 'Get a quote' }}</h2>
                <p class="mt-2 text-white/75">{{ $isTransport ? 'Tell us the pickup address, the destination and a time window.' : 'Tell us the site, island, hours and number of posts, and operations will be in touch.' }}</p>
                <a href="{{ $isTransport ? route('pickup') : route('quote', ['service' => $key]) }}" class="btn btn-aqua mt-5 w-full">{{ $isTransport ? 'Request an armoured pickup' : 'Request a quote' }}</a>
                <div class="mt-5 space-y-1 border-t border-white/10 pt-4 text-sm">
                    @foreach (['Freeport' => setting('freeport_phone'), 'Nassau' => setting('nassau_phone')] as $label => $phone)
                        @if ($phone)<a href="{{ tel_href($phone) }}" class="flex min-h-11 items-center justify-between hover:text-aqua"><span class="text-white/70">{{ $label }}</span><span class="num font-semibold">{{ format_phone($phone) }}</span></a>@endif
                    @endforeach
                </div>
            </div>
        </aside>
    </div>

    <section class="wrap mt-20" aria-labelledby="more-title">
        <h2 id="more-title" class="display-3">Other services</h2>
        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($others as $k => $o)
                <a href="{{ route('services.show', $k) }}" class="panel card-link flex items-center gap-4 p-5"><x-icon :name="$o['icon']" class="h-7 w-7 flex-none text-aqua-text" /><span class="font-semibold">{{ $o['name'] }}</span></a>
            @endforeach
        </div>
    </section>
</x-layouts.site>
