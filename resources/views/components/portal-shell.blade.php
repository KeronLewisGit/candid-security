@props(['title', 'lead' => null, 'code' => null])
@php
    $me = auth('applicant')->user();
    $unread = $me ? $me->unreadCount() : 0;
    $tabs = [['portal.dashboard', 'My applications', 'briefcase', ['portal.dashboard', 'portal.applications.*']], ['portal.inbox', 'Messages', 'bell', 'portal.inbox'], ['portal.profile', 'Profile', 'user', 'portal.profile']];
@endphp
<x-layouts.site :title="$title.' · Applicant portal'" :mobile-bar="false" :noindex="true">
    <x-page-header :title="$title" eyebrow="Applicant portal" :code="$code ?? ($me ? initials($me->full_name) : null)" :lead="$lead">
        <nav class="mt-8 flex flex-wrap gap-2" aria-label="Applicant portal">
            @foreach ($tabs as [$route, $label, $icon, $match])
                @php($on = request()->routeIs($match))
                <a href="{{ route($route) }}" @class(['inline-flex min-h-11 items-center gap-2 rounded-full px-4 font-semibold ring-1', 'bg-aqua text-aqua-ink ring-aqua' => $on, 'text-white ring-white/30 hover:ring-white' => ! $on]) @if($on) aria-current="page" @endif>
                    <x-icon :name="$icon" class="h-4 w-4" />{{ $label }}@if($icon === 'bell' && $unread)<span class="num rounded-full bg-gold px-1.5 text-xs font-bold text-char-950">{{ $unread }}</span>@endif
                </a>
            @endforeach
            <form method="POST" action="{{ route('portal.logout') }}">@csrf<button class="inline-flex min-h-11 items-center gap-2 rounded-full px-4 font-semibold text-white/80 hover:text-white"><x-icon name="log-out" class="h-4 w-4" />Sign out</button></form>
        </nav>
    </x-page-header>
    <div class="wrap mt-10">
        @if (session('status'))
            <p class="mb-6 flex items-start gap-3 rounded-lg bg-aqua-soft px-5 py-4 font-medium" role="status"><x-icon name="check-circle" class="mt-0.5 h-5 w-5 flex-none text-aqua-text" />{{ session('status') }}</p>
        @endif
        @include('pages._form-errors')
        <div class="{{ $errors->any() ? 'mt-6' : '' }}">{{ $slot }}</div>
    </div>
</x-layouts.site>
