<x-layouts.admin title="Job postings">
    <x-slot:actions><a href="{{ route('admin.jobs.create') }}" class="a-btn a-btn-primary"><x-icon name="plus" class="h-4 w-4" />New posting</a></x-slot:actions>
    <nav class="tag-row mb-4" aria-label="Island">
        <a href="{{ route('admin.jobs.index') }}" class="tag" @if(! $island) aria-current="true" @endif>All islands</a>
        @foreach (candid('islands') as $k => $i)<a href="{{ route('admin.jobs.index', ['island' => $k]) }}" class="tag" @if($island === $k) aria-current="true" @endif>{{ $i['name'] }}</a>@endforeach
    </nav>
    <div class="a-card overflow-x-auto">
        <table class="data-table">
            <thead><tr><th>Title</th><th>Island</th><th>Status</th><th class="r">Openings</th><th class="r">Active applicants</th><th>Closes</th><th></th></tr></thead>
            <tbody>
                @forelse ($jobs as $job)
                    <tr>
                        <td><a href="{{ route('admin.jobs.edit', $job) }}" class="font-semibold hover:text-aqua-text">{{ $job->title }}</a><span class="block text-xs text-ink-3">{{ $job->positionLabel() }} · {{ $job->typeLabel() }}</span></td>
                        <td>{{ $job->islandName() }}</td>
                        <td><x-chip :tone="['open' => 'good', 'paused' => 'attention', 'draft' => 'neutral', 'closed' => 'muted'][$job->status]" :icon="['open' => 'check', 'paused' => 'clock', 'draft' => 'pencil', 'closed' => 'x'][$job->status]">{{ \App\Models\JobPosting::STATUSES[$job->status] }}</x-chip></td>
                        <td class="r num">{{ $job->openings }}</td>
                        <td class="r num"><a class="text-link" href="{{ route('admin.applications.index', ['job' => $job->id]) }}">{{ $job->active_count }}</a></td>
                        <td class="num">{{ $job->closes_on?->format('j M Y') ?? '—' }}</td>
                        <td class="r whitespace-nowrap">@if($job->isLive())<a class="text-sm text-link" href="{{ route('careers.show', $job) }}" target="_blank" rel="noopener">View</a>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-10 text-center text-ink-3">No postings yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.admin>
