<x-layouts.admin title="Email templates">
    <x-slot:actions><a href="{{ route('admin.templates.create') }}" class="a-btn a-btn-primary"><x-icon name="plus" class="h-4 w-4" />New template</a></x-slot:actions>
    <div class="a-card overflow-x-auto">
        <table class="data-table">
            <thead><tr><th>Name</th><th>Subject</th><th>Moves applicant to</th><th></th></tr></thead>
            <tbody>
                @foreach ($templates as $t)
                    <tr><td><a class="font-semibold hover:text-aqua-text" href="{{ route('admin.templates.edit', $t) }}">{{ $t->name }}</a></td><td class="text-ink-2">{{ $t->subject }}</td><td>@if($t->set_status)<x-status :stage="$t->set_status" />@else<span class="text-ink-3">—</span>@endif</td>
                    <td class="r"><form method="POST" action="{{ route('admin.templates.destroy', $t) }}">@csrf @method('DELETE')<button class="a-btn px-2 text-ink-3" aria-label="Delete {{ $t->name }}" onclick="return confirm('Delete this template?')"><x-icon name="trash" class="h-4 w-4" /></button></form></td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-layouts.admin>
