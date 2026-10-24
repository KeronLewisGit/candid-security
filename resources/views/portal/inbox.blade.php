<x-portal-shell title="Messages" lead="Updates from the Candid recruitment team. Each one is also sent to your email.">
    <ul class="max-w-3xl space-y-3">
        @forelse ($items as $n)
            <li class="panel p-5 {{ $n->read_at ? '' : 'border-l-4 !border-l-[color:var(--aqua-text)]' }}">
                <div class="flex flex-wrap items-baseline justify-between gap-2"><h2 class="font-semibold">{{ $n->title }}</h2><p class="text-sm text-ink-3">{{ $n->created_at->format('j M Y, g:i a') }}</p></div>
                @if ($n->body)<div class="mt-2 whitespace-pre-line text-ink-2">{{ $n->body }}</div>@endif
                @if ($n->link)<a href="{{ $n->link }}" class="mt-3 inline-flex min-h-11 items-center text-link">Open the application</a>@endif
            </li>
        @empty
            <li class="panel p-6 text-ink-3">No messages yet.</li>
        @endforelse
    </ul>
    <div class="mt-6">{{ $items->links() }}</div>
</x-portal-shell>
