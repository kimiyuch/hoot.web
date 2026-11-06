<x-layouts.auth title="Sign up" heading="Create your account" description="Free, and it takes a minute. Then follow a show or two.">
    <form method="post" action="{{ route('register.store') }}" class="flex flex-col gap-5">
        @csrf
        <x-field name="name" label="Name" required autofocus autocomplete="name" />
        <x-field name="email" type="email" label="Email" :value="request('email')" required autocomplete="email" hint="We’ll send you a link to confirm it." />
        <x-field name="password" type="password" label="Password" required autocomplete="new-password" hint="At least 8 characters." />
        <x-field name="password_confirmation" type="password" label="Password again" required autocomplete="new-password" />
        <x-button class="mt-1 w-full">Create account</x-button>
    </form>

    <x-slot:below>
        Already have an account? <a href="{{ route('login') }}" class="font-semibold text-white hover:text-midnight-200">Log in</a>
    </x-slot:below>
</x-layouts.auth>
