{{-- Shared by the list and the pipeline. --}}
<form method="GET" class="a-card flex flex-wrap items-end gap-3 p-4">
    @if (request('q'))<input type="hidden" name="q" value="{{ input_string(request('q')) }}">@endif
    <div><label class="a-label" for="flt-island">Island</label><select id="flt-island" name="island" class="a-input" onchange="this.form.requestSubmit()"><option value="">All islands</option>@foreach (candid('islands') as $k => $i)<option value="{{ $k }}" @selected(request('island') === $k)>{{ $i['name'] }}</option>@endforeach</select></div>
    @if (! ($pipeline ?? false))
        <div><label class="a-label" for="flt-stage">Stage</label><select id="flt-stage" name="stage" class="a-input" onchange="this.form.requestSubmit()"><option value="">All stages</option>@foreach (\App\Models\Application::STAGES as $k => $l)<option value="{{ $k }}" @selected(request('stage') === $k)>{{ $l }}</option>@endforeach</select></div>
    @endif
    <div><label class="a-label" for="flt-position">Position</label><select id="flt-position" name="position" class="a-input" onchange="this.form.requestSubmit()"><option value="">Any position</option>@foreach (\App\Models\Application::POSITIONS as $k => $l)<option value="{{ $k }}" @selected(request('position') === $k)>{{ $l }}</option>@endforeach</select></div>
    <div><label class="a-label" for="flt-job">Job posting</label><select id="flt-job" name="job" class="a-input max-w-56" onchange="this.form.requestSubmit()"><option value="">Any posting</option>@foreach ($jobs as $j)<option value="{{ $j->id }}" @selected((string) request('job') === (string) $j->id)>{{ $j->title }} · {{ island_name($j->island) }}</option>@endforeach</select></div>
    <div><label class="a-label" for="flt-assigned">Recruiter</label><select id="flt-assigned" name="assigned" class="a-input" onchange="this.form.requestSubmit()"><option value="">Anyone</option><option value="none" @selected(request('assigned') === 'none')>Unassigned</option>@foreach ($recruiters as $u)<option value="{{ $u->id }}" @selected((string) request('assigned') === (string) $u->id)>{{ $u->name }}</option>@endforeach</select></div>
    <label class="flex min-h-11 items-center gap-2 text-sm font-medium"><input type="checkbox" name="docs" value="missing" class="h-4 w-4 accent-[var(--aqua-text)]" @checked(request('docs') === 'missing') onchange="this.form.requestSubmit()">Documents missing</label>
    <button class="a-btn a-btn-ghost">Apply</button>
    @if (collect(request()->only(['island', 'stage', 'position', 'job', 'assigned', 'docs', 'q']))->filter()->isNotEmpty())<a href="{{ url()->current() }}" class="a-btn text-link">Clear</a>@endif
</form>
