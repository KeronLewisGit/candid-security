@php($A = \App\Models\Application::class)
<x-layouts.admin title="Hiring pipeline" :wide="true">
    <x-slot:actions>
        <a href="{{ route('admin.applications.index', request()->query()) }}" class="a-btn a-btn-ghost"><x-icon name="list" class="h-4 w-4" />List</a>
        <a href="{{ route('admin.applications.export', request()->query()) }}" class="a-btn a-btn-ghost"><x-icon name="download" class="h-4 w-4" />Export CSV</a>
    </x-slot:actions>
    @include('admin.applications._filters', ['pipeline' => true])

    <div x-data="board" class="relative mt-4">
        <div x-show="toast" x-cloak x-transition class="fixed bottom-6 left-1/2 z-50 -translate-x-1/2 rounded-lg bg-char-900 px-4 py-2.5 text-sm font-medium text-white shadow-xl" role="status" x-text="toast"></div>
        <p class="mb-4 text-sm text-ink-2">Drag a card to another column, or use its stage menu. Moving a card notifies the applicant. Closed: <a class="text-link" href="{{ route('admin.applications.index', ['stage' => 'hired']) }}">{{ $closed['hired'] ?? 0 }} hired</a> · <a class="text-link" href="{{ route('admin.applications.index', ['stage' => 'rejected']) }}">{{ $closed['rejected'] ?? 0 }} rejected</a> · <a class="text-link" href="{{ route('admin.applications.index', ['stage' => 'withdrawn']) }}">{{ $closed['withdrawn'] ?? 0 }} withdrawn</a></p>
        <div class="flex gap-3 overflow-x-auto pb-4">
            @foreach ($columns as $stage => $apps)
                <section class="kanban-col" data-stage="{{ $stage }}" :class="over === '{{ $stage }}' && 'drag-over'" @dragover.prevent="over = '{{ $stage }}'" @dragleave.self="over = null" @drop.prevent="drop('{{ $stage }}')" aria-label="{{ $A::STAGES[$stage] }}">
                    <header class="flex items-center justify-between px-1.5 pb-2 pt-1"><h2 class="text-sm font-bold">{{ $A::STAGES[$stage] }}</h2><span class="num rounded-full bg-surface px-2 py-0.5 text-xs font-bold" data-count>{{ $apps->count() }}</span></header>
                    <div class="flex min-h-[55vh] flex-col gap-2" data-list>
                        <p class="rounded-lg border border-dashed border-line-strong p-4 text-center text-xs text-ink-3 {{ $apps->count() ? 'hidden' : '' }}" data-empty>No one here</p>
                        @foreach ($apps as $a)
                            @php($late = $a->status_changed_at && $a->status_changed_at->lt(now()->subDays(3)))
                            @php($booking = ($k = $a->bookableKind()) ? $a->bookingFor($k) : null)
                            <article id="card-{{ $a->id }}" @class(['kanban-card', 'is-late' => $late]) draggable="true" data-url="{{ route('admin.applications.update', $a) }}" data-name="{{ $a->full_name }}" @dragstart="start($event, {{ $a->id }})" @dragend="end()" :class="dragging === {{ $a->id }} && 'opacity-40'">
                                <a href="{{ route('admin.applications.show', $a) }}" class="-my-3 inline-block py-3 font-semibold hover:text-aqua-text">{{ $a->full_name }}</a>
                                <p class="text-xs text-ink-3">{{ $a->roleTitle() }}</p>
                                <div class="mt-2 flex flex-wrap gap-1">
                                    <span class="badge-id !py-0 text-ink-3">{{ candid('islands.'.$a->island.'.code') ?? '—' }}</span>
                                    @if ($m = count($a->missingDocuments()))<x-chip tone="attention" icon="file" class="!text-[11px]">{{ $m }} doc{{ $m > 1 ? 's' : '' }} missing</x-chip>@endif
                                    @if ($a->exam_score !== null)<x-chip :tone="$a->examPassed() ? 'good' : 'critical'" icon="pencil" class="!text-[11px]">{{ $a->exam_score }}%</x-chip>@endif
                                    @if ($a->drug_test_result)<x-chip :tone="$a->drug_test_result === 'negative' ? 'good' : ($a->drug_test_result === 'pending' ? 'neutral' : 'critical')" icon="shield" class="!text-[11px]">{{ ucfirst($a->drug_test_result) }}</x-chip>@endif
                                </div>
                                @if ($booking)<p class="mt-2 rounded bg-aqua-soft px-2 py-1 text-[11px] font-semibold">Booked {{ $booking->slot->starts_at->format('D j M, g:i a') }}</p>@elseif ($k)<p class="mt-2 text-[11px] text-ink-3">Not booked yet</p>@endif
                                <div class="mt-2 flex items-center justify-between gap-2 text-[11px] text-ink-3"><span>{{ $a->assignee?->name ?? 'Unassigned' }}</span><span @class(['font-semibold text-[color:var(--critical-text)]' => $late])>{{ $a->status_changed_at?->diffForHumans(short: true) }}</span></div>
                                {{-- No-JS / keyboard fallback for moving the card --}}
                                <form method="POST" action="{{ route('admin.applications.update', $a) }}" class="mt-2 flex gap-1">
                                    @csrf @method('PATCH')
                                    <label class="sr-only" for="mv-{{ $a->id }}">Move {{ $a->full_name }} to</label>
                                    <select id="mv-{{ $a->id }}" name="status" class="a-input !min-h-11 lg:!min-h-9 flex-1 !py-1 text-xs">@foreach ($A::STAGES as $k2 => $l2)<option value="{{ $k2 }}" @selected($a->status === $k2)>{{ $l2 }}</option>@endforeach</select>
                                    <button class="a-btn a-btn-ghost !min-h-11 lg:!min-h-9 px-2 text-xs">Move</button>
                                </form>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    </div>
</x-layouts.admin>
