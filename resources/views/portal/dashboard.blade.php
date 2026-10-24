<x-portal-shell :title="'Hello, '.$applicant->first_name" lead="Your applications, what to do next, and messages from the recruitment team.">
    <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <div class="space-y-6">
            @foreach ($drafts as $d)
                <article class="panel flex flex-col gap-4 border-l-4 !border-l-[color:var(--gold)] p-6 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="mono-label text-gold-text">Draft · step {{ $d->step }} of 5</p>
                        <h2 class="mt-1 text-xl font-bold">{{ $d->job->title ?? ($d->position ? $d->positionLabel() : 'General application') }}</h2>
                        <p class="text-ink-2">Not sent yet. Pick up where you left off.</p>
                    </div>
                    <a href="{{ route('apply.step', [$d->reference, $d->step]) }}" class="btn btn-primary">Continue<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
                </article>
            @endforeach

            @forelse ($submitted as $a)
                @php($action = $a->isActive() && ($a->canBook() || $a->missingDocuments()))
                <a href="{{ route('portal.applications.show', $a->reference) }}" class="panel card-link block p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <span class="badge-id text-ink-3">{{ $a->reference }}</span>
                        @if ($action)<span class="chip" data-tone="attention"><x-icon name="alert" />Action needed</span>@endif
                    </div>
                    <h2 class="mt-3 text-xl font-bold">{{ $a->roleTitle() }}</h2>
                    <p class="text-ink-3">{{ $a->islandName() }} · sent {{ $a->submitted_at?->format('j M Y') }}</p>
                    <p class="mt-4 flex items-center gap-3"><span @class(['checkpoint is-light', 'is-done' => $a->status === 'hired', 'is-todo' => in_array($a->status, ['rejected', 'withdrawn'], true)])></span><span class="font-semibold">{{ \App\Models\Application::PUBLIC_STAGES[$a->status][0] }}</span></p>
                    @if ($a->isActive())<p class="mt-1 pl-8 text-ink-2">{{ $a->missingDocuments() ? 'Upload your '.implode(' and ', array_map(fn ($t) => strtolower(\App\Models\ApplicationDocument::TYPES[$t]), $a->missingDocuments())).'.' : \App\Models\Application::PUBLIC_STAGES[$a->status][1] }}</p>@endif
                </a>
            @empty
                @if ($drafts->isEmpty())
                    <div class="panel p-8">
                        <h2 class="display-3">No applications yet</h2>
                        <p class="mt-2 text-ink-2">Find an opening and apply in about ten minutes.</p>
                        <a href="{{ route('careers') }}" class="btn btn-primary mt-6">See open positions</a>
                    </div>
                @endif
            @endforelse
        </div>
        <aside class="space-y-6">
            <div class="panel p-5">
                <div class="flex items-center justify-between"><h2 class="font-semibold">Messages</h2>@if($unread)<span class="chip" data-tone="attention"><x-icon name="dot" />{{ $unread }} new</span>@endif</div>
                <ul class="mt-3 divide-y divide-line">
                    @forelse ($inbox as $n)
                        <li class="py-3"><a href="{{ $n->link ?? route('portal.inbox') }}" class="block hover:text-aqua-text"><p @class(['font-semibold' => ! $n->read_at])>{{ $n->title }}</p><p class="text-sm text-ink-3">{{ $n->created_at->diffForHumans() }}</p></a></li>
                    @empty
                        <li class="py-3 text-ink-3">Nothing yet.</li>
                    @endforelse
                </ul>
                <a href="{{ route('portal.inbox') }}" class="mt-2 inline-flex min-h-11 items-center text-link">All messages</a>
            </div>
            @if ($jobs->isNotEmpty())
                <div class="panel p-5">
                    <h2 class="font-semibold">Other openings</h2>
                    <ul class="mt-3 space-y-2">@foreach ($jobs as $j)<li><a href="{{ route('careers.show', $j) }}" class="flex min-h-11 flex-col justify-center hover:text-aqua-text"><span class="font-medium">{{ $j->title }}</span><span class="text-sm text-ink-3">{{ $j->islandName() }}</span></a></li>@endforeach</ul>
                    <a href="{{ route('apply') }}" class="btn btn-outline btn-sm mt-4 w-full">Start another application</a>
                </div>
            @endif
        </aside>
    </div>
</x-portal-shell>
