<x-layouts.auth title="Confirm password" heading="Confirm your password" description="Just to be sure it’s you.">
    <form method="post" action="{{ route('password.confirm.store') }}" class="flex flex-col gap-5">
        @csrf
        <x-field name="password" type="password" label="Password" required autofocus autocomplete="current-password" />
        <x-button class="mt-1 w-full">Confirm</x-button>
    </form>
</x-layouts.auth>
