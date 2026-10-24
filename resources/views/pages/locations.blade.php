<x-layouts.site title="Locations" description="Candid Security offices in Freeport, Grand Bahama (head office) and Nassau, New Providence, with officers deployed in Abaco and Bimini.">
    <x-page-header title="Two offices, four islands." eyebrow="Locations" code="GB · NP · AB · BI" photo="beach" lead="Head office in Freeport, a second office in Nassau, and officers on client sites in Abaco and Bimini." />

    <section class="wrap mt-16 grid gap-6 md:mt-20 lg:grid-cols-2" aria-label="Offices">
        @foreach (['freeport' => ['grand_bahama', 'Head office', 'freeport'], 'nassau' => ['new_providence', 'Office', 'nassau']] as $office => [$island, $kind, $photo])
            @php
                $address = setting($office.'_address');
                $phone = setting($office.'_phone');
                $fax = setting($office.'_fax');
                $hours = setting($office.'_hours');
            @endphp
            <article class="panel overflow-hidden" id="{{ $office }}">
                <x-photo :key="$photo" :alt="ucfirst($office).', '.island_name($island)" sizes="(min-width: 1024px) 50vw, 100vw" class="aspect-[16/9]" />
                <div class="p-6 md:p-8">
                    <p class="mono-label text-gold-text">{{ candid('islands.'.$island.'.code') }} · {{ $kind }}</p>
                    <h2 class="display-3 mt-2">{{ ucfirst($office) }}, {{ island_name($island) }}</h2>
                    <dl class="mt-6 grid gap-x-6 gap-y-4 sm:grid-cols-[7rem_1fr]">
                        @if ($address)<dt class="font-semibold text-ink-3">Address</dt><dd class="whitespace-pre-line">{{ $address }}</dd>@endif
                        @if ($phone)<dt class="font-semibold text-ink-3">Phone</dt><dd><a href="{{ tel_href($phone) }}" class="text-link num font-semibold">{{ format_phone($phone) }}</a></dd>@endif
                        @if ($fax)<dt class="font-semibold text-ink-3">Fax</dt><dd class="num">{{ format_phone($fax) }}</dd>@endif
                        <dt class="font-semibold text-ink-3">Hours</dt><dd>{{ $hours ?: 'Please call for office hours.' }}</dd>
                    </dl>
                    <div class="mt-7 flex flex-wrap gap-3">
                        @if ($phone)<a href="{{ tel_href($phone) }}" class="btn btn-primary btn-sm"><x-icon name="phone" class="h-4 w-4" />Call {{ ucfirst($office) }}</a>@endif
                        <a href="https://www.openstreetmap.org/search?query={{ urlencode(str_replace("\n", ', ', (string) $address) ?: ucfirst($office).', Bahamas') }}" class="btn btn-outline btn-sm" rel="noopener" target="_blank">Map<x-icon name="external" class="h-4 w-4" /></a>
                    </div>
                </div>
            </article>
        @endforeach
    </section>

    <section class="wrap mt-20" aria-labelledby="coverage-title">
        <x-eyebrow code="AB · BI">Family Island coverage</x-eyebrow>
        <h2 id="coverage-title" class="display-2 mt-3">Abaco and Bimini</h2>
        <p class="measure mt-4 text-ink-2">Officers are deployed to client sites in Abaco and Bimini and managed from our Freeport head office. Call Freeport or request a quote to arrange cover.</p>
        <div class="mt-10 grid gap-6 md:grid-cols-2">
            @foreach (['abaco', 'bimini'] as $island)
                <article class="panel overflow-hidden">
                    <x-photo :key="$island" :alt="island_name($island).', The Bahamas'" sizes="(min-width: 768px) 50vw, 100vw" class="aspect-[16/9]" />
                    <div class="flex flex-wrap items-center justify-between gap-4 p-6">
                        <div>
                            <p class="mono-label text-gold-text">{{ candid('islands.'.$island.'.code') }} · Coverage</p>
                            <h3 class="display-3 mt-1">{{ island_name($island) }}</h3>
                            @if ($n = $openByIsland[$island] ?? 0)<p class="mt-1 text-ink-2"><a class="text-link" href="{{ route('careers', ['island' => $island]) }}">{{ $n }} open {{ str('position')->plural($n) }}</a></p>@endif
                        </div>
                        <a href="{{ route('quote') }}" class="btn btn-outline btn-sm">Request cover</a>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
</x-layouts.site>
