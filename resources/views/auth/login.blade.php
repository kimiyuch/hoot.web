<x-layouts.auth title="Log in" heading="Welcome back" description="Log in to see what’s coming up for you.">
    <form method="post" action="{{ route('login.store') }}" class="flex flex-col gap-5">
        @csrf
        <x-field name="email" type="email" label="Email" :value="request('email')" required autofocus autocomplete="username" />
        <div>
            <x-field name="password" type="password" label="Password" required autocomplete="current-password" />
            <a href="{{ route('password.request') }}" class="mt-2 inline-block text-base/6 text-white/55 hover:text-white sm:text-sm/6">Forgot your password?</a>
        </div>
        <label class="flex items-center gap-3 text-base/6 text-white/70 sm:text-sm/6">
            <input type="checkbox" name="remember" value="1" @checked(old('remember', true)) class="size-4 rounded accent-midnight-400">
            Keep me logged in
        </label>
        <x-button class="mt-1 w-full">Log in</x-button>
    </form>

    <x-slot:below>
        New here? <a href="{{ route('register') }}" class="font-semibold text-white hover:text-midnight-200">Create an account</a>
    </x-slot:below>
</x-layouts.auth>
