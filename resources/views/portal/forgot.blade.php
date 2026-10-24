<x-auth-card title="Reset your password" lead="Enter the email you applied with and we will send a reset link.">
    <form method="POST" action="{{ route('portal.password.email') }}" class="space-y-5">
        @csrf
        <x-field name="email" type="email" label="Email" required autocomplete="email" />
        <button class="btn btn-primary w-full">Email me a reset link</button>
        <p class="text-[0.9375rem]"><a href="{{ route('portal.login') }}" class="text-link">Back to sign in</a></p>
    </form>
</x-auth-card>
