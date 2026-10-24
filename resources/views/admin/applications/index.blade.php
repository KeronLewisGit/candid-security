@php($A = \App\Models\Application::class)
<x-layouts.admin title="Applicants">
    <x-slot:actions>
        <a href="{{ route('admin.applications.pipeline', request()->except('stage', 'page')) }}" class="a-btn a-btn-ghost"><x-icon name="columns" class="h-4 w-4" />Pipeline</a>
        <a href="{{ route('admin.applications.export', request()->except('page')) }}" class="a-btn a-btn-ghost"><x-icon name="download" class="h-4 w-4" />Export CSV</a>
    </x-slot:actions>

    <nav class="tag-row mb-4" aria-label="Stages">
        <a href="{{ route('admin.applications.index', request()->except('stage', 'page')) }}" class="tag" @if(! request('stage')) aria-current="true" @endif>All <span class="count">{{ $stageCounts->sum() }}</span></a>
        @foreach ($A::STAGES as $k => $l)
            <a href="{{ route('admin.applications.index', ['stage' => $k] + request()->except('stage', 'page')) }}" class="tag" @if(request('stage') === $k) aria-current="true" @endif>{{ $l }} <span class="count">{{ $stageCounts[$k] ?? 0 }}</span></a>
        @endforeach
    </nav>
    @include('admin.applications._filters')

    <form method="POST" action="{{ route('admin.applications.bulk') }}" x-data="bulkSelect" class="mt-4">
        @csrf
        <div class="a-card overflow-x-auto">
            <table class="data-table">
                <thead><tr>
                    <th class="w-10"><input type="checkbox" class="h-4 w-4" aria-label="Select all on this page" @change="all($event)"></th>
                    <th>Applicant</th><th>Position</th><th>Island</th><th>Stage</th><th>Documents</th><th class="r">Exam</th><th>Recruiter</th><th class="r">Sent</th>
                </tr></thead>
                <tbody>
                    @forelse ($applications as $a)
                        @php($missing = $a->missingDocuments())
                        <tr>
                            <td><input type="checkbox" name="ids[]" value="{{ $a->id }}" data-row x-model="selected" class="h-4 w-4" aria-label="Select {{ $a->full_name }}"></td>
                            <td><a href="{{ route('admin.applications.show', $a) }}" class="font-semibold hover:text-aqua-text">{{ $a->full_name }}</a><span class="mono block text-xs text-ink-3">{{ $a->reference }}</span></td>
                            <td>{{ $a->roleTitle() }}</td>
                            <td>{{ $a->islandName() }}</td>
                            <td><x-status :stage="$a->status" /></td>
                            <td>@if($missing)<x-chip tone="attention" icon="alert">{{ count($missing) }} missing</x-chip>@elseif($a->documents->where('status', 'pending')->isNotEmpty())<x-chip tone="neutral" icon="clock">To review</x-chip>@else<x-chip tone="good" icon="check">Complete</x-chip>@endif</td>
                            <td class="r num">@if($a->exam_score !== null)<span @class(['font-semibold', 'text-[color:var(--critical-text)]' => ! $a->examPassed()])>{{ $a->exam_score }}%</span>@else<span class="text-ink-3">—</span>@endif</td>
                            <td class="text-ink-2">{{ $a->assignee?->name ?? '—' }}</td>
                            <td class="r text-ink-3" title="{{ $a->submitted_at?->format('j M Y H:i') }}">{{ $a->submitted_at?->diffForHumans(short: true) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="py-10 text-center text-ink-3">No applicants match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $applications->links() }}</div>

        {{-- Bulk email: appears once rows are selected; without JS it stays visible. --}}
        <section class="a-card mt-6 p-5" x-data="composer(@js($templates->map->only(['id', 'name', 'subject', 'body', 'set_status'])->values()))" aria-labelledby="bulk-title">
            <h2 id="bulk-title" class="font-semibold">Email selected applicants <span class="num text-ink-3" x-text="selected.length ? '(' + selected.length + ')' : ''"></span></h2>
            <p class="mt-1 text-sm text-ink-3">Placeholders: {{ implode(' ', \App\Models\MessageTemplate::placeholders()) }}. Each person also gets the message in their applicant portal.</p>
            <div class="mt-4 grid gap-4 lg:grid-cols-3">
                <div><label class="a-label" for="bulk-template">Template</label><select id="bulk-template" class="a-input w-full" @change="pick($event.target.value)"><option value="">Write your own…</option>@foreach ($templates as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach</select></div>
                <div class="lg:col-span-2"><label class="a-label" for="bulk-subject">Subject</label><input id="bulk-subject" name="subject" x-model="subject" class="a-input w-full" required maxlength="200"></div>
                <div class="lg:col-span-3"><label class="a-label" for="bulk-body">Message</label><textarea id="bulk-body" name="body" x-model="body" rows="6" class="a-input w-full" required></textarea></div>
                <div><label class="a-label" for="bulk-status">Then move them to</label><select id="bulk-status" name="set_status" x-model="setStatus" class="a-input w-full"><option value="">Keep current stage</option>@foreach ($A::STAGES as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
            </div>
            <button class="a-btn a-btn-primary mt-4" :disabled="!selected.length"><x-icon name="send" class="h-4 w-4" />Send to selected</button>
        </section>
    </form>
</x-layouts.admin>
