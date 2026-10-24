@php($R = \App\Models\ServiceRequest::class)
<x-layouts.admin :title="$item->kindLabel().' '.$item->reference">
    <x-slot:actions><a href="{{ route('admin.requests.index', ['kind' => $item->kind]) }}" class="a-btn a-btn-ghost"><x-icon name="columns" class="h-4 w-4" />Board</a></x-slot:actions>
    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <section class="a-card p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold">{{ $item->company ?: $item->name }}</h2>
                    <p class="text-ink-2">{{ $item->name }}@if($item->island) · {{ island_name($item->island) }}@endif</p>
                    <p class="mt-1 flex flex-wrap gap-x-4">@if($item->phone)<a class="text-link" href="{{ tel_href($item->phone) }}">{{ $item->phone }}</a>@endif<a class="text-link" href="mailto:{{ $item->email }}?subject={{ rawurlencode('Your request '.$item->reference) }}">{{ $item->email }}</a></p>
                </div>
                @if ($item->isOverdue())<x-chip tone="critical" icon="clock">Unanswered over 24h</x-chip>@else<x-chip :tone="$item->status === 'new' ? 'attention' : 'neutral'" icon="dot">{{ $item->statusLabel() }}</x-chip>@endif
            </div>
            <dl class="mt-6 grid gap-x-6 gap-y-3 text-[0.9375rem] sm:grid-cols-2">
                <div><dt class="text-ink-3">Received</dt><dd class="font-medium">{{ $item->created_at->format('D j M Y, g:i a') }}</dd></div>
                @if ($item->serviceLabel())<div><dt class="text-ink-3">Service</dt><dd class="font-medium">{{ $item->serviceLabel() }}</dd></div>@endif
                @if ($item->kind === 'quote')
                    @foreach (['Site type' => $item->option('site_type', $R::SITE_TYPES), 'Site address' => $item->detail('site_address'), 'Armed / unarmed' => $item->option('coverage', $R::COVERAGE), 'Posts' => $item->detail('posts'), 'Hours' => $item->option('hours', $R::HOURS), 'Days per week' => $item->detail('days'), 'Duration' => $item->option('duration', $R::DURATION), 'Start date' => $item->needed_on?->format('j M Y')] as $k => $v)
                        <div><dt class="text-ink-3">{{ $k }}</dt><dd class="font-medium">{{ $v ?: '—' }}</dd></div>
                    @endforeach
                @elseif ($item->kind === 'pickup')
                    @foreach (['Pickup date' => $item->needed_on?->format('D j M Y'), 'Time window' => $item->option('window', $R::WINDOWS), 'Pickup address' => $item->detail('pickup_address'), 'Deliver to' => $item->detail('destination'), 'Frequency' => $item->option('frequency', $R::FREQUENCY), 'Existing client' => $item->detail('existing_client') === 'yes' ? 'Yes' : 'No'] as $k => $v)
                        <div><dt class="text-ink-3">{{ $k }}</dt><dd class="font-medium">{{ $v ?: '—' }}</dd></div>
                    @endforeach
                @elseif ($item->detail('topic'))
                    <div><dt class="text-ink-3">Topic</dt><dd class="font-medium">{{ ucfirst((string) $item->detail('topic')) }}</dd></div>
                @endif
            </dl>
            @if ($item->message)<div class="mt-5 rounded-lg bg-surface-2 p-4"><p class="text-sm text-ink-3">Message</p><p class="mt-1 whitespace-pre-line">{{ $item->message }}</p></div>@endif
        </section>
        <form method="POST" action="{{ route('admin.requests.update', $item) }}" class="a-card space-y-4 self-start p-5">
            @csrf @method('PATCH')
            <div><label class="a-label" for="status">Status</label><select id="status" name="status" class="a-input w-full">@foreach ($item->statuses() as $k => $l)<option value="{{ $k }}" @selected($item->status === $k)>{{ $l }}</option>@endforeach</select></div>
            <div><label class="a-label" for="assigned_to">Owner</label><select id="assigned_to" name="assigned_to" class="a-input w-full"><option value="">Unassigned</option>@foreach ($users as $u)<option value="{{ $u->id }}" @selected($item->assigned_to === $u->id)>{{ $u->name }}</option>@endforeach</select></div>
            @if ($item->kind === 'pickup')<div><label class="a-label" for="needed_on">Confirmed pickup date</label><input id="needed_on" name="needed_on" type="date" value="{{ $item->needed_on?->toDateString() }}" class="a-input w-full"></div>@endif
            <div><label class="a-label" for="staff_notes">Internal notes</label><textarea id="staff_notes" name="staff_notes" rows="6" class="a-input w-full">{{ $item->staff_notes }}</textarea></div>
            <button class="a-btn a-btn-primary w-full">Save</button>
            <p class="text-xs text-ink-3">First response: {{ $item->responded_at?->format('j M Y, g:i a') ?? 'not yet' }}. Moving out of New counts as a response.</p>
        </form>
    </div>
</x-layouts.admin>
