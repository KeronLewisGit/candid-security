<x-layouts.admin title="Staff accounts">
    <x-slot:actions><a href="{{ route('admin.users.create') }}" class="a-btn a-btn-primary"><x-icon name="plus" class="h-4 w-4" />Add staff</a></x-slot:actions>
    <p class="mb-5 max-w-3xl text-ink-2">Recruiters see applicants, jobs, sessions and templates. Operations see quotes, pickups and messages. Admins see everything.</p>
    <div class="a-card overflow-x-auto">
        <table class="data-table">
            <thead><tr><th>Name</th><th>Role</th><th>Status</th><th>Last sign-in</th></tr></thead>
            <tbody>
                @foreach ($users as $u)
                    <tr><td><a class="font-semibold hover:text-aqua-text" href="{{ route('admin.users.edit', $u) }}">{{ $u->name }}</a><span class="block text-xs text-ink-3">{{ $u->email }}</span></td><td>{{ $u->roleLabel() }}</td><td>@if($u->is_active)<x-chip tone="good" icon="check">Active</x-chip>@else<x-chip tone="muted" icon="x">Deactivated</x-chip>@endif</td><td class="text-ink-3">{{ $u->last_login_at?->diffForHumans() ?? 'Never' }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-layouts.admin>
