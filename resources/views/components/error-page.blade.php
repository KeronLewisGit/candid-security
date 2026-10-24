@props(['code'])
@php
    [$title, $body] = match ($code) {
        '404' => ['Page not found', 'That page has moved or no longer exists. The old website’s addresses have changed.'],
        '403' => ['Not allowed', 'You do not have access to this page.'],
        '419' => ['Page expired', 'The form was open too long. Go back, refresh the page and try again.'],
        '429' => ['Too many attempts', 'Please wait a minute and try again.'],
        '503' => ['Back shortly', 'The site is being updated. Please try again in a few minutes.'],
        default => ['Something went wrong', 'Please try again. If it keeps happening, call the office.'],
    };
@endphp
<x-layouts.site :title="$title" :mobile-bar="false" :noindex="true">
    <x-page-header :title="$title" :lead="$body" eyebrow="Error" :code="$code" />
    <section class="wrap mt-12 flex flex-wrap gap-3">
        <a href="{{ route('home') }}" class="btn btn-primary">Home page</a>
        @if ($code === '404')<a href="{{ route('careers') }}" class="btn btn-outline">Careers</a><a href="{{ route('services') }}" class="btn btn-outline">Services</a>@endif
        @if ($phone = setting('freeport_phone'))<a href="{{ tel_href($phone) }}" class="btn btn-outline"><x-icon name="phone" class="h-4 w-4" />{{ format_phone($phone) }}</a>@endif
    </section>
</x-layouts.site>
