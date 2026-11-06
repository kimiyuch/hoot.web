<x-layouts.auth title="New password" heading="Pick a new password">
    <form method="post" action="{{ route('password.update') }}" class="flex flex-col gap-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <x-field name="email" type="email" label="Email" :value="$request->email" required autocomplete="username" />
        <x-field name="password" type="password" label="New password" required autofocus autocomplete="new-password" hint="At least 8 characters." />
        <x-field name="password_confirmation" type="password" label="New password again" required autocomplete="new-password" />
        <x-button class="mt-1 w-full">Save password</x-button>
    </form>
</x-layouts.auth>
