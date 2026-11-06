<x-layouts.auth title="Sign up" heading="Create your account" description="Free, and it takes a minute. Then follow a show or two.">
    <form method="post" action="{{ route('register.store') }}" class="flex flex-col gap-5">
        @csrf
        <x-field name="name" label="Name" required autofocus autocomplete="name" />
        <x-field name="username" label="Username" required autocomplete="username" autocapitalize="none" spellcheck="false" maxlength="20" placeholder="pip_owl"
            :hint="'Your profile will be at '.parse_url(url('/'), PHP_URL_HOST).'/@username. Lowercase letters, numbers and _.'" />
        <x-field name="email" type="email" label="Email" :value="request('email')" required autocomplete="email" hint="We’ll send you a link to confirm it." />
        <x-field name="password" type="password" label="Password" required autocomplete="new-password" hint="At least 8 characters." />
        <x-field name="password_confirmation" type="password" label="Password again" required autocomplete="new-password" />
        <x-visibility-choice />
        <x-button class="mt-1 w-full">Create account</x-button>
        <p class="-mt-1 text-center text-sm/6 text-pretty text-white/45">By signing up you agree to <a href="{{ route('privacy') }}#rules" class="text-white/70 underline decoration-white/20 underline-offset-2 hover:text-white">the rules</a>. <a href="{{ route('privacy') }}" class="text-white/70 underline decoration-white/20 underline-offset-2 hover:text-white">What’s stored and who sees it</a>.</p>
    </form>

    <x-slot:below>
        Already have an account? <a href="{{ route('login') }}" class="font-semibold text-white hover:text-midnight-200">Log in</a>
    </x-slot:below>
</x-layouts.auth>
