@php
    $a = $application;
    $isGuest = $guest;
    $action = $isGuest ? route('apply.begin') : route('apply.save', [$a->reference, $step]);
    $islands = collect(candid('islands'))->map(fn ($i) => $i['name'])->all();
    $docs = $a?->documents ?? collect();
@endphp
<x-layouts.site :title="'Apply · '.$steps[$step]" :mobile-bar="false" :noindex="true">
    <x-page-header :title="$job ? 'Apply: '.$job->title : 'Apply to Candid Security'" :eyebrow="'Step '.$step.' of '.count($steps).' · '.$steps[$step]" :code="$a?->reference ?? 'NEW'" :lead="$job ? $job->islandName().' · '.$job->typeLabel() : 'A general application for any position. We will match you to openings on your islands.'" />

    <div class="wrap mt-10 max-w-3xl">
        <ol class="tracker" aria-label="Application progress">
            @foreach ($steps as $n => $label)
                @php($state = $n < $step ? 'is-done' : ($n === $step ? 'is-current' : ''))
                <li class="{{ $state }}" @if($n === $step) aria-current="step" @endif>
                    @if (! $isGuest && $n <= $a->step && $n !== $step)
                        <a href="{{ route('apply.step', [$a->reference, $n]) }}" class="flex flex-col items-center gap-2 rounded"><span class="checkpoint is-light {{ $n < $step || $n <= $a->step ? 'is-done' : '' }}"></span><span class="hidden sm:block">{{ $label }}</span></a>
                    @else
                        <span class="checkpoint is-light {{ $n < $step ? 'is-done' : ($n === $step ? '' : 'is-todo') }}"></span><span class="hidden sm:block">{{ $label }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
        <p class="mt-3 text-center text-sm font-semibold sm:hidden">{{ $steps[$step] }}</p>

        @if (session('status'))
            <p class="mt-8 flex items-start gap-3 rounded-lg bg-aqua-soft px-5 py-4 font-medium" role="status"><x-icon name="check-circle" class="mt-0.5 h-5 w-5 flex-none text-aqua-text" />{{ session('status') }}</p>
        @endif
        @if ($errors->any())
            <div class="mt-8 rounded-lg border-2 border-[color:var(--critical)] bg-surface px-5 py-4" role="alert" tabindex="-1" id="error-summary">
                <p class="flex items-center gap-2 font-semibold"><x-icon name="alert" class="h-5 w-5 text-[color:var(--critical)]" />Please check {{ $errors->count() === 1 ? 'one answer' : $errors->count().' answers' }}</p>
                <ul class="mt-2 list-disc space-y-1 pl-10 text-[0.9375rem]">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="panel mt-8 space-y-7 p-5 sm:p-8" novalidate>
            @csrf
            {{-- Enter in a text field presses the first submit button, so the default "continue" comes first. --}}
            <button type="submit" name="action" value="next" class="sr-only" tabindex="-1" aria-hidden="true">Continue</button>

            @if ($step === 1)
                <div>
                    <h2 class="display-3">About you</h2>
                    <p class="mt-1 text-ink-2">{{ $isGuest ? 'This also creates your applicant account, so you can stop at any point and come back.' : 'Check these details are still right.' }}</p>
                    @if ($isGuest)<p class="mt-2 text-[0.9375rem]">Already started? <a href="{{ route('portal.login') }}" class="text-link">Sign in to continue</a>.</p>@endif
                </div>
                @if ($isGuest && $job)<input type="hidden" name="job" value="{{ $job->slug }}">@endif
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-field name="first_name" label="First name" :value="$a?->first_name" required autocomplete="given-name" />
                    <x-field name="last_name" label="Last name" :value="$a?->last_name" required autocomplete="family-name" />
                    @if ($isGuest)
                        <x-field name="email" type="email" label="Email" required autocomplete="email" hint="We send updates about your application here." class="sm:col-span-2" />
                    @endif
                    <x-field name="phone" type="tel" label="Mobile phone" :value="$a?->phone" required autocomplete="tel" placeholder="242-555-0100" />
                    <x-field name="date_of_birth" type="date" label="Date of birth" :value="$a?->date_of_birth?->toDateString()" required autocomplete="bday" />
                    <x-field name="island" label="Island you live on" :options="$islands" :value="$a?->island" required />
                    <x-field name="settlement" label="Town or settlement" :value="$a?->settlement" required autocomplete="address-level2" placeholder="e.g. Eight Mile Rock" />
                </div>
                <x-choices name="citizenship" type="radio" label="Right to work in The Bahamas" :options="\App\Models\Application::CITIZENSHIP" :value="$a?->citizenship" required />
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-field name="emergency_name" label="Emergency contact name" :value="$a?->emergency_name" required />
                    <x-field name="emergency_phone" type="tel" label="Emergency contact phone" :value="$a?->emergency_phone" required />
                </div>
                @if ($isGuest)
                    <div class="grid gap-5 border-t border-line pt-7 sm:grid-cols-2">
                        <x-field name="password" type="password" label="Create a password" required autocomplete="new-password" hint="At least 8 characters." />
                        <x-field name="password_confirmation" type="password" label="Type it again" required autocomplete="new-password" />
                    </div>
                @endif
            @elseif ($step === 2)
                <div>
                    <h2 class="display-3">Position &amp; availability</h2>
                    <p class="mt-1 text-ink-2">Choose an opening, or a type of work for a general application.</p>
                </div>
                <x-field name="job_posting_id" label="Opening" :options="$jobs->mapWithKeys(fn ($j) => [$j->id => $j->title.' · '.$j->islandName()])->all()" :value="$a->job_posting_id" hint="Leave as “Choose…” for a general application." />
                <x-field name="position" label="Type of work (general application)" :options="\App\Models\Application::POSITIONS" :value="$a->job_posting_id ? null : $a->position" hint="Only needed when no opening is chosen above." />
                <x-choices name="work_islands" label="Islands you can work on" :options="$islands" :value="$a->work_islands ?? array_filter([$a->island])" required />
                <x-choices name="employment_type" type="radio" label="Hours" :options="\App\Models\Application::EMPLOYMENT" :value="$a->employment_type" required cols="sm:grid-cols-3" />
                <x-choices name="shifts" label="Shifts you can work" :options="\App\Models\Application::SHIFTS" :value="$a->shifts ?? []" required />
                <x-field name="available_from" type="date" label="Earliest start date" :value="$a->available_from?->toDateString()" />
            @elseif ($step === 3)
                <div>
                    <h2 class="display-3">Licences &amp; experience</h2>
                    <p class="mt-1 text-ink-2">No experience is needed for most officer roles. Candid trains every new officer.</p>
                </div>
                <x-choices name="drivers_licence" type="radio" label="Driver’s licence" :options="\App\Models\Application::LICENCES" :value="$a->drivers_licence" required cols="" />
                <fieldset>
                    <legend class="label">Security licences</legend>
                    <div class="grid gap-2.5 sm:grid-cols-2">
                        <label class="choice"><input type="checkbox" name="guard_licence" value="1" @checked(old('guard_licence', $a->guard_licence))><span>I have worked as a licensed security officer</span></label>
                        <label class="choice"><input type="checkbox" name="firearm_licence" value="1" @checked(old('firearm_licence', $a->firearm_licence))><span>I hold a firearm licence</span></label>
                    </div>
                </fieldset>
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-field name="experience_years" type="number" label="Years of security experience" :value="$a->experience_years ?? 0" required min="0" max="50" inputmode="numeric" />
                    <x-field name="last_employer" label="Most recent employer" :value="$a->last_employer" autocomplete="organization" />
                </div>
                <x-field name="education" label="Education" :options="\App\Models\Application::EDUCATION" :value="$a->education" required />
                <x-choices name="certifications" label="Certificates and training" :options="\App\Models\Application::CERTIFICATIONS" :value="$a->certifications ?? []" />
                <x-field name="message" type="textarea" label="Anything else we should know?" :value="$a->message" hint="For example: why you want to be a security officer, or languages you speak." maxlength="2000" />
            @elseif ($step === 4)
                <div>
                    <h2 class="display-3">Documents</h2>
                    <p class="mt-1 text-ink-2">Photos from your phone are fine (PDF, JPG, PNG, HEIC or Word, up to {{ \App\Models\ApplicationDocument::MAX_KB / 1024 }} MB each). Anything you skip now can be uploaded later from your applicant portal.</p>
                </div>
                @foreach (\App\Models\ApplicationDocument::TYPES as $type => $label)
                    @php($have = $docs->where('type', $type)->first())
                    <div>
                        <label for="f-{{ $type }}" class="label">{{ $label }}@if(in_array($type, \App\Models\Application::REQUIRED_DOCUMENTS, true)) <span class="opt">(needed before the document check)</span>@else <span class="opt">(optional)</span>@endif</label>
                        @if ($have)
                            <p class="mb-2 flex items-center gap-2 text-[0.9375rem] text-ink-2"><x-icon name="check-circle" class="h-4 w-4 text-aqua-text" />Uploaded: {{ $have->original_name }} ({{ $have->sizeLabel() }}). Choose a new file to replace it.</p>
                        @endif
                        <input id="f-{{ $type }}" name="{{ $type }}" type="file" class="input" accept=".pdf,.jpg,.jpeg,.png,.webp,.heic,.doc,.docx,image/*,application/pdf" aria-describedby="f-{{ $type }}-hint" @error($type) aria-invalid="true" @enderror>
                        <span class="hint" id="f-{{ $type }}-hint">{{ \App\Models\ApplicationDocument::HINTS[$type] }}</span>
                        <x-error :for="$type" />
                    </div>
                @endforeach
            @else
                <div>
                    <h2 class="display-3">Review &amp; send</h2>
                    <p class="mt-1 text-ink-2">Check your answers. Use the steps above to change anything.</p>
                </div>
                <dl class="divide-y divide-line rounded-lg border border-line">
                    @foreach ([
                        'Name' => $a->full_name, 'Phone' => $a->phone, 'Email' => $a->email, 'Date of birth' => $a->date_of_birth?->format('j F Y'),
                        'Lives on' => $a->islandName().($a->settlement ? ', '.$a->settlement : ''), 'Emergency contact' => trim($a->emergency_name.' · '.$a->emergency_phone, ' ·'),
                        'Applying for' => $a->roleTitle(), 'Can work on' => implode(', ', array_map('island_name', $a->work_islands ?? [])),
                        'Shifts' => implode(', ', array_map(fn ($s) => \App\Models\Application::SHIFTS[$s] ?? $s, $a->shifts ?? [])),
                        'Driver’s licence' => \App\Models\Application::LICENCES[$a->drivers_licence] ?? '—', 'Experience' => $a->experience_years.' '.str('year')->plural($a->experience_years),
                        'Education' => \App\Models\Application::EDUCATION[$a->education] ?? '—',
                        'Documents' => $docs->isEmpty() ? 'None yet' : $docs->map(fn ($d) => $d->typeLabel())->implode(', '),
                    ] as $k => $v)
                        <div class="grid gap-1 px-4 py-3 sm:grid-cols-[11rem_1fr]"><dt class="text-ink-3">{{ $k }}</dt><dd class="font-medium">{{ $v ?: '—' }}</dd></div>
                    @endforeach
                </dl>
                @if ($missing = $a->missingDocuments())
                    <p class="flex items-start gap-3 rounded-lg bg-surface-2 px-4 py-3 text-[0.9375rem]"><x-icon name="alert" class="mt-0.5 h-5 w-5 flex-none text-[color:var(--attention)]" /><span>You can send now, but we still need your {{ implode(' and ', array_map(fn ($t) => strtolower(\App\Models\ApplicationDocument::TYPES[$t]), $missing)) }} before the document check. <a class="text-link" href="{{ route('apply.step', [$a->reference, 4]) }}">Upload now</a> or later from your portal.</span></p>
                @endif
                <p class="text-[0.9375rem] text-ink-2">New officers complete Candid training, which ends with an exam. The pass mark is {{ setting('exam_pass_mark', '70') }}%.</p>
                <div class="space-y-3">
                    <label class="choice"><input type="checkbox" name="declaration" value="1" required @checked(old('declaration'))><span>The information I have given is true and complete.</span></label>
                    <x-error for="declaration" />
                    <label class="choice"><input type="checkbox" name="consent" value="1" required @checked(old('consent'))><span>I agree to a document check, entrance exam, drug test and background check, and to Candid storing my application as described in the <a href="{{ route('privacy') }}" class="text-link" target="_blank">privacy notice</a>.</span></label>
                    <x-error for="consent" />
                </div>
            @endif

            <div class="flex flex-col-reverse gap-3 border-t border-line pt-6 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-wrap gap-3">
                    @if (! $isGuest && $step > 1)
                        <a href="{{ route('apply.step', [$a->reference, $step - 1]) }}" class="btn btn-outline"><x-icon name="arrow-left" class="h-4 w-4" />Back</a>
                    @endif
                    @if (! $isGuest && $step < 5)
                        <button type="submit" name="action" value="exit" class="btn px-3 font-semibold text-link underline underline-offset-4">Save &amp; finish later</button>
                    @endif
                </div>
                <button type="submit" name="action" value="next" class="btn btn-primary">{{ $step === 5 ? 'Send application' : ($isGuest ? 'Create account & continue' : 'Save & continue') }}<x-icon :name="$step === 5 ? 'send' : 'arrow-right'" class="nudge h-4 w-4" /></button>
            </div>
        </form>
        <p class="mt-6 text-center text-sm text-ink-3">Trouble applying? Call Freeport <a class="text-link" href="{{ tel_href(setting('freeport_phone')) }}">{{ format_phone(setting('freeport_phone')) }}</a> or email <a class="text-link" href="mailto:{{ setting('hr_email') }}">{{ setting('hr_email') }}</a>.</p>
    </div>
</x-layouts.site>
