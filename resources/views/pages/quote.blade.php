@php($M = \App\Models\ServiceRequest::class)
<x-layouts.site title="Request a quote" description="Request a quote for security officers, mobile patrol, aviation security or lifeguards from Candid Security in Freeport and Nassau.">
    <x-page-header title="Request a guard-service quote." eyebrow="Quote request" code="RFP" photo="cctv" lead="Tell us about the site and the cover you need. Operations will contact you to talk it through, and arrange a site visit if needed." />

    <div class="wrap mt-12 grid gap-10 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <form method="POST" action="{{ route('requests.store', 'quote') }}" class="relative space-y-8" novalidate>
            @csrf
            @include('pages._form-errors')
            <section class="panel space-y-6 p-5 sm:p-8">
                <h2 class="display-3"><span class="badge-id mr-2 align-middle text-ink-3">01</span>The site</h2>
                <x-field name="service" label="Service" :options="collect(candid('services'))->except('armored-transport')->map(fn ($s) => $s['name'])->all()" :value="$service ?: 'security-officers'" required />
                <x-choices name="site_type" type="radio" label="Type of site" :options="$M::SITE_TYPES" required />
                <x-field name="site_address" label="Site address or area" hint="Street, settlement or development name." />
            </section>
            <section class="panel space-y-6 p-5 sm:p-8">
                <h2 class="display-3"><span class="badge-id mr-2 align-middle text-ink-3">02</span>The cover</h2>
                <x-choices name="coverage" type="radio" label="Armed or unarmed" :options="$M::COVERAGE" required />
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-field name="posts" type="number" label="Number of posts (officers on duty at once)" :value="1" required min="1" max="200" inputmode="numeric" />
                    <x-field name="days" type="number" label="Days per week" min="1" max="7" inputmode="numeric" />
                </div>
                <x-choices name="hours" type="radio" label="Hours" :options="$M::HOURS" required />
                <x-choices name="duration" type="radio" label="How long" :options="$M::DURATION" required cols="" />
                <x-field name="needed_on" type="date" label="Start date" />
                <x-field name="message" type="textarea" label="Anything else" hint="Concerns, past incidents, special duties, events." maxlength="3000" />
            </section>
            <section class="panel space-y-6 p-5 sm:p-8">
                <h2 class="display-3"><span class="badge-id mr-2 align-middle text-ink-3">03</span>Your details</h2>
                @include('pages._contact-fields', ['requireCompany' => false])
                <p class="text-sm text-ink-3">We use these details only to reply to this request. See the <a href="{{ route('privacy') }}" class="text-link">privacy notice</a>.</p>
                <button class="btn btn-primary w-full sm:w-auto">Send quote request<x-icon name="send" class="nudge h-4 w-4" /></button>
            </section>
        </form>
        @include('pages._offices-aside')
    </div>
</x-layouts.site>
