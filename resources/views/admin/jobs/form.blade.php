@php($editing = $job->exists)
<x-layouts.admin :title="$editing ? 'Edit: '.$job->title : 'New job posting'">
    <form method="POST" action="{{ $editing ? route('admin.jobs.update', $job) : route('admin.jobs.store') }}" class="a-card grid max-w-4xl gap-5 p-5 sm:grid-cols-2 sm:p-6">
        @csrf @if($editing) @method('PUT') @endif
        <div class="sm:col-span-2"><label class="a-label" for="title">Title</label><input id="title" name="title" value="{{ old('title', $job->title) }}" class="a-input w-full" required maxlength="120"></div>
        <div><label class="a-label" for="position">Position type</label><select id="position" name="position" class="a-input w-full">@foreach (\App\Models\Application::POSITIONS as $k => $l)<option value="{{ $k }}" @selected(old('position', $job->position) === $k)>{{ $l }}</option>@endforeach</select></div>
        <div><label class="a-label" for="island">Island</label><select id="island" name="island" class="a-input w-full">@foreach (candid('islands') as $k => $i)<option value="{{ $k }}" @selected(old('island', $job->island) === $k)>{{ $i['name'] }}</option>@endforeach</select></div>
        <div><label class="a-label" for="employment_type">Type</label><select id="employment_type" name="employment_type" class="a-input w-full">@foreach (\App\Models\JobPosting::EMPLOYMENT_TYPES as $k => $l)<option value="{{ $k }}" @selected(old('employment_type', $job->employment_type) === $k)>{{ $l }}</option>@endforeach</select></div>
        <div><label class="a-label" for="status">Status</label><select id="status" name="status" class="a-input w-full">@foreach (\App\Models\JobPosting::STATUSES as $k => $l)<option value="{{ $k }}" @selected(old('status', $job->status) === $k)>{{ $l }}</option>@endforeach</select></div>
        <div><label class="a-label" for="openings">Openings</label><input id="openings" name="openings" type="number" min="1" value="{{ old('openings', $job->openings) }}" class="a-input w-full"></div>
        <div><label class="a-label" for="closes_on">Closes on <span class="font-normal text-ink-3">(optional)</span></label><input id="closes_on" name="closes_on" type="date" value="{{ old('closes_on', $job->closes_on?->toDateString()) }}" class="a-input w-full"></div>
        <div class="sm:col-span-2"><label class="a-label" for="summary">Summary <span class="font-normal text-ink-3">(shown on the careers list)</span></label><textarea id="summary" name="summary" rows="2" class="a-input w-full" required maxlength="600">{{ old('summary', $job->summary) }}</textarea></div>
        <div class="sm:col-span-2"><label class="a-label" for="description">Description <span class="font-normal text-ink-3">(paragraphs separated by a blank line)</span></label><textarea id="description" name="description" rows="4" class="a-input w-full">{{ old('description', $job->description) }}</textarea></div>
        <div><label class="a-label" for="duties">Duties <span class="font-normal text-ink-3">(one per line)</span></label><textarea id="duties" name="duties" rows="6" class="a-input w-full">{{ old('duties', $job->duties) }}</textarea></div>
        <div><label class="a-label" for="requirements">Requirements <span class="font-normal text-ink-3">(one per line)</span></label><textarea id="requirements" name="requirements" rows="6" class="a-input w-full">{{ old('requirements', $job->requirements ?? implode("\n", candid('careers.requirements'))) }}</textarea></div>
        <label class="flex min-h-11 items-center gap-2 sm:col-span-2"><input type="hidden" name="is_featured" value="0"><input type="checkbox" name="is_featured" value="1" class="h-4 w-4" @checked(old('is_featured', $job->is_featured))>Feature on the home page</label>
        <div class="flex flex-wrap gap-2 sm:col-span-2"><button class="a-btn a-btn-primary">Save posting</button><a href="{{ route('admin.jobs.index') }}" class="a-btn a-btn-ghost">Cancel</a></div>
    </form>
    @if ($editing)
        <form method="POST" action="{{ route('admin.jobs.destroy', $job) }}" class="mt-4">@csrf @method('DELETE')<button class="a-btn a-btn-danger" onclick="return confirm('Delete this posting? If it has applications it will be closed instead.')"><x-icon name="trash" class="h-4 w-4" />Delete</button></form>
    @endif
</x-layouts.admin>
