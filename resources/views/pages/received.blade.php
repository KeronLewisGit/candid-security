@php
    $copy = [
        'quote' => ['Quote request received.', 'Operations will contact you to talk through the site and the cover you need.'],
        'pickup' => ['Pickup request received.', 'Operations will call to confirm the time window before a crew is dispatched. A pickup is not booked until it is confirmed by phone.'],
        'contact' => ['Message received.', 'Thank you. The right person at Candid will reply by email or phone.'],
    ][$kind] ?? ['Request received.', ''];
@endphp
<x-layouts.site :title="$copy[0]" :noindex="true">
    <x-page-header :title="$copy[0]" eyebrow="Reference" :code="$reference" :lead="$copy[1]" />
    <section class="wrap mt-12 max-w-3xl">
        <div class="panel p-6 md:p-8">
            <p class="text-ink-2">Quote reference <strong class="mono text-ink">{{ $reference }}</strong> if you call us. Urgent? Call the office now:</p>
            <div class="mt-5 flex flex-wrap gap-3">
                @foreach (['Freeport' => setting('freeport_phone'), 'Nassau' => setting('nassau_phone')] as $label => $phone)
                    @if ($phone)<a href="{{ tel_href($phone) }}" class="btn btn-outline"><x-icon name="phone" class="h-4 w-4" />{{ $label }} {{ format_phone($phone) }}</a>@endif
                @endforeach
            </div>
        </div>
        <a href="{{ route('home') }}" class="btn btn-primary mt-8">Back to the home page</a>
    </section>
</x-layouts.site>
