<x-layouts.site title="Careers" description="Security officer, armoured officer and lifeguard jobs with Candid Security in Freeport, Nassau, Abaco and Bimini. Apply online and track your application.">
    <x-page-header title="Join Candid. Apply online, track every step." eyebrow="Careers" code="{{ $total }} OPEN" photo="bahamas-flag" lead="No more printing a form and dropping it at the office. Apply from your phone, upload your documents, and book your exam and interview yourself.">
        <div class="mt-8 flex flex-wrap gap-3">
            <a href="#openings" class="btn btn-aqua">See open positions</a>
            <a href="{{ route('portal.login') }}" class="btn btn-on-dark">Already applied? Sign in</a>
        </div>
    </x-page-header>

    @if ($note = setting('careers_note'))
        <div class="wrap mt-8"><p class="flex items-start gap-3 rounded-lg bg-aqua-soft px-5 py-4 font-medium"><x-icon name="bell" class="mt-0.5 h-5 w-5 flex-none text-aqua-text" />{{ $note }}</p></div>
    @endif

    {{-- How hiring works --}}
    <section class="wrap mt-16" aria-labelledby="process-title">
        <x-eyebrow code="01">How hiring works</x-eyebrow>
        <h2 id="process-title" class="display-2 mt-3">Six checkpoints from application to first post.</h2>
        <ol class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                ['Apply online', 'Five short steps, saved as you go. Finish later if you need to.', 'file'],
                ['Document check', 'Upload your police certificate and passport or photo ID. We check them and tell you if anything is missing.', 'clipboard'],
                ['Entrance exam', 'Book a sitting in your portal. The pass mark is '.setting('exam_pass_mark', '70').'%.', 'pencil'],
                ['Drug test', 'Book a time that suits you.', 'shield'],
                ['Interview', 'Meet the recruitment team in Freeport or Nassau.', 'users'],
                ['Background check & offer', 'Then uniform, training and your first post.', 'badge'],
            ] as $i => [$step, $detail, $icon])
                <li class="panel flex gap-4 p-5">
                    <span class="font-display num grid h-11 w-11 flex-none place-items-center rounded-full bg-char-900 text-lg font-extrabold text-aqua">{{ $i + 1 }}</span>
                    <div><h3 class="text-lg font-semibold" style="font-family: var(--ff-sans)">{{ $step }}</h3><p class="mt-1 text-ink-2">{{ $detail }}</p></div>
                </li>
            @endforeach
        </ol>
    </section>

    {{-- Openings --}}
    <section id="openings" class="wrap mt-20 scroll-mt-28" aria-labelledby="openings-title">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <x-eyebrow code="02">Open positions</x-eyebrow>
                <h2 id="openings-title" class="display-2 mt-3">{{ $island ? island_name($island) : 'All islands' }}</h2>
            </div>
        </div>
        <nav class="tag-row mt-6" aria-label="Filter by island">
            <a href="{{ route('careers') }}#openings" class="tag" @if(! $island) aria-current="true" @endif>All <span class="count">{{ $total }}</span></a>
            @foreach (candid('islands') as $key => $i)
                <a href="{{ route('careers', ['island' => $key]) }}#openings" class="tag" @if($island === $key) aria-current="true" @endif>{{ $i['name'] }} <span class="count">{{ $counts[$key] ?? 0 }}</span></a>
            @endforeach
        </nav>

        @if ($jobs->isEmpty())
            <div class="panel mt-8 p-8">
                <h3 class="display-3">No openings listed {{ $island ? 'on '.island_name($island) : '' }} right now.</h3>
                <p class="measure mt-3 text-ink-2">Candid is always looking for well-rounded people. Send a general application and we will contact you when a post opens.</p>
                <a href="{{ route('apply') }}" class="btn btn-primary mt-6">Send a general application</a>
            </div>
        @else
            <ul class="mt-8 grid gap-4 md:grid-cols-2">
                @foreach ($jobs as $job)
                    <li>
                        <a href="{{ route('careers.show', $job) }}" class="panel card-link group flex h-full flex-col p-6">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="badge-id text-aqua-text">{{ candid('islands.'.$job->island.'.code') }} · {{ $job->islandName() }}</span>
                                <span class="chip" data-tone="neutral"><x-icon name="clock" />{{ $job->typeLabel() }}</span>
                                @if ($job->openings > 1)<span class="chip" data-tone="neutral"><x-icon name="users" />{{ $job->openings }} openings</span>@endif
                            </div>
                            <h3 class="display-3 mt-4 group-hover:text-aqua-text">{{ $job->title }}</h3>
                            <p class="mt-2 text-ink-2">{{ $job->summary }}</p>
                            <span class="mt-auto inline-flex items-center gap-2 pt-5 font-semibold text-aqua-text">View &amp; apply<x-icon name="arrow-right" class="h-4 w-4 transition-transform group-hover:translate-x-1" /></span>
                        </a>
                    </li>
                @endforeach
            </ul>
            <p class="mt-6 text-ink-2">Don't see your role? <a href="{{ route('apply') }}" class="text-link">Send a general application</a>.</p>
        @endif
    </section>

    {{-- Requirements + benefits --}}
    <section class="wrap mt-20 grid gap-6 lg:grid-cols-2" aria-label="Requirements and benefits">
        <div class="panel p-6 md:p-8">
            <x-eyebrow code="03">What we look for</x-eyebrow>
            <ul class="mt-5 space-y-3">
                @foreach (candid('careers.requirements') as $r)<li class="flex gap-3"><x-icon name="check" class="mt-1 h-4 w-4 flex-none text-aqua-text" />{{ $r }}</li>@endforeach
            </ul>
            <h3 class="mt-8 font-semibold">Have these ready to upload</h3>
            <ul class="mt-3 space-y-2 text-ink-2">
                @foreach (\App\Models\ApplicationDocument::TYPES as $key => $label)
                    <li class="flex gap-3"><x-icon name="file" class="mt-1 h-4 w-4 flex-none" />{{ $label }}@if(in_array($key, \App\Models\Application::REQUIRED_DOCUMENTS, true)) <span class="font-semibold text-ink">(required)</span>@endif</li>
                @endforeach
            </ul>
            <p class="mt-4 text-sm text-ink-3">Phone photos are fine. We never ask for your National Insurance number online; that is collected in person if you are hired.</p>
        </div>
        <div class="rounded-[10px] bg-char-900 p-6 text-white on-dark md:p-8">
            <x-eyebrow code="04">What Candid provides</x-eyebrow>
            <ul class="mt-5 space-y-3">
                @foreach (candid('careers.benefits') as $b)<li class="flex gap-3"><span class="chev mt-1" aria-hidden="true"></span>{{ $b }}</li>@endforeach
            </ul>
            <p class="mt-8 text-white/75">Questions about a role? Email <a href="mailto:{{ setting('hr_email') }}" class="font-semibold text-aqua underline-offset-4 hover:underline">{{ setting('hr_email') }}</a>.</p>
        </div>
    </section>
</x-layouts.site>
