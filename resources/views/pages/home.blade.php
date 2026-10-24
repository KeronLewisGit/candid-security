<x-layouts.site>
    @php
        $services = candid('services');
        $freeport = setting('freeport_phone');
        $nassau = setting('nassau_phone');
        $islands = candid('islands');
    @endphp

    {{-- Hero --}}
    <section class="relative isolate overflow-hidden bg-char-900 text-white on-dark">
        <div class="absolute inset-0 -z-10">
            <x-photo key="security-guard" alt="" :eager="true" sizes="100vw" class="hero-photo h-full w-full" />
            <div class="hero-scrim absolute inset-0"></div>
        </div>
        <div class="wrap grid gap-10 pb-16 pt-28 md:pb-24 md:pt-36 lg:grid-cols-[1fr_20rem] lg:items-end">
            <div>
                <x-eyebrow code="EST. {{ candid('founded') }}">Freeport · Nassau · Abaco · Bimini</x-eyebrow>
                <h1 class="display-1 mt-5 max-w-4xl">Security officers on post across The Bahamas.</h1>
                <p class="lead mt-6 text-white/85">Armed and unarmed guarding, armoured cash transport, mobile patrol, aviation security and lifeguards. A Bahamian company, family-led since {{ candid('founded') }}.</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('quote') }}" class="btn btn-aqua">Request a quote<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
                    <a href="{{ route('careers') }}" class="btn btn-on-dark">Apply to join</a>
                </div>
            </div>
            <div class="rounded-xl bg-char-950/80 p-5 ring-1 ring-white/15 backdrop-blur">
                <p class="mono-label text-white/60">Call an office</p>
                <ul class="mt-3 divide-y divide-white/10">
                    @foreach (['Freeport' => $freeport, 'Nassau' => $nassau] as $label => $phone)
                        @if ($phone)
                            <li><a href="{{ tel_href($phone) }}" class="flex min-h-14 items-center justify-between gap-3 hover:text-aqua"><span class="text-white/80">{{ $label }}</span><span class="font-display num text-xl font-bold">{{ format_phone($phone) }}</span></a></li>
                        @endif
                    @endforeach
                </ul>
                <a href="{{ route('pickup') }}" class="mt-3 inline-flex min-h-11 items-center gap-2 text-sm font-semibold text-aqua hover:underline"><x-icon name="truck" class="h-4 w-4" />Book an armoured pickup online</a>
            </div>
        </div>
        <div class="absolute inset-x-0 bottom-4" aria-hidden="true"><div class="wrap flex items-center"><span class="checkpoint is-pulse h-3.5 w-3.5 border-2"></span><span class="route route-run flex-1"></span></div></div>
    </section>

    {{-- Services --}}
    <section class="wrap mt-20 md:mt-28" aria-labelledby="services-title">
        <div class="flex flex-wrap items-end justify-between gap-6">
            <div>
                <x-eyebrow code="01">What we do</x-eyebrow>
                <h2 id="services-title" class="display-2 mt-3 max-w-2xl">Five services, one Bahamian company.</h2>
            </div>
            <a href="{{ route('services') }}" class="btn btn-outline">All services<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
        </div>
        <div class="mt-10 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($services as $key => $s)
                <a href="{{ route('services.show', $key) }}" @class(['panel card-link group flex flex-col overflow-hidden', 'lg:col-span-2 lg:flex-row' => $loop->first]) data-reveal style="--reveal-delay: {{ $loop->index * 60 }}ms">
                    <x-photo :key="$s['card'] ?? $s['photo']" :alt="candid('photos.'.($s['card'] ?? $s['photo']).'.alt')" sizes="(min-width: 1024px) 40vw, (min-width: 768px) 50vw, 100vw" @class(['aspect-[16/10]', 'lg:aspect-auto lg:w-1/2' => $loop->first]) />
                    <div class="flex flex-1 flex-col p-6">
                        <div class="flex items-center justify-between gap-3">
                            <span class="badge-id text-ink-3">Post {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            @if ($s['since'])<span class="mono-label text-gold-text">Since {{ $s['since'] }}</span>@endif
                        </div>
                        <h3 class="display-3 mt-4">{{ $s['name'] }}</h3>
                        <p class="mt-2 text-ink-2">{{ $s['summary'] }}</p>
                        <span class="mt-auto inline-flex items-center gap-2 pt-5 font-semibold text-aqua-text">Read more<x-icon name="arrow-right" class="h-4 w-4 transition-transform group-hover:translate-x-1" /></span>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Coverage: the patrol route across the islands --}}
    <section class="mt-24 bg-surface-2 py-20 md:mt-32" aria-labelledby="islands-title">
        <div class="wrap">
            <x-eyebrow code="02">Where we work</x-eyebrow>
            <div class="mt-3 grid gap-6 lg:grid-cols-[1.3fr_1fr] lg:items-end">
                <h2 id="islands-title" class="display-2">Head office in Freeport. On post across four islands.</h2>
                <p class="measure text-ink-2">Our head office is on Polaris Drive in Freeport and our second office is in Nassau. Officers are also deployed to client sites in Abaco and Bimini.</p>
            </div>
            <ol class="relative mt-12 grid gap-6 md:grid-cols-4 md:gap-4">
                <span class="route route-light absolute left-2 right-2 top-[0.6rem] hidden md:block" aria-hidden="true"></span>
                @foreach ($islands as $key => $island)
                    @php($hq = $key === 'grand_bahama')
                    <li class="relative flex gap-4 md:block" data-reveal style="--reveal-delay: {{ $loop->index * 90 }}ms">
                        <span @class(['checkpoint is-light relative', 'is-done' => in_array($key, ['grand_bahama', 'new_providence'], true)])></span>
                        <div class="md:mt-5">
                            <p class="mono-label text-gold-text">{{ $island['code'] }} · {{ $hq ? 'Head office' : ($key === 'new_providence' ? 'Office' : 'Coverage') }}</p>
                            <h3 class="display-3 mt-1">{{ $island['name'] }}</h3>
                            <p class="mt-1 text-ink-2">{{ $hq ? 'Freeport' : ($key === 'new_providence' ? 'Nassau' : 'Client sites') }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
            <a href="{{ route('locations') }}" class="btn btn-outline mt-12">Offices &amp; coverage<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
        </div>
    </section>

    {{-- History --}}
    <section class="wrap mt-24 grid gap-12 md:mt-32 lg:grid-cols-[1fr_1fr] lg:items-center" aria-labelledby="history-title">
        <div>
            <x-eyebrow code="03">Since {{ candid('founded') }}</x-eyebrow>
            <h2 id="history-title" class="display-2 mt-3">Built up one service at a time.</h2>
            <p class="measure mt-5 text-ink-2">Cleveland and Marcia Duncombe founded Candid Security in Freeport in {{ candid('founded') }}. Armoured transport followed in 2002 and mobile patrol in 2007.</p>
            <a href="{{ route('about') }}" class="btn btn-outline mt-8">Our history &amp; leadership<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
        </div>
        <ol class="route-v space-y-7">
            @foreach (candid('history') as $h)
                <li class="relative flex gap-5" data-reveal>
                    <span class="checkpoint is-light is-done relative z-10 mt-1"></span>
                    <div>
                        <p class="font-display num text-3xl font-extrabold text-aqua-text">{{ $h['year'] }}</p>
                        <h3 class="mt-1 text-lg font-semibold" style="font-family: var(--ff-sans)">{{ $h['title'] }}</h3>
                        <p class="text-ink-2">{{ $h['body'] }}</p>
                    </div>
                </li>
            @endforeach
        </ol>
    </section>

    {{-- Careers band --}}
    <section class="mt-24 bg-char-900 py-20 text-white on-dark md:mt-32" aria-labelledby="careers-title">
        <div class="wrap grid gap-12 lg:grid-cols-[1.1fr_1fr]">
            <div>
                <x-eyebrow code="04">Careers</x-eyebrow>
                <h2 id="careers-title" class="display-2 mt-3">Apply online. No more paper forms.</h2>
                <p class="lead mt-5 text-white/80">Fill in the application on your phone, upload your police certificate and ID, and book your entrance exam and interview yourself. Track every step in your applicant portal.</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('careers') }}" class="btn btn-aqua">{{ $openCount ? 'See '.$openCount.' open '.str('position')->plural($openCount) : 'Apply now' }}<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
                    <a href="{{ route('portal.login') }}" class="btn btn-on-dark">Applicant sign-in</a>
                </div>
            </div>
            <ol class="route-v space-y-4">
                @foreach (['Apply online' => 'Five short steps; save and finish later.', 'Document check' => 'Police certificate and photo ID.', 'Entrance exam' => 'Book a sitting in Freeport or Nassau.', 'Drug test' => 'Book a time that suits you.', 'Interview' => 'Meet the recruitment team.', 'Background check & offer' => 'Then uniform, training and your first post.'] as $step => $detail)
                    <li class="relative flex gap-4">
                        <span class="checkpoint relative z-10 mt-0.5 {{ $loop->first ? 'is-done' : '' }}"></span>
                        <p><span class="font-semibold text-white">{{ $step }}</span> <span class="text-white/70">· {{ $detail }}</span></p>
                    </li>
                @endforeach
            </ol>
        </div>
        @if ($jobs->isNotEmpty())
            <div class="wrap mt-14 grid gap-4 md:grid-cols-3">
                @foreach ($jobs as $job)
                    <a href="{{ route('careers.show', $job) }}" class="group rounded-xl bg-char-800 p-5 ring-1 ring-white/10 transition hover:ring-aqua">
                        <p class="mono-label text-gold">{{ $job->islandName() }} · {{ $job->typeLabel() }}</p>
                        <h3 class="mt-2 text-xl font-bold">{{ $job->title }}</h3>
                        <p class="mt-2 line-clamp-2 text-white/75">{{ $job->summary }}</p>
                        <span class="mt-4 inline-flex items-center gap-2 font-semibold text-aqua">View &amp; apply<x-icon name="arrow-right" class="h-4 w-4 transition-transform group-hover:translate-x-1" /></span>
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    {{-- Two ways to work with us --}}
    <section class="wrap mt-24 grid gap-5 md:mt-32 md:grid-cols-2" aria-label="Get in touch">
        <div class="panel flex flex-col p-8 md:p-10">
            <x-icon name="badge" class="h-9 w-9 text-aqua-text" />
            <h2 class="display-3 mt-5">Need officers on site?</h2>
            <p class="mt-3 text-ink-2">Tell us the site, the island, the hours and how many posts. Armed or unarmed, one night or a standing contract.</p>
            <a href="{{ route('quote') }}" class="btn btn-primary mt-6 self-start">Request a guard-service quote<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
        </div>
        <div class="panel flex flex-col p-8 md:p-10">
            <x-icon name="truck" class="h-9 w-9 text-aqua-text" />
            <h2 class="display-3 mt-5">Need a cash pickup?</h2>
            <p class="mt-3 text-ink-2">Book a one-off or regular armoured pickup from your business to the bank. Operations confirms the time window by phone.</p>
            <a href="{{ route('pickup') }}" class="btn btn-outline mt-6 self-start md:mt-auto">Request an armoured pickup<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
        </div>
    </section>
</x-layouts.site>
