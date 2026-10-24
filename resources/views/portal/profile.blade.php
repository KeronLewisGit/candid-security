<x-portal-shell title="Your profile">
    <form method="POST" action="{{ route('portal.profile.update') }}" class="panel max-w-2xl space-y-5 p-6 sm:p-8">
        @csrf @method('PUT')
        <div class="grid gap-5 sm:grid-cols-2">
            <x-field name="first_name" label="First name" :value="$applicant->first_name" required />
            <x-field name="last_name" label="Last name" :value="$applicant->last_name" required />
        </div>
        <x-field name="phone" type="tel" label="Phone" :value="$applicant->phone" />
        <p class="text-[0.9375rem] text-ink-3">Email: <strong class="text-ink">{{ $applicant->email }}</strong>. To change it, email {{ setting('hr_email') }}.</p>
        <fieldset class="space-y-5 border-t border-line pt-5">
            <legend class="pt-5 font-semibold">Change password</legend>
            <x-field name="current_password" type="password" label="Current password" autocomplete="current-password" />
            <div class="grid gap-5 sm:grid-cols-2">
                <x-field name="password" type="password" label="New password" autocomplete="new-password" />
                <x-field name="password_confirmation" type="password" label="Type it again" autocomplete="new-password" />
            </div>
        </fieldset>
        <button class="btn btn-primary">Save</button>
    </form>
</x-portal-shell>
