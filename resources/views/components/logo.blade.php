@props(['tone' => 'auto'])
{{-- Candid's own round badge, cut from the candidbahamas.com header (the largest copy online, 143 px). Ask Candid for a vector file before launch. --}}
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    <img src="{{ asset('img/brand/candid-badge.png') }}" width="143" height="143" alt="" class="h-11 w-11 flex-none">
    <span class="leading-none {{ $tone === 'light' ? 'text-white' : 'text-ink' }}">
        <span class="font-display block text-[1.45rem] font-extrabold uppercase tracking-[0.02em]" style="font-variation-settings:'wdth' 125">Candid</span>
        <span class="mono-label mt-1 block whitespace-nowrap text-[0.6rem] tracking-[0.18em] {{ $tone === 'light' ? 'text-white/70' : 'text-ink-3' }}">Security · Bahamas</span>
    </span>
</span>
