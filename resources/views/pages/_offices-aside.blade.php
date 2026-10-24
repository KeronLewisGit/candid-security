<aside class="space-y-5 lg:sticky lg:top-28 lg:self-start">
    @foreach (['freeport' => 'Freeport head office', 'nassau' => 'Nassau office'] as $office => $label)
        <div class="panel p-5">
            <p class="mono-label text-gold-text">{{ $label }}</p>
            @if ($phone = setting($office.'_phone'))<a href="{{ tel_href($phone) }}" class="font-display num mt-2 inline-flex min-h-11 items-center gap-2 text-2xl font-extrabold hover:text-aqua-text"><x-icon name="phone" class="h-5 w-5" />{{ format_phone($phone) }}</a>@endif
            @if ($address = setting($office.'_address'))<p class="mt-1 whitespace-pre-line text-ink-2">{{ $address }}</p>@endif
            <p class="mt-1 text-sm text-ink-3">{{ setting($office.'_hours') ?: 'Call for office hours.' }}</p>
        </div>
    @endforeach
</aside>
