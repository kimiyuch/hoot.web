<x-layouts.auth title="Forgot password" heading="Forgot your password?" description="Enter your email and we’ll send you a link to pick a new one.">
    <form method="post" action="{{ route('password.email') }}" class="flex flex-col gap-5">
        @csrf
        <x-field name="email" type="email" label="Email" required autofocus autocomplete="username" />
        <x-button class="mt-1 w-full">Send the link</x-button>
    </form>

    <x-slot:below>
        <a href="{{ route('login') }}" class="font-semibold text-white hover:text-midnight-200"><span aria-hidden="true">←</span> Back to log in</a>
    </x-slot:below>
</x-layouts.auth>
