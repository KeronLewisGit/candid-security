@php
    $A = \App\Models\Application::class;
    $D = \App\Models\ApplicationDocument::class;
    $index = $application->stageIndex();
    $closed = $index === null;
    $kind = $application->bookableKind();
    $booking = $kind ? $application->bookingFor($kind) : null;
    $missing = $application->isActive() ? $application->missingDocuments() : [];
    [$headline, $now] = $A::PUBLIC_STAGES[$application->status];
@endphp
<x-portal-shell :title="$application->roleTitle()" :code="$application->reference" :lead="$application->islandName().' · sent '.$application->submitted_at?->format('j F Y')">
    {{-- Tracker: the patrol route from application to offer --}}
    <section class="panel p-5 sm:p-7" aria-labelledby="status-title">
        <ol class="tracker" aria-label="Hiring steps">
            @foreach ($A::ACTIVE_STAGES as $i => $stage)
                @php($state = $closed ? ($application->status === 'hired' ? 'is-done' : '') : ($i < $index ? 'is-done' : ($i === $index ? 'is-current' : '')))
                <li class="{{ $state }}" @if($i === $index) aria-current="step" @endif>
                    <span class="checkpoint is-light {{ $state === 'is-done' ? 'is-done' : ($state === 'is-current' ? 'is-pulse' : 'is-todo') }}"></span>
                    <span class="hidden md:block">{{ $A::PUBLIC_STAGES[$stage][0] }}</span>
                </li>
            @endforeach
        </ol>
        <div class="mt-6 border-t border-line pt-6">
            <p class="mono-label text-gold-text">{{ $closed ? 'Status' : 'Step '.($index + 1).' of '.count($A::ACTIVE_STAGES) }}</p>
            <h2 id="status-title" class="display-3 mt-1">{{ $headline }}</h2>
            <p class="measure mt-2 text-ink-2">{{ $now }}</p>
        </div>
    </section>

    <div class="mt-8 grid gap-8 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="space-y-8">
            {{-- Booking --}}
            @if ($kind)
                <section class="panel p-5 sm:p-7" aria-labelledby="book-title">
                    <h2 id="book-title" class="display-3">{{ $booking ? 'Your booking' : 'Book your '.strtolower(\App\Models\ScreeningSlot::KINDS[$kind]) }}</h2>
                    @if ($booking)
                        <div class="mt-4 rounded-lg bg-aqua-soft p-5">
                            <p class="font-semibold">You are booked</p>
                            <p class="mt-1 text-lg">{{ $booking->slot->starts_at->format('l j F, g:i a') }}</p>
                            <p class="text-ink-2">{{ $booking->slot->location }} · about {{ $booking->slot->duration_minutes }} minutes</p>
                            @if ($booking->slot->starts_at->gt(now()->addDay()))
                                <form method="POST" action="{{ route('portal.applications.cancel', [$application->reference, $booking]) }}" class="mt-4">@csrf @method('DELETE')<button class="btn btn-outline btn-sm">Cancel and choose another time</button></form>
                            @else
                                <p class="mt-3 text-sm text-ink-3">To change a booking within 24 hours, call the office.</p>
                            @endif
                        </div>
                    @elseif ($slots->isEmpty())
                        <p class="mt-3 text-ink-2">No times are open on {{ $application->islandName() }} right now. New dates are added regularly, and we will message you when they are. You can also call {{ format_phone(setting($application->island === 'new_providence' ? 'nassau_phone' : 'freeport_phone')) }}.</p>
                    @else
                        <form method="POST" action="{{ route('portal.applications.book', $application->reference) }}" class="mt-4 space-y-4">
                            @csrf
                            <fieldset>
                                <legend class="label">Choose a time</legend>
                                <div class="grid gap-2.5 sm:grid-cols-2">
                                    @foreach ($slots as $slot)
                                        <label class="choice"><input type="radio" name="slot" value="{{ $slot->id }}" required><span><span class="block font-semibold">{{ $slot->starts_at->format('D j M · g:i a') }}</span><span class="block text-sm text-ink-3">{{ $slot->location }} · {{ $slot->seatsLeft() }} {{ str('seat')->plural($slot->seatsLeft()) }} left</span></span></label>
                                    @endforeach
                                </div>
                                <x-error for="slot" />
                            </fieldset>
                            <button class="btn btn-primary">Book this time</button>
                        </form>
                    @endif
                </section>
            @endif

            {{-- Documents --}}
            <section class="panel p-5 sm:p-7" aria-labelledby="docs-title">
                <h2 id="docs-title" class="display-3">Documents</h2>
                @if ($missing)
                    <p class="mt-3 flex items-start gap-3 rounded-lg bg-surface-2 px-4 py-3"><x-icon name="alert" class="mt-0.5 h-5 w-5 flex-none text-[color:var(--attention)]" />Still needed: {{ implode(', ', array_map(fn ($t) => $D::TYPES[$t], $missing)) }}.</p>
                @endif
                <ul class="mt-4 divide-y divide-line">
                    @forelse ($application->documents as $doc)
                        <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                            <div class="min-w-0">
                                <p class="font-semibold">{{ $doc->typeLabel() }}</p>
                                <p class="truncate text-sm text-ink-3"><a href="{{ route('portal.documents.show', $doc) }}" class="text-link">{{ $doc->original_name }}</a> · {{ $doc->sizeLabel() }}</p>
                                @if ($doc->status === 'rejected' && $doc->review_note)<p class="text-sm text-[color:var(--critical-text)]">{{ $doc->review_note }}</p>@endif
                            </div>
                            <x-chip :tone="['accepted' => 'good', 'rejected' => 'critical', 'pending' => 'neutral'][$doc->status]" :icon="['accepted' => 'check', 'rejected' => 'x', 'pending' => 'clock'][$doc->status]">{{ $doc->statusLabel() }}</x-chip>
                        </li>
                    @empty
                        <li class="py-3 text-ink-3">No documents uploaded yet.</li>
                    @endforelse
                </ul>
                @if ($application->isActive())
                    <form method="POST" action="{{ route('portal.applications.documents', $application->reference) }}" enctype="multipart/form-data" class="mt-5 grid gap-4 border-t border-line pt-5 sm:grid-cols-[minmax(0,1fr)_minmax(0,1.3fr)]">
                        @csrf
                        <x-field name="type" label="Document" :options="$D::TYPES" :value="$missing[0] ?? null" required />
                        <div>
                            <label for="upload-file" class="label">File</label>
                            <input id="upload-file" type="file" name="file" class="input" required accept=".pdf,.jpg,.jpeg,.png,.webp,.heic,.doc,.docx,image/*,application/pdf" @error('file') aria-invalid="true" @enderror>
                            <x-error for="file" />
                        </div>
                        <button class="btn btn-primary sm:col-span-2 sm:justify-self-start"><x-icon name="upload" class="h-4 w-4" />Upload</button>
                    </form>
                @endif
            </section>
        </div>

        <aside class="space-y-6">
            <section class="panel p-5" aria-labelledby="timeline-title">
                <h2 id="timeline-title" class="font-semibold">Timeline</h2>
                <ol class="route-v mt-4 space-y-4">
                    @foreach ($timeline as $note)
                        <li class="relative flex gap-3"><span class="checkpoint is-light is-done relative z-10 mt-0.5 h-5 w-5"></span><div><p class="font-medium">{{ $note->body }}</p><p class="text-sm text-ink-3">{{ $note->created_at->format('j M Y, g:i a') }}</p></div></li>
                    @endforeach
                </ol>
            </section>
            <section class="panel p-5 text-[0.9375rem]">
                <h2 class="font-semibold">Questions?</h2>
                <p class="mt-1 text-ink-2">Email <a class="text-link" href="mailto:{{ setting('hr_email') }}">{{ setting('hr_email') }}</a> and quote {{ $application->reference }}.</p>
                @if ($application->isActive())
                    <details class="mt-4">
                        <summary class="inline-flex min-h-11 cursor-pointer items-center font-semibold text-ink-2">Withdraw this application</summary>
                        <form method="POST" action="{{ route('portal.applications.withdraw', $application->reference) }}" class="mt-2">@csrf<p class="text-ink-3">This removes you from the hiring process for this role.</p><button class="btn btn-outline btn-sm mt-3">Yes, withdraw</button></form>
                    </details>
                @endif
            </section>
        </aside>
    </div>
</x-portal-shell>
