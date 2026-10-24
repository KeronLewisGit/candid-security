<x-layouts.site title="About Candid Security" description="Candid Security Ltd. was founded in Freeport, Grand Bahama in {{ candid('founded') }} by Cleveland and Marcia Duncombe. History, leadership and how the company has grown.">
    <x-page-header title="Bahamian-owned. Family-led. Since {{ candid('founded') }}." eyebrow="About us" code="EST. {{ candid('founded') }}" photo="freeport" :lead="'Founded in Freeport by Cleveland and Marcia Duncombe, Candid Security has grown from guarding into armoured transport, mobile patrol, aviation security and lifeguards.'" />

    <section id="history" class="wrap mt-16 grid gap-12 md:mt-20 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)]" aria-labelledby="history-title">
        <div>
            <x-eyebrow code="01">Company history</x-eyebrow>
            <h2 id="history-title" class="display-2 mt-3">From one service to five.</h2>
            <div class="prose-copy measure mt-6 text-ink-2">
                @foreach (candid('about') as $paragraph)<p>{{ $paragraph }}</p>@endforeach
            </div>
        </div>
        <ol class="route-v space-y-8 lg:pt-14">
            @foreach (candid('history') as $h)
                <li class="relative flex gap-5" data-reveal>
                    <span class="checkpoint is-light is-done relative z-10 mt-1.5"></span>
                    <div>
                        <p class="font-display num text-4xl font-extrabold text-aqua-text">{{ $h['year'] }}</p>
                        <h3 class="mt-1 text-lg font-semibold" style="font-family: var(--ff-sans)">{{ $h['title'] }}</h3>
                        <p class="measure text-ink-2">{{ $h['body'] }}</p>
                    </div>
                </li>
            @endforeach
        </ol>
    </section>

    <section id="leadership" class="mt-24 bg-surface-2 py-20" aria-labelledby="leadership-title">
        <div class="wrap">
            <x-eyebrow code="02">Leadership</x-eyebrow>
            <h2 id="leadership-title" class="display-2 mt-3">Executive &amp; management team</h2>
            <div class="mt-10 grid gap-5 md:grid-cols-2">
                @foreach (candid('leadership') as $person)
                    <article class="panel flex gap-5 p-6 md:p-8">
                        <span class="font-display grid h-16 w-16 flex-none place-items-center rounded-full bg-char-900 text-xl font-extrabold text-aqua" aria-hidden="true">{{ initials($person['name']) }}</span>
                        <div>
                            <h3 class="text-xl font-bold">{{ $person['name'] }}</h3>
                            <p class="mono-label mt-1 text-gold-text">{{ $person['role'] }}</p>
                            @if (! empty($person['bio']))<p class="mt-3 text-ink-2">{{ $person['bio'] }}</p>@endif
                        </div>
                    </article>
                @endforeach
            </div>
            @if ($team = candid('management'))
                <h3 class="display-3 mt-14">Management</h3>
                <ul class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($team as $m)
                        <li class="panel p-5"><p class="font-semibold">{{ $m['name'] }}</p><p class="text-sm text-ink-3">{{ $m['role'] }}</p></li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>

    <section class="wrap mt-24 grid gap-12 lg:grid-cols-2" aria-labelledby="values-title">
        <div>
            <x-eyebrow code="03">Mission &amp; values</x-eyebrow>
            <blockquote class="mt-5 border-l-4 border-gold pl-6">
                <p id="values-title" class="font-display text-2xl font-bold leading-snug md:text-3xl">“{{ candid('mission.statement') }}”</p>
                <footer class="mono-label mt-4 text-ink-3">Candid mission statement</footer>
            </blockquote>
            <ul class="mt-10 grid gap-3 sm:grid-cols-2">
                @foreach (candid('mission.values') as $value)
                    <li class="panel flex items-start gap-3 p-5"><span class="chev mt-1" aria-hidden="true"></span><span class="font-semibold">{{ $value }}</span></li>
                @endforeach
            </ul>
        </div>
        <div>
            <x-photo key="candid-training" :alt="candid('photos.candid-training.alt')" sizes="(min-width: 1024px) 600px, 100vw" class="aspect-[4/3] rounded-xl" />
            <h3 class="display-3 mt-8">Training</h3>
            <p class="mt-2 text-ink-2">Officers are hired after background checks and interviews, then trained with a blended approach:</p>
            <ul class="mt-4 space-y-2">
                @foreach (candid('training') as $item)
                    <li class="flex gap-2.5"><x-icon name="check" class="mt-1 h-4 w-4 flex-none text-aqua-text" />{{ $item }}</li>
                @endforeach
            </ul>
        </div>
    </section>

    <section class="wrap mt-20">
        <div class="flex flex-col gap-6 rounded-xl bg-char-900 p-8 text-white on-dark md:flex-row md:items-center md:justify-between md:p-10">
            <div>
                <h2 class="display-3">Work with Candid</h2>
                <p class="mt-2 text-white/75">Request officers for your site, or apply to join the team.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('quote') }}" class="btn btn-aqua">Request a quote</a>
                <a href="{{ route('careers') }}" class="btn btn-on-dark">Careers</a>
            </div>
        </div>
    </section>
</x-layouts.site>
