<x-layouts.site title="Contact" description="Contact Candid Security: Freeport head office {{ format_phone(setting('freeport_phone')) }}, Nassau office {{ format_phone(setting('nassau_phone')) }}.">
    <x-page-header title="Talk to Candid." eyebrow="Contact" code="GB · NP" photo="freeport" lead="Call the office nearest you, or send a message and the right person will reply." />

    <div class="wrap mt-12 grid gap-10 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <div class="space-y-8">
            <div class="grid gap-4 sm:grid-cols-3">
                <a href="{{ route('quote') }}" class="panel card-link p-5"><x-icon name="badge" class="h-7 w-7 text-aqua-text" /><p class="mt-3 font-semibold">Officers for a site</p><p class="text-sm text-ink-3">Request a quote</p></a>
                <a href="{{ route('pickup') }}" class="panel card-link p-5"><x-icon name="truck" class="h-7 w-7 text-aqua-text" /><p class="mt-3 font-semibold">Armoured pickup</p><p class="text-sm text-ink-3">Book a pickup</p></a>
                <a href="{{ route('careers') }}" class="panel card-link p-5"><x-icon name="briefcase" class="h-7 w-7 text-aqua-text" /><p class="mt-3 font-semibold">Jobs</p><p class="text-sm text-ink-3">Apply online</p></a>
            </div>
            <form id="message" method="POST" action="{{ route('requests.store', 'contact') }}" class="panel relative space-y-6 p-5 sm:p-8" novalidate>
                @csrf
                <h2 class="display-3">Send a message</h2>
                @include('pages._form-errors')
                @include('pages._contact-fields', ['requireCompany' => false, 'requirePhone' => false, 'requireIsland' => false])
                <x-field name="topic" label="Topic" :options="['general' => 'General question', 'billing' => 'Accounts & billing', 'complaint' => 'Feedback about an officer or service', 'other' => 'Something else']" />
                <x-field name="message" type="textarea" label="Message" required maxlength="3000" />
                <button class="btn btn-primary">Send message<x-icon name="send" class="nudge h-4 w-4" /></button>
            </form>
            <div class="panel p-5 sm:p-8">
                <h2 class="display-3">Email</h2>
                <dl class="mt-4 grid gap-x-3 sm:grid-cols-[12rem_1fr] sm:items-center">
                    @if ($e = setting('general_email'))<dt class="text-ink-3">Office of the President</dt><dd class="mb-2 sm:mb-0"><a class="text-link inline-flex min-h-11 items-center break-all" href="mailto:{{ $e }}">{{ $e }}</a></dd>@endif
                    @if ($e = setting('hr_email'))<dt class="text-ink-3">Recruitment &amp; HR</dt><dd class="mb-2 sm:mb-0"><a class="text-link inline-flex min-h-11 items-center break-all" href="mailto:{{ $e }}">{{ $e }}</a></dd>@endif
                </dl>
            </div>
        </div>
        @include('pages._offices-aside')
    </div>
</x-layouts.site>
