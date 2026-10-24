@php($R = \App\Models\ServiceRequest::class)
<x-layouts.admin :title="'Operations board · '.$R::KINDS[$kind]['label']" :wide="true">
    <nav class="tag-row mb-3" aria-label="Request type">
        @foreach ($R::KINDS as $k => $meta)
            <a href="{{ route('admin.requests.index', ['kind' => $k, 'island' => $island ?: null]) }}" class="tag" @if($kind === $k) aria-current="true" @endif>{{ $meta['label'] }} <span class="count">{{ $openCounts[$k] ?? 0 }} open</span></a>
        @endforeach
    </nav>
    <form method="GET" class="mb-4 flex flex-wrap items-end gap-3">
        <input type="hidden" name="kind" value="{{ $kind }}">
        <div><label class="a-label" for="island">Island</label><select id="island" name="island" class="a-input" onchange="this.form.requestSubmit()"><option value="">All islands</option>@foreach (candid('islands') as $k => $i)<option value="{{ $k }}" @selected($island === $k)>{{ $i['name'] }}</option>@endforeach</select></div>
        <noscript><button class="a-btn a-btn-ghost">Filter</button></noscript>
        @if ($overdue)<p class="flex min-h-11 items-center gap-2 text-sm font-semibold text-[color:var(--critical-text)]"><x-icon name="clock" class="h-4 w-4" />{{ $overdue }} new {{ str('request')->plural($overdue) }} waiting more than 24 hours</p>@endif
    </form>

    <div x-data="board" class="relative">
        <div x-show="toast" x-cloak x-transition class="fixed bottom-6 left-1/2 z-50 -translate-x-1/2 rounded-lg bg-char-900 px-4 py-2.5 text-sm font-medium text-white shadow-xl" role="status" x-text="toast"></div>
        <div class="flex gap-3 overflow-x-auto pb-4">
            @foreach ($columns as $status => $col)
                <section class="kanban-col" data-stage="{{ $status }}" :class="over === '{{ $status }}' && 'drag-over'" @dragover.prevent="over = '{{ $status }}'" @dragleave.self="over = null" @drop.prevent="drop('{{ $status }}')" aria-label="{{ $col['label'] }}">
                    <header class="flex items-center justify-between px-1.5 pb-2 pt-1"><h2 class="text-sm font-bold">{{ $col['label'] }}@if(in_array($status, $R::CLOSED, true)) <span class="font-normal text-ink-3">· 30 days</span>@endif</h2><span class="num rounded-full bg-surface px-2 py-0.5 text-xs font-bold" data-count>{{ $col['items']->count() }}</span></header>
                    <div class="flex min-h-[55vh] flex-col gap-2" data-list>
                        <p class="rounded-lg border border-dashed border-line-strong p-4 text-center text-xs text-ink-3 {{ $col['items']->count() ? 'hidden' : '' }}" data-empty>Empty</p>
                        @foreach ($col['items'] as $r)
                            <article id="card-{{ $r->id }}" @class(['kanban-card', 'is-late' => $r->isOverdue()]) draggable="true" data-url="{{ route('admin.requests.update', $r) }}" data-name="{{ $r->reference }}" @dragstart="start($event, {{ $r->id }})" @dragend="end()" :class="dragging === {{ $r->id }} && 'opacity-40'">
                                <div class="flex items-start justify-between gap-2"><a href="{{ route('admin.requests.show', $r) }}" class="-my-3 inline-block py-3 font-semibold hover:text-aqua-text">{{ $r->company ?: $r->name }}</a><span class="badge-id !py-0 text-ink-3">{{ candid('islands.'.$r->island.'.code') ?? '—' }}</span></div>
                                <p class="mono text-xs text-ink-3">{{ $r->reference }}</p>
                                @if ($kind === 'quote')
                                    <p class="mt-1.5 text-xs text-ink-2">{{ $r->option('site_type', $R::SITE_TYPES) }} · {{ $r->detail('posts') }} {{ str('post')->plural((int) $r->detail('posts')) }} · {{ $r->option('coverage', $R::COVERAGE) }} · {{ $r->option('hours', $R::HOURS) }}</p>
                                @elseif ($kind === 'pickup')
                                    <p class="mt-1.5 text-xs font-semibold">{{ $r->needed_on?->format('D j M') }} · {{ $r->option('window', $R::WINDOWS) }}</p>
                                    <p class="text-xs text-ink-2">{{ $r->option('frequency', $R::FREQUENCY) }} · {{ $r->detail('existing_client') === 'yes' ? 'Existing client' : 'New client' }}</p>
                                @else
                                    <p class="mt-1.5 line-clamp-2 text-xs text-ink-2">{{ $r->message }}</p>
                                @endif
                                <div class="mt-2 flex items-center justify-between text-xs text-ink-3"><span>{{ $r->assignee?->name ?? 'Unassigned' }}</span><span @class(['font-semibold text-[color:var(--critical-text)]' => $r->isOverdue()])>{{ $r->created_at->diffForHumans(short: true) }}</span></div>
                                <form method="POST" action="{{ route('admin.requests.update', $r) }}" class="mt-2 flex gap-1">
                                    @csrf @method('PATCH')
                                    <label class="sr-only" for="st-{{ $r->id }}">Status for {{ $r->reference }}</label>
                                    <select id="st-{{ $r->id }}" name="status" class="a-input !min-h-11 lg:!min-h-9 flex-1 !py-1 text-xs">@foreach ($r->statuses() as $k => $l)<option value="{{ $k }}" @selected($r->status === $k)>{{ $l }}</option>@endforeach</select>
                                    <button class="a-btn a-btn-ghost !min-h-11 lg:!min-h-9 px-2 text-xs">Set</button>
                                </form>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    </div>
</x-layouts.admin>
