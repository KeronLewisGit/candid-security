<x-layouts.admin :title="$template->exists ? 'Edit template' : 'New template'">
    <form method="POST" action="{{ $template->exists ? route('admin.templates.update', $template) : route('admin.templates.store') }}" class="a-card max-w-3xl space-y-4 p-5 sm:p-6">
        @csrf @if($template->exists) @method('PUT') @endif
        <div><label class="a-label" for="name">Name</label><input id="name" name="name" value="{{ old('name', $template->name) }}" class="a-input w-full" required></div>
        <div><label class="a-label" for="subject">Subject</label><input id="subject" name="subject" value="{{ old('subject', $template->subject) }}" class="a-input w-full" required></div>
        <div><label class="a-label" for="body">Body</label><textarea id="body" name="body" rows="10" class="a-input w-full" required>{{ old('body', $template->body) }}</textarea><p class="mt-1 text-xs text-ink-3">Placeholders: {{ implode(' ', \App\Models\MessageTemplate::placeholders()) }}</p></div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="a-label" for="set_status">After sending, move to</label><select id="set_status" name="set_status" class="a-input w-full"><option value="">Keep current stage</option>@foreach (\App\Models\Application::STAGES as $k => $l)<option value="{{ $k }}" @selected(old('set_status', $template->set_status) === $k)>{{ $l }}</option>@endforeach</select></div>
            <div><label class="a-label" for="sort_order">Order</label><input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $template->sort_order ?? 0) }}" class="a-input w-full"></div>
        </div>
        <div class="flex gap-2"><button class="a-btn a-btn-primary">Save</button><a href="{{ route('admin.templates.index') }}" class="a-btn a-btn-ghost">Cancel</a></div>
    </form>
</x-layouts.admin>
