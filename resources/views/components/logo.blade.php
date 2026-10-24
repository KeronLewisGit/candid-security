@props(['tone' => 'auto'])
{{-- Mark: a redraw of Candid's round badge (blue field, five stars) in the site palette: aquamarine disc, charcoal ring and C, gold stars. --}}
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    <svg viewBox="0 0 44 44" class="h-10 w-10 flex-none" aria-hidden="true">
        <circle cx="22" cy="22" r="21.5" fill="var(--aqua)"/>
        <circle cx="22" cy="22" r="15.6" fill="none" stroke="var(--char-900)" stroke-width="1.4"/>
        <path d="M29.4 16.9a9 9 0 1 0 0 10.2l-3.7-2.6a4.5 4.5 0 1 1 0-5z" fill="var(--char-900)"/>
        <path fill="var(--gold)" stroke="var(--char-900)" stroke-width=".5" d="M5.89 10.40L6.48 11.89L8.08 11.99L6.84 13.01L7.24 14.56L5.89 13.70L4.54 14.56L4.94 13.01L3.70 11.99L5.30 11.89zM12.70 3.59L13.29 5.08L14.89 5.18L13.65 6.20L14.05 7.75L12.70 6.89L11.35 7.75L11.75 6.20L10.51 5.18L12.11 5.08zM22.00 1.10L22.59 2.59L24.19 2.69L22.95 3.71L23.35 5.26L22.00 4.40L20.65 5.26L21.05 3.71L19.81 2.69L21.41 2.59zM31.30 3.59L31.89 5.08L33.49 5.18L32.25 6.20L32.65 7.75L31.30 6.89L29.95 7.75L30.35 6.20L29.11 5.18L30.71 5.08zM38.11 10.40L38.70 11.89L40.30 11.99L39.06 13.01L39.46 14.56L38.11 13.70L36.76 14.56L37.16 13.01L35.92 11.99L37.52 11.89z"/>
    </svg>
    <span class="leading-none {{ $tone === 'light' ? 'text-white' : 'text-ink' }}">
        <span class="font-display block text-[1.45rem] font-extrabold uppercase tracking-[0.02em]" style="font-variation-settings:'wdth' 125">Candid</span>
        <span class="mono-label mt-1 block whitespace-nowrap text-[0.6rem] tracking-[0.18em] {{ $tone === 'light' ? 'text-white/70' : 'text-ink-3' }}">Security · Bahamas</span>
    </span>
</span>
