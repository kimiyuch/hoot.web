<x-layouts.auth title="Pick a username" heading="Hoot is getting social" description="Soon you can follow people and see what they’re watching. For that, everyone gets a profile with a username. Pick yours and who can see it; you can change both in Settings.">
    <form method="post" action="{{ route('onboarding.update') }}" class="flex flex-col gap-5">
        @csrf
        <x-field name="username" label="Username" required autofocus autocomplete="username" autocapitalize="none" spellcheck="false" maxlength="20" placeholder="pip_owl"
            :value="\Illuminate\Support\Str::of(auth()->user()->name)->lower()->ascii()->replaceMatches('/[^a-z0-9_]+/', '_')->trim('_')->limit(20, '')"
            :hint="'Your profile will be at '.parse_url(url('/'), PHP_URL_HOST).'/@username. Lowercase letters, numbers and _.'" />
        <x-visibility-choice />
        <x-button class="mt-1 w-full">Continue</x-button>
    </form>

    <x-slot:below>
        Not now? <form method="post" action="{{ route('logout') }}" class="inline">@csrf<button type="submit" class="font-semibold text-white hover:text-midnight-200">Log out</button></form>
    </x-slot:below>
</x-layouts.auth>
