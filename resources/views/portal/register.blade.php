<x-auth-card title="Create an applicant account" lead="Most people create their account on the first step of the application. You can also make one here and apply later.">
    <form method="POST" action="{{ route('portal.register') }}" class="space-y-5">
        @csrf
        <div class="grid gap-5 sm:grid-cols-2">
            <x-field name="first_name" label="First name" required autocomplete="given-name" />
            <x-field name="last_name" label="Last name" required autocomplete="family-name" />
        </div>
        <x-field name="email" type="email" label="Email" required autocomplete="email" />
        <x-field name="phone" type="tel" label="Phone" autocomplete="tel" />
        <x-field name="password" type="password" label="Password" required autocomplete="new-password" hint="At least 8 characters." />
        <x-field name="password_confirmation" type="password" label="Type it again" required autocomplete="new-password" />
        <button class="btn btn-primary w-full">Create account</button>
        <p class="text-[0.9375rem]">Already have one? <a href="{{ route('portal.login') }}" class="text-link">Sign in</a></p>
    </form>
</x-auth-card>
