<x-layouts.site title="Application sent" :mobile-bar="false" :noindex="true">
    <x-page-header title="Application sent. Thank you." eyebrow="Reference" :code="$application->reference" lead="We have emailed a copy to {{ $application->email }}. Track every step from your applicant portal." />
    <section class="wrap mt-12 max-w-3xl space-y-6">
        <div class="panel p-6 md:p-8">
            <h2 class="display-3">What happens next</h2>
            <ol class="route-v mt-6 space-y-5">
                @foreach (['Document check' => $application->documentsComplete() ? 'Your police certificate and ID are uploaded. The team will check them.' : 'Upload your '.implode(' and ', array_map(fn ($t) => strtolower(\App\Models\ApplicationDocument::TYPES[$t]), $application->missingDocuments())).' from your portal so we can start.', 'Entrance exam' => 'When your documents pass, you will be invited to book an exam sitting.', 'Drug test & interview' => 'Book each one yourself in the portal.', 'Background check & offer' => 'We will contact you directly.'] as $title => $body)
                    <li class="relative flex gap-4"><span class="checkpoint is-light relative z-10 mt-0.5 {{ $loop->first ? 'is-done' : 'is-todo' }}"></span><div><p class="font-semibold">{{ $title }}</p><p class="text-ink-2">{{ $body }}</p></div></li>
                @endforeach
            </ol>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('portal.applications.show', $application->reference) }}" class="btn btn-primary">Track my application<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
            <a href="{{ route('careers') }}" class="btn btn-outline">Back to careers</a>
        </div>
    </section>
</x-layouts.site>
