<x-layouts.admin :title="$account->exists ? 'Edit '.$account->name : 'Add staff account'">
    <form method="POST" action="{{ $account->exists ? route('admin.users.update', $account) : route('admin.users.store') }}" class="a-card max-w-2xl space-y-4 p-5 sm:p-6">
        @csrf @if($account->exists) @method('PUT') @endif
        <div><label class="a-label" for="name">Name</label><input id="name" name="name" value="{{ old('name', $account->name) }}" class="a-input w-full" required></div>
        <div><label class="a-label" for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email', $account->email) }}" class="a-input w-full" required></div>
        <div><label class="a-label" for="role">Role</label><select id="role" name="role" class="a-input w-full">@foreach (\App\Models\User::ROLES as $k => $l)<option value="{{ $k }}" @selected(old('role', $account->role) === $k)>{{ $l }}</option>@endforeach</select></div>
        <div><label class="a-label" for="password">{{ $account->exists ? 'New password (leave empty to keep)' : 'Password' }}</label><input id="password" name="password" type="password" class="a-input w-full" autocomplete="new-password" @unless($account->exists) required @endunless><p class="mt-1 text-xs text-ink-3">At least 10 characters.</p></div>
        <label class="flex min-h-11 items-center gap-2"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" class="h-4 w-4" @checked(old('is_active', $account->is_active))>Can sign in</label>
        <div class="flex gap-2"><button class="a-btn a-btn-primary">Save</button><a href="{{ route('admin.users.index') }}" class="a-btn a-btn-ghost">Cancel</a></div>
    </form>
</x-layouts.admin>
