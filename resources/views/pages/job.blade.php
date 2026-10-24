@php
    $posting = [
        '@context' => 'https://schema.org', '@type' => 'JobPosting', 'title' => $job->title, 'description' => nl2br(e($job->summary."\n\n".$job->description)),
        'datePosted' => ($job->published_at ?? $job->created_at)->toDateString(), 'validThrough' => $job->closes_on?->toDateString(),
        'employmentType' => strtoupper($job->employment_type === 'seasonal' ? 'temporary' : $job->employment_type),
        'hiringOrganization' => ['@type' => 'Organization', 'name' => candid('name'), 'sameAs' => route('home')],
        'jobLocation' => ['@type' => 'Place', 'address' => ['@type' => 'PostalAddress', 'addressLocality' => $job->island === 'grand_bahama' ? 'Freeport' : ($job->island === 'new_providence' ? 'Nassau' : $job->islandName()), 'addressRegion' => $job->islandName(), 'addressCountry' => 'BS']],
        'directApply' => true,
    ];
@endphp
<x-layouts.site :title="$job->title.' · '.$job->islandName()" :description="$job->summary" :json-ld="$job->isLive() ? [$posting] : []" :noindex="! $job->isLive()">
    <x-page-header :title="$job->title" eyebrow="{{ $job->islandName() }} · {{ $job->typeLabel() }}" :code="candid('islands.'.$job->island.'.code')" :lead="$job->summary">
        <nav aria-label="Breadcrumb" class="mt-6 text-sm text-white/70"><a href="{{ route('careers') }}" class="inline-flex min-h-11 items-center underline-offset-4 hover:underline">Careers</a> <span aria-hidden="true">/</span> <span aria-current="page">{{ $job->title }}</span></nav>
    </x-page-header>

    <div class="wrap mt-14 grid gap-12 lg:grid-cols-[minmax(0,1fr)_21rem]">
        <article class="prose-copy measure text-ink-2">
            @if (! $job->isLive())
                <p class="rounded-lg bg-surface-2 px-5 py-4 font-medium text-ink">This posting is paused and not taking applications right now.</p>
            @endif
            @if ($job->description)
                @foreach (preg_split('/\R{2,}/', $job->description) as $para)<p>{{ $para }}</p>@endforeach
            @endif
            @if ($duties = $job->lines('duties'))
                <h2 class="text-ink">What you'll do</h2>
                <ul>@foreach ($duties as $d)<li>{{ $d }}</li>@endforeach</ul>
            @endif
            @if ($req = $job->lines('requirements'))
                <h2 class="text-ink">Requirements</h2>
                <ul>@foreach ($req as $r)<li>{{ $r }}</li>@endforeach</ul>
            @endif
            <h2 class="text-ink">Documents to upload</h2>
            <p>Your Police Character Certificate and passport (first three pages) or photo ID. A passport-size photo, written references and, if you are not Bahamian, a spouse or work permit help too. You can add any of these later from your applicant portal.</p>
        </article>
        <aside class="lg:sticky lg:top-28 lg:self-start">
            <div class="panel p-6">
                <dl class="space-y-3 text-[0.9375rem]">
                    <div class="flex justify-between gap-4"><dt class="text-ink-3">Island</dt><dd class="font-semibold">{{ $job->islandName() }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-ink-3">Position</dt><dd class="text-right font-semibold">{{ $job->positionLabel() }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-ink-3">Type</dt><dd class="font-semibold">{{ $job->typeLabel() }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-ink-3">Openings</dt><dd class="num font-semibold">{{ $job->openings }}</dd></div>
                    @if ($job->closes_on)<div class="flex justify-between gap-4"><dt class="text-ink-3">Closes</dt><dd class="font-semibold">{{ $job->closes_on->format('j M Y') }}</dd></div>@endif
                </dl>
                @if ($job->isLive())
                    <a href="{{ route('apply', ['job' => $job->slug]) }}" class="btn btn-primary mt-6 w-full">Apply online<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
                    <p class="mt-3 text-center text-sm text-ink-3">About 10 minutes. Save and finish later.</p>
                @endif
            </div>
            @if ($more->isNotEmpty())
                <h2 class="mono-label mt-8 text-ink-3">More on {{ $job->islandName() }}</h2>
                <ul class="mt-3 space-y-2">
                    @foreach ($more as $m)<li><a href="{{ route('careers.show', $m) }}" class="panel card-link block p-4 font-semibold">{{ $m->title }}</a></li>@endforeach
                </ul>
            @endif
        </aside>
    </div>
</x-layouts.site>
