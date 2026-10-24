@php($M = \App\Models\ServiceRequest::class)
<x-layouts.site title="Armoured pickup request" description="Request a one-off or regular armoured cash-in-transit pickup from Candid Security on Grand Bahama or New Providence.">
    <x-page-header title="Request an armoured pickup." eyebrow="Armoured transport" code="CIT" photo="armored-truck" lead="Book a cash or valuables pickup from your business to the bank. Operations confirms the time window by phone before the crew is dispatched." />

    <div class="wrap mt-12 grid gap-10 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <form method="POST" action="{{ route('requests.store', 'pickup') }}" class="relative space-y-8" novalidate>
            @csrf
            @include('pages._form-errors')
            <p class="flex items-start gap-3 rounded-lg bg-surface-2 px-5 py-4 text-[0.9375rem]"><x-icon name="lock" class="mt-0.5 h-5 w-5 flex-none text-aqua-text" />For your safety, do not enter cash amounts or account numbers here. Operations confirms those details by phone.</p>
            <section class="panel space-y-6 p-5 sm:p-8">
                <h2 class="display-3"><span class="badge-id mr-2 align-middle text-ink-3">01</span>The pickup</h2>
                <x-field name="pickup_address" label="Pickup address" required autocomplete="street-address" />
                <x-field name="destination" label="Deliver to" required hint="Bank and branch, or another destination." />
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-field name="needed_on" type="date" label="Pickup date" required :min="today()->toDateString()" />
                    <x-field name="window" label="Time window" :options="$M::WINDOWS" required />
                </div>
                <x-choices name="frequency" type="radio" label="How often" :options="$M::FREQUENCY" required />
                <x-choices name="existing_client" type="radio" label="Are you an existing Candid armoured client?" :options="['yes' => 'Yes', 'no' => 'No, this is new']" required />
                <x-field name="message" type="textarea" label="Notes for operations" hint="Access instructions, contact on site." maxlength="3000" />
            </section>
            <section class="panel space-y-6 p-5 sm:p-8">
                <h2 class="display-3"><span class="badge-id mr-2 align-middle text-ink-3">02</span>Your business</h2>
                @include('pages._contact-fields', ['requireCompany' => true])
                <button class="btn btn-primary w-full sm:w-auto">Send pickup request<x-icon name="send" class="nudge h-4 w-4" /></button>
            </section>
        </form>
        @include('pages._offices-aside')
    </div>
</x-layouts.site>
