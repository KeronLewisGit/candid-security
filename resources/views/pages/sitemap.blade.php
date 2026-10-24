<x-layouts.site title="Sitemap">
    <x-page-header title="Sitemap" eyebrow="Every page" code="MAP" />
    <section class="wrap mt-12 grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            'Company' => [['Home', route('home')], ['About & history', route('about')], ['Locations', route('locations')], ['Contact', route('contact')]],
            'Services' => collect(candid('services'))->map(fn ($s, $k) => [$s['name'], route('services.show', $k)])->prepend(['All services', route('services')])->values()->all(),
            'Clients' => [['Request a quote', route('quote')], ['Armoured pickup', route('pickup')]],
            'Careers' => collect([['Careers', route('careers')], ['Apply online', route('apply')], ['Applicant portal', route('portal.login')]])->merge($jobs->map(fn ($j) => [$j->title.' · '.$j->islandName(), route('careers.show', $j)]))->all(),
        ] as $group => $links)
            <div>
                <h2 class="mono-label text-gold-text">{{ $group }}</h2>
                <ul class="mt-4 space-y-1">@foreach ($links as [$label, $url])<li><a class="inline-flex min-h-11 items-center text-link" href="{{ $url }}">{{ $label }}</a></li>@endforeach</ul>
            </div>
        @endforeach
    </section>
    <p class="wrap mt-10 text-sm text-ink-3">Also: <a class="text-link" href="{{ route('privacy') }}">Privacy</a> · <a class="text-link" href="{{ route('credits') }}">Photo credits</a> · <a class="text-link" href="{{ route('sitemap') }}">sitemap.xml</a></p>
</x-layouts.site>
