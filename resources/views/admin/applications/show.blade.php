@php
    $A = \App\Models\Application::class;
    $D = \App\Models\ApplicationDocument::class;
    $a = $application;
    $index = $a->stageIndex();
    $missing = $a->missingDocuments();
    $next = $index !== null && isset($A::ACTIVE_STAGES[$index + 1]) ? $A::ACTIVE_STAGES[$index + 1] : null;
@endphp
<x-layouts.admin :title="$a->full_name">
    <x-slot:actions>
        <a href="{{ route('admin.applications.pipeline', ['island' => $a->island]) }}" class="a-btn a-btn-ghost"><x-icon name="columns" class="h-4 w-4" />Pipeline</a>
    </x-slot:actions>

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="space-y-5">
            {{-- Summary + stage --}}
            <section class="a-card p-5">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="mono text-sm text-ink-3">{{ $a->reference }} · sent {{ $a->submitted_at?->format('j M Y, g:i a') }}</p>
                        <p class="mt-1 text-lg font-semibold">{{ $a->roleTitle() }} · {{ $a->islandName() }}</p>
                        <p class="mt-1 flex flex-wrap gap-x-4 text-ink-2"><a class="text-link" href="tel:{{ preg_replace('/[^\d+]/', '', $a->phone) }}">{{ $a->phone }}</a><a class="text-link" href="mailto:{{ $a->email }}">{{ $a->email }}</a></p>
                    </div>
                    <x-status :stage="$a->status" class="!text-sm" />
                </div>
                <ol class="tracker mt-6" aria-label="Pipeline">
                    @foreach ($A::ACTIVE_STAGES as $i => $stage)
                        @php($state = $index === null ? ($a->status === 'hired' ? 'is-done' : '') : ($i < $index ? 'is-done' : ($i === $index ? 'is-current' : '')))
                        <li class="{{ $state }}"><span class="checkpoint is-light {{ $state === 'is-done' ? 'is-done' : ($state === 'is-current' ? '' : 'is-todo') }}"></span><span class="hidden text-xs md:block">{{ $A::STAGES[$stage] }}</span></li>
                    @endforeach
                </ol>
                <form method="POST" action="{{ route('admin.applications.update', $a) }}" class="mt-6 grid gap-3 border-t border-line pt-5 md:grid-cols-[14rem_1fr_auto] md:items-end">
                    @csrf @method('PATCH')
                    <div><label class="a-label" for="status">Move to</label><select id="status" name="status" class="a-input w-full">@foreach ($A::STAGES as $k => $l)<option value="{{ $k }}" @selected(($next ?? $a->status) === $k)>{{ $l }}{{ $k === $a->status ? ' (current)' : '' }}</option>@endforeach</select></div>
                    <div><label class="a-label" for="comment">Internal comment <span class="font-normal text-ink-3">(optional, not shown to the applicant)</span></label><input id="comment" name="comment" class="a-input w-full" maxlength="1000"></div>
                    <button class="a-btn a-btn-primary">Move &amp; notify</button>
                </form>
                @if ($next === 'entrance_exam' && $missing)
                    <p class="mt-3 flex items-center gap-2 text-sm text-[color:var(--critical-text)]"><x-icon name="alert" class="h-4 w-4" />Documents are still missing. Check them before moving on.</p>
                @endif
            </section>

            {{-- Documents --}}
            <section class="a-card p-5" aria-labelledby="docs-title">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 id="docs-title" class="font-semibold">Documents</h2>
                    @if ($missing)<x-chip tone="attention" icon="alert">Missing: {{ implode(', ', array_map(fn ($t) => $D::TYPES[$t], $missing)) }}</x-chip>@else<x-chip tone="good" icon="check">Required documents in</x-chip>@endif
                </div>
                <ul class="mt-3 divide-y divide-line">
                    @forelse ($a->documents as $doc)
                        <li class="grid gap-3 py-3 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
                            <div class="min-w-0">
                                <p class="font-semibold">{{ $doc->typeLabel() }} <x-chip :tone="['accepted' => 'good', 'rejected' => 'critical', 'pending' => 'neutral'][$doc->status]" :icon="['accepted' => 'check', 'rejected' => 'x', 'pending' => 'clock'][$doc->status]" class="ml-1">{{ $doc->statusLabel() }}</x-chip></p>
                                <p class="truncate text-sm text-ink-3"><a class="text-link" href="{{ route('admin.documents.show', $doc) }}" target="_blank" rel="noopener">{{ $doc->original_name }}</a> · {{ $doc->sizeLabel() }} · {{ $doc->created_at->format('j M') }}@if($doc->review_note) · “{{ $doc->review_note }}”@endif</p>
                            </div>
                            @if ($doc->status === 'pending')
                                <div class="flex flex-wrap gap-2">
                                    <form method="POST" action="{{ route('admin.documents.update', $doc) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="accepted"><button class="a-btn a-btn-ghost"><x-icon name="check" class="h-4 w-4 text-[color:var(--good)]" />Accept</button></form>
                                    <details class="relative">
                                        <summary class="a-btn a-btn-ghost list-none"><x-icon name="x" class="h-4 w-4 text-[color:var(--critical)]" />Send back</summary>
                                        <form method="POST" action="{{ route('admin.documents.update', $doc) }}" class="absolute right-0 z-10 mt-1 w-72 space-y-2 rounded-lg border border-line bg-surface p-3 shadow-lg">@csrf @method('PATCH')<input type="hidden" name="status" value="rejected"><label class="a-label" for="note-{{ $doc->id }}">Reason (sent to the applicant)</label><input id="note-{{ $doc->id }}" name="review_note" class="a-input w-full" required maxlength="300" placeholder="e.g. Photo is blurry, certificate older than 6 months"><button class="a-btn a-btn-primary w-full">Send back</button></form>
                                    </details>
                                </div>
                            @endif
                        </li>
                    @empty
                        <li class="py-3 text-ink-3">Nothing uploaded yet.</li>
                    @endforelse
                </ul>
                <details class="mt-3">
                    <summary class="inline-flex min-h-11 cursor-pointer items-center text-sm font-semibold text-link">Add a document dropped off at the office</summary>
                    <form method="POST" action="{{ route('admin.documents.store', $a) }}" enctype="multipart/form-data" class="mt-2 flex flex-wrap items-end gap-3">
                        @csrf
                        <div><label class="a-label" for="doc-type">Type</label><select id="doc-type" name="type" class="a-input">@foreach ($D::TYPES as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
                        <div><label class="a-label" for="doc-file">File</label><input id="doc-file" type="file" name="file" class="a-input" required></div>
                        <button class="a-btn a-btn-ghost"><x-icon name="upload" class="h-4 w-4" />Upload</button>
                    </form>
                </details>
            </section>

            {{-- Screening results --}}
            <section class="a-card p-5" aria-labelledby="results-title">
                <h2 id="results-title" class="font-semibold">Screening results</h2>
                <form method="POST" action="{{ route('admin.applications.update', $a) }}" class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @csrf @method('PATCH')
                    <div><label class="a-label" for="exam_score">Entrance exam score (%) <span class="font-normal text-ink-3">pass {{ setting('exam_pass_mark', '70') }}</span></label><input id="exam_score" name="exam_score" type="number" min="0" max="100" value="{{ old('exam_score', $a->exam_score) }}" class="a-input w-full" inputmode="numeric"></div>
                    <div><label class="a-label" for="exam_taken_on">Exam date</label><input id="exam_taken_on" name="exam_taken_on" type="date" value="{{ old('exam_taken_on', $a->exam_taken_on?->toDateString()) }}" class="a-input w-full"></div>
                    <div class="flex items-end">@if($a->exam_score !== null)<x-chip :tone="$a->examPassed() ? 'good' : 'critical'" :icon="$a->examPassed() ? 'check' : 'x'">{{ $a->examPassed() ? 'Passed' : 'Below pass mark' }}</x-chip>@endif</div>
                    <div><label class="a-label" for="drug_test_result">Drug test result</label><select id="drug_test_result" name="drug_test_result" class="a-input w-full"><option value="">Not tested</option>@foreach ($A::DRUG_RESULTS as $k => $l)<option value="{{ $k }}" @selected($a->drug_test_result === $k)>{{ $l }}</option>@endforeach</select></div>
                    <div><label class="a-label" for="drug_test_on">Drug test date</label><input id="drug_test_on" name="drug_test_on" type="date" value="{{ $a->drug_test_on?->toDateString() }}" class="a-input w-full"></div>
                    <div><label class="a-label" for="background_result">Background check</label><select id="background_result" name="background_result" class="a-input w-full"><option value="">Not started</option>@foreach ($A::BACKGROUND_RESULTS as $k => $l)<option value="{{ $k }}" @selected($a->background_result === $k)>{{ $l }}</option>@endforeach</select></div>
                    <div class="sm:col-span-2 lg:col-span-3"><button class="a-btn a-btn-primary">Save results</button> <span class="ml-2 text-sm text-ink-3">Results are internal; the applicant only sees stage changes.</span></div>
                </form>
            </section>

            {{-- Application answers --}}
            <section class="a-card p-5" aria-labelledby="answers-title">
                <h2 id="answers-title" class="font-semibold">Application</h2>
                <dl class="mt-4 grid gap-x-6 gap-y-3 text-[0.9375rem] sm:grid-cols-2">
                    @foreach ([
                        'Date of birth' => $a->date_of_birth ? $a->date_of_birth->format('j M Y').' (age '.$a->age().')' : '—',
                        'Lives' => $a->islandName().($a->settlement ? ', '.$a->settlement : ''),
                        'Right to work' => $A::CITIZENSHIP[$a->citizenship] ?? '—',
                        'Emergency contact' => trim($a->emergency_name.' · '.$a->emergency_phone, ' ·') ?: '—',
                        'Can work on' => implode(', ', array_map('island_name', $a->work_islands ?? [])) ?: '—',
                        'Hours' => $A::EMPLOYMENT[$a->employment_type] ?? '—',
                        'Shifts' => implode(', ', array_map(fn ($s) => $A::SHIFTS[$s] ?? $s, $a->shifts ?? [])) ?: '—',
                        'Available from' => $a->available_from?->format('j M Y') ?? 'Now',
                        'Driver’s licence' => $A::LICENCES[$a->drivers_licence] ?? '—',
                        'Licensed guard before' => $a->guard_licence ? 'Yes' : 'No',
                        'Firearm licence' => $a->firearm_licence ? 'Yes' : 'No',
                        'Security experience' => $a->experience_years.' '.str('year')->plural($a->experience_years),
                        'Last employer' => $a->last_employer ?: '—',
                        'Education' => $A::EDUCATION[$a->education] ?? '—',
                        'Certificates' => implode(', ', array_map(fn ($c) => $A::CERTIFICATIONS[$c] ?? $c, $a->certifications ?? [])) ?: '—',
                        'Source' => $A::SOURCES[$a->source] ?? $a->source,
                    ] as $k => $v)
                        <div><dt class="text-ink-3">{{ $k }}</dt><dd class="font-medium">{{ $v }}</dd></div>
                    @endforeach
                </dl>
                @if ($a->message)<div class="mt-4 rounded-lg bg-surface-2 p-4"><p class="text-sm text-ink-3">In their own words</p><p class="mt-1 whitespace-pre-line">{{ $a->message }}</p></div>@endif
            </section>

            {{-- Email --}}
            <section class="a-card p-5" x-data="composer(@js($templates->map->only(['id', 'name', 'subject', 'body', 'set_status'])->values()))" aria-labelledby="email-title">
                <h2 id="email-title" class="font-semibold">Email {{ $a->first_name }}</h2>
                <form method="POST" action="{{ route('admin.applications.message', $a) }}" class="mt-4 grid gap-4 lg:grid-cols-3">
                    @csrf
                    <div><label class="a-label" for="tpl">Template</label><select id="tpl" class="a-input w-full" @change="pick($event.target.value)"><option value="">Write your own…</option>@foreach ($templates as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach</select></div>
                    <div class="lg:col-span-2"><label class="a-label" for="subject">Subject</label><input id="subject" name="subject" x-model="subject" class="a-input w-full" required maxlength="200"></div>
                    <div class="lg:col-span-3"><label class="a-label" for="body">Message</label><textarea id="body" name="body" x-model="body" rows="7" class="a-input w-full" required></textarea><p class="mt-1 text-xs text-ink-3">{{ implode(' ', \App\Models\MessageTemplate::placeholders()) }}</p></div>
                    <div><label class="a-label" for="set_status">Then move to</label><select id="set_status" name="set_status" x-model="setStatus" class="a-input w-full"><option value="">Keep current stage</option>@foreach ($A::STAGES as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
                    <div class="flex items-end lg:col-span-2"><button class="a-btn a-btn-primary"><x-icon name="send" class="h-4 w-4" />Send email</button></div>
                </form>
            </section>

            {{-- Notes + timeline --}}
            <section class="a-card p-5" aria-labelledby="timeline-title">
                <h2 id="timeline-title" class="font-semibold">Notes &amp; timeline</h2>
                <form method="POST" action="{{ route('admin.applications.notes', $a) }}" class="mt-3 flex gap-2">
                    @csrf
                    <label for="note" class="sr-only">Add an internal note</label>
                    <input id="note" name="body" class="a-input flex-1" placeholder="Add an internal note (never shown to the applicant)" required maxlength="4000">
                    <button class="a-btn a-btn-ghost">Add</button>
                </form>
                <ol class="mt-5 space-y-4">
                    @foreach ($a->notes as $note)
                        <li class="flex gap-3">
                            <span class="mt-0.5 grid h-8 w-8 flex-none place-items-center rounded-full bg-surface-2 text-ink-2"><x-icon :name="['status' => 'route', 'email' => 'mail', 'booking' => 'calendar', 'document' => 'file', 'system' => 'dot'][$note->type] ?? 'pencil'" class="h-4 w-4" /></span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm text-ink-3">{{ $note->user?->name ?? ($note->type === 'system' || $note->type === 'booking' || $note->type === 'document' ? 'Applicant / system' : 'System') }} · {{ $note->created_at->format('j M Y, g:i a') }}@unless($note->is_public) · <span class="font-semibold">internal</span>@endunless</p>
                                @if ($note->type === 'email')<p class="font-semibold">{{ $note->meta['subject'] ?? 'Email' }}</p><p class="line-clamp-3 whitespace-pre-line text-ink-2">{{ $note->body }}</p>
                                @elseif ($note->type === 'status')<p><span class="font-semibold">Moved to {{ $A::STAGES[$note->meta['to'] ?? ''] ?? $note->body }}</span>@if(! empty($note->meta['comment'])) · {{ $note->meta['comment'] }}@endif</p>
                                @else<p class="whitespace-pre-line">{{ $note->body }}</p>@endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            </section>
        </div>

        <aside class="space-y-5">
            <section class="a-card p-5">
                <h2 class="font-semibold">Owner &amp; rating</h2>
                <form method="POST" action="{{ route('admin.applications.update', $a) }}" class="mt-3 space-y-3">
                    @csrf @method('PATCH')
                    <div><label class="a-label" for="assigned_to">Recruiter</label><select id="assigned_to" name="assigned_to" class="a-input w-full"><option value="">Unassigned</option>@foreach ($users as $u)<option value="{{ $u->id }}" @selected($a->assigned_to === $u->id)>{{ $u->name }}</option>@endforeach</select></div>
                    <div><label class="a-label" for="rating">Rating</label><select id="rating" name="rating" class="a-input w-full"><option value="0">Not rated</option>@for ($i = 1; $i <= 5; $i++)<option value="{{ $i }}" @selected($a->rating === $i)>{{ $i }} / 5</option>@endfor</select></div>
                    <button class="a-btn a-btn-ghost w-full">Save</button>
                </form>
            </section>
            <section class="a-card p-5">
                <h2 class="font-semibold">Screening bookings</h2>
                <ul class="mt-3 space-y-3 text-[0.9375rem]">
                    @forelse ($a->bookings as $b)
                        <li><p class="font-semibold">{{ $b->slot->kindLabel() }}</p><p class="text-ink-2">{{ $b->slot->starts_at->format('D j M Y, g:i a') }} · {{ $b->slot->location }}</p></li>
                    @empty
                        <li class="text-ink-3">None yet. Applicants book from their portal when they reach the exam, drug test or interview stage.</li>
                    @endforelse
                </ul>
            </section>
            <section class="a-card p-5 text-[0.9375rem]">
                <h2 class="font-semibold">Applicant account</h2>
                <p class="mt-2">{{ $a->applicant->full_name }} · {{ $a->applicant->email }}</p>
                <p class="text-ink-3">Last portal sign-in: {{ $a->applicant->last_login_at?->diffForHumans() ?? 'never since applying' }}</p>
                @if ($others->isNotEmpty())
                    <h3 class="mt-4 font-semibold">Other applications</h3>
                    <ul class="mt-2 space-y-1">@foreach ($others as $o)<li><a class="text-link" href="{{ route('admin.applications.show', $o) }}">{{ $o->roleTitle() }}</a> · {{ $o->stageLabel() }}</li>@endforeach</ul>
                @endif
            </section>
        </aside>
    </div>
</x-layouts.admin>
