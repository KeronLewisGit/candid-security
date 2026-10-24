@props(['title' => null, 'description' => null, 'jsonLd' => [], 'mobileBar' => true, 'ogImage' => 'security-guard', 'noindex' => false])
@php
    $pageTitle = $title ? $title.' · '.candid('short_name') : candid('name').' · Security officers, armoured transport and patrols in The Bahamas';
    $metaDescription = $description ?? 'Candid Security Ltd. has provided armed and unarmed security officers, armoured transport, mobile patrol, aviation security and lifeguards across The Bahamas since 1997. Offices in Freeport and Nassau.';
    $freeport = setting('freeport_phone');
    $nassau = setting('nassau_phone');
    $nav = [
        ['route' => 'services', 'match' => 'services*', 'label' => 'Services'],
        ['route' => 'about', 'match' => 'about', 'label' => 'About'],
        ['route' => 'locations', 'match' => 'locations', 'label' => 'Locations'],
        ['route' => 'careers', 'match' => ['careers*', 'apply*'], 'label' => 'Careers'],
        ['route' => 'contact', 'match' => ['contact', 'pickup'], 'label' => 'Contact'],
    ];
    $organization = [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => candid('name'),
        'url' => route('home'),
        'logo' => asset('img/brand/candid-badge.png'),
        'foundingDate' => (string) candid('founded'),
        'email' => setting('general_email') ?: null,
        'areaServed' => ['Grand Bahama', 'New Providence', 'Abaco', 'Bimini'],
        'location' => array_values(array_filter([
            $freeport ? ['@type' => 'Place', 'name' => 'Freeport office', 'telephone' => '+'.us_digits($freeport), 'address' => ['@type' => 'PostalAddress', 'addressLocality' => 'Freeport', 'addressRegion' => 'Grand Bahama', 'addressCountry' => 'BS']] : null,
            $nassau ? ['@type' => 'Place', 'name' => 'Nassau office', 'telephone' => '+'.us_digits($nassau), 'address' => ['@type' => 'PostalAddress', 'addressLocality' => 'Nassau', 'addressRegion' => 'New Providence', 'addressCountry' => 'BS']] : null,
        ])),
    ];
    $portalUser = auth('applicant')->user();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head')
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    @if (candid('preview') || $noindex)
        <meta name="robots" content="noindex, nofollow">
    @endif
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ candid('name') }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('img/'.$ogImage.'-1920.webp') }}">
    <meta property="og:locale" content="en_BS">
    <meta name="twitter:card" content="summary_large_image">
    @foreach (array_merge([$organization], $jsonLd) as $ld)
        <script type="application/ld+json">{!! json_encode(array_filter($ld, fn ($v) => $v !== null), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    @endforeach
</head>
<body class="min-h-screen {{ $mobileBar ? 'pb-24 lg:pb-0' : '' }}" x-data="{ menu: false }" @keydown.escape.window="menu = false">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded focus:bg-surface focus:px-4 focus:py-3 focus:shadow-md">Skip to content</a>

    @if (candid('preview'))
        <div class="bg-gold text-char-950 text-sm">
            <p class="wrap flex items-start gap-2 py-2.5"><x-icon name="alert" class="mt-0.5 h-4 w-4 flex-none" /><span><strong class="font-bold">Concept preview.</strong> This is a proposed redesign, not the official Candid Security website.</span></p>
        </div>
    @endif

    {{-- Utility strip: both offices one tap away on desktop. --}}
    <div class="hidden bg-char-950 text-sm text-white/75 lg:block">
        <div class="wrap flex h-10 items-center gap-6">
            @if ($freeport)<a href="{{ tel_href($freeport) }}" class="inline-flex items-center gap-2 hover:text-white"><span class="mono-label text-white/55">Freeport</span><span class="num font-semibold text-white">{{ format_phone($freeport) }}</span></a>@endif
            @if ($nassau)<a href="{{ tel_href($nassau) }}" class="inline-flex items-center gap-2 hover:text-white"><span class="mono-label text-white/55">Nassau</span><span class="num font-semibold text-white">{{ format_phone($nassau) }}</span></a>@endif
            <span class="ml-auto inline-flex items-center gap-5">
                <a href="{{ route('pickup') }}" class="inline-flex items-center gap-1.5 hover:text-white"><x-icon name="truck" class="h-4 w-4" />Armoured pickup</a>
                <a href="{{ $portalUser ? route('portal.dashboard') : route('portal.login') }}" class="inline-flex items-center gap-1.5 hover:text-white"><x-icon name="user" class="h-4 w-4" />{{ $portalUser ? 'My applications' : 'Applicant portal' }}</a>
                <button type="button" onclick="toggleTheme()" class="inline-flex h-8 w-8 items-center justify-center rounded hover:bg-white/10 hover:text-white" aria-label="Switch light or dark theme"><x-icon name="moon" class="h-4 w-4 dark-hide" /><x-icon name="sun" class="hidden h-4 w-4 dark-show" /></button>
            </span>
        </div>
    </div>

    <header class="sticky top-0 z-40 bg-char-900 text-white">
        <div class="wrap flex h-[4.5rem] items-center gap-2 min-[400px]:gap-3">
            <a href="{{ route('home') }}" class="mr-auto flex-none rounded" aria-label="{{ candid('name') }}, home"><x-logo tone="light" /></a>

            <nav class="hidden items-center lg:flex" aria-label="Main">
                @foreach ($nav as $item)
                    @php($current = request()->routeIs($item['match']))
                    <a href="{{ route($item['route']) }}" @class(['relative whitespace-nowrap px-3 py-2.5 font-medium transition-colors xl:px-4', 'text-white' => $current, 'text-white/75 hover:text-white' => ! $current]) @if($current) aria-current="page" @endif>
                        {{ $item['label'] }}
                        @if ($current)<span class="absolute inset-x-3 -bottom-[0.95rem] h-[3px] rounded-full bg-aqua xl:inset-x-4" aria-hidden="true"></span>@endif
                    </a>
                @endforeach
            </nav>

            <a href="{{ route('careers') }}" class="btn btn-on-dark btn-sm hidden flex-none xl:inline-flex">Join the team</a>
            <a href="{{ route('quote') }}" class="btn btn-aqua btn-sm hidden flex-none whitespace-nowrap lg:inline-flex">Request a quote<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
            <button type="button" onclick="toggleTheme()" class="inline-flex h-11 w-11 flex-none items-center justify-center rounded text-white/80 hover:bg-white/10 lg:hidden" aria-label="Switch light or dark theme">
                <x-icon name="moon" class="h-5 w-5 dark-hide" /><x-icon name="sun" class="hidden h-5 w-5 dark-show" />
            </button>
            <button type="button" class="inline-flex h-11 w-11 flex-none items-center justify-center rounded ring-1 ring-white/25 lg:hidden" @click="menu = !menu" :aria-expanded="menu.toString()" aria-controls="mobile-menu" aria-label="Menu">
                <x-icon name="menu" x-show="!menu" /><x-icon name="x" x-show="menu" x-cloak />
            </button>
        </div>
        <div class="flash-rule"></div>
        <div id="mobile-menu" class="max-h-[calc(100vh-4.75rem)] overflow-y-auto border-t border-white/10 bg-char-900 lg:hidden" x-show="menu" x-cloak x-transition.opacity>
            <nav class="wrap flex flex-col py-3" aria-label="Mobile">
                <a href="{{ route('home') }}" class="font-display rounded px-1 py-3 text-2xl font-extrabold">Home</a>
                @foreach ($nav as $item)
                    <a href="{{ route($item['route']) }}" class="font-display rounded px-1 py-3 text-2xl font-extrabold" @if(request()->routeIs($item['match'])) aria-current="page" @endif>{{ $item['label'] }}</a>
                @endforeach
                <a href="{{ route('pickup') }}" class="rounded px-1 py-3 font-semibold text-white/85">Armoured pickup request</a>
                <a href="{{ $portalUser ? route('portal.dashboard') : route('portal.login') }}" class="rounded px-1 py-3 font-semibold text-white/85">{{ $portalUser ? 'My applications' : 'Applicant portal sign-in' }}</a>
                <div class="mt-3 grid gap-3 border-t border-white/10 pt-5 sm:grid-cols-2">
                    @if ($freeport)<a href="{{ tel_href($freeport) }}" class="btn btn-on-dark"><x-icon name="phone" class="h-4 w-4" />Freeport {{ format_phone($freeport) }}</a>@endif
                    @if ($nassau)<a href="{{ tel_href($nassau) }}" class="btn btn-on-dark"><x-icon name="phone" class="h-4 w-4" />Nassau {{ format_phone($nassau) }}</a>@endif
                </div>
            </nav>
        </div>
    </header>

    <main id="main">
        {{ $slot }}
    </main>

    <footer class="mt-24 bg-char-950 text-white/75">
        <div class="route opacity-70"></div>
        <div class="wrap grid gap-12 py-16 md:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_1.3fr]">
            <div>
                <x-logo tone="light" />
                <p class="mt-5 max-w-xs text-white/70">Security officers, armoured transport, mobile patrol, aviation security and lifeguards across The Bahamas since {{ candid('founded') }}.</p>
                <a href="{{ route('quote') }}" class="btn btn-aqua mt-6">Request a quote<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
            </div>
            <div>
                <h2 class="mono-label text-white/55">Services</h2>
                <ul class="mt-5 space-y-3">
                    @foreach (candid('services') as $key => $s)
                        <li><a class="hover:text-white" href="{{ route('services.show', $key) }}">{{ $s['name'] }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div>
                <h2 class="mono-label text-white/55">Company</h2>
                <ul class="mt-5 space-y-3">
                    <li><a class="hover:text-white" href="{{ route('about') }}">About &amp; history</a></li>
                    <li><a class="hover:text-white" href="{{ route('locations') }}">Locations</a></li>
                    <li><a class="hover:text-white" href="{{ route('careers') }}">Careers</a></li>
                    <li><a class="hover:text-white" href="{{ route('portal.login') }}">Applicant portal</a></li>
                    <li><a class="hover:text-white" href="{{ route('pickup') }}">Armoured pickup</a></li>
                    <li><a class="hover:text-white" href="{{ route('contact') }}">Contact</a></li>
                </ul>
            </div>
            <div class="space-y-6">
                @foreach (['freeport' => 'Freeport · Grand Bahama', 'nassau' => 'Nassau · New Providence'] as $office => $label)
                    <div>
                        <h2 class="mono-label text-white/55">{{ $label }}</h2>
                        @if ($address = setting($office.'_address'))<p class="mt-2 whitespace-pre-line leading-relaxed">{{ $address }}</p>@endif
                        @if ($phone = setting($office.'_phone'))<a href="{{ tel_href($phone) }}" class="font-display num mt-1 inline-flex min-h-11 items-center gap-2 text-xl font-bold text-white hover:text-aqua"><x-icon name="phone" class="h-5 w-5" />{{ format_phone($phone) }}</a>@endif
                    </div>
                @endforeach
                @if ($email = setting('general_email'))<p><a class="inline-flex min-h-11 items-center hover:text-white" href="mailto:{{ $email }}">{{ $email }}</a></p>@endif
            </div>
        </div>
        <div class="border-t border-white/10">
            <div class="wrap flex flex-col gap-3 py-6 text-sm text-white/60 md:flex-row md:items-center md:justify-between">
                <p>© {{ date('Y') }} {{ candid('name') }} All rights reserved.</p>
                <p class="flex flex-wrap gap-x-5 gap-y-1">
                    <a class="inline-flex min-h-11 items-center hover:text-white" href="{{ route('privacy') }}">Privacy</a>
                    <a class="inline-flex min-h-11 items-center hover:text-white" href="{{ route('credits') }}">Photo credits</a>
                    <a class="inline-flex min-h-11 items-center hover:text-white" href="{{ route('sitemap.page') }}">Sitemap</a>
                    <a class="inline-flex min-h-11 items-center hover:text-white" href="{{ route('login') }}">Staff sign-in</a>
                </p>
            </div>
        </div>
    </footer>

    @if ($mobileBar)
        {{-- Thumb-reach actions on phones. The call chooser is a <details>, so it works without JavaScript. --}}
        <nav aria-label="Quick actions" class="fixed inset-x-0 bottom-0 z-30 border-t border-white/10 bg-char-900/95 p-3 backdrop-blur lg:hidden">
            <div class="flex gap-2">
                <details class="group relative flex-none">
                    <summary class="btn btn-on-dark list-none px-4 [&::-webkit-details-marker]:hidden" aria-label="Call an office"><x-icon name="phone" /><span class="sr-only">Call</span></summary>
                    <div class="absolute bottom-full left-0 mb-2 w-64 rounded-lg bg-char-800 p-2 shadow-xl ring-1 ring-white/15">
                        @if ($freeport)<a href="{{ tel_href($freeport) }}" class="flex min-h-12 items-center justify-between gap-3 rounded px-3 text-white hover:bg-white/10"><span>Freeport</span><span class="num font-semibold">{{ format_phone($freeport) }}</span></a>@endif
                        @if ($nassau)<a href="{{ tel_href($nassau) }}" class="flex min-h-12 items-center justify-between gap-3 rounded px-3 text-white hover:bg-white/10"><span>Nassau</span><span class="num font-semibold">{{ format_phone($nassau) }}</span></a>@endif
                    </div>
                </details>
                <a href="{{ route('careers') }}" class="btn btn-on-dark flex-1 whitespace-nowrap px-2">Apply</a>
                <a href="{{ route('quote') }}" class="btn btn-aqua flex-1 whitespace-nowrap px-2">Get a quote</a>
            </div>
        </nav>
    @endif
</body>
</html>
