@props(['title', 'lead' => null])
<x-layouts.site :title="$title" :mobile-bar="false" :noindex="true">
    <x-page-header :title="$title" eyebrow="Applicant portal" code="CSL" :lead="$lead" />
    <div class="wrap mt-10 max-w-xl">
        @if (session('status'))
            <p class="mb-6 flex items-start gap-3 rounded-lg bg-aqua-soft px-5 py-4 font-medium" role="status"><x-icon name="check-circle" class="mt-0.5 h-5 w-5 flex-none text-aqua-text" />{{ session('status') }}</p>
        @endif
        <div class="panel p-6 sm:p-8">{{ $slot }}</div>
    </div>
</x-layouts.site>
