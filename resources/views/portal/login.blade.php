<x-auth-card title="Sign in to track your application" lead="See where your application stands, upload documents and book your exam and interview.">
    <form method="POST" action="{{ route('portal.login') }}" class="space-y-5">
        @csrf
        <x-field name="email" type="email" label="Email" required autocomplete="username" autofocus />
        <x-field name="password" type="password" label="Password" required autocomplete="current-password" />
        <label class="flex min-h-11 items-center gap-3"><input type="checkbox" name="remember" value="1" class="h-5 w-5 accent-[var(--aqua-text)]">Keep me signed in on this device</label>
        <button class="btn btn-primary w-full">Sign in</button>
        <p class="flex flex-wrap justify-between gap-3 text-[0.9375rem]"><a href="{{ route('portal.password.request') }}" class="text-link inline-flex min-h-11 items-center">Forgot your password?</a><a href="{{ route('apply') }}" class="text-link inline-flex min-h-11 items-center">New here? Start an application</a></p>
    </form>
</x-auth-card>
