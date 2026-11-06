@props(['title', 'heading' => null, 'description' => null])
{{-- Log in, sign up and the other account pages: one small card in the middle of the night sky. --}}
<!DOCTYPE html>
<html lang="en" class="scheme-only-dark bg-night-950 antialiased">
<head>
    <x-head :title="$title.' · Hoot'" />
</head>
<body class="font-sans text-white">
    <div class="relative isolate flex min-h-dvh flex-col items-center justify-center px-4 py-12">
        <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[40rem] bg-linear-to-b from-midnight-900/70 to-transparent"></div>

        <div class="w-full max-w-sm">
            <a href="{{ url('/') }}" class="mx-auto flex w-fit flex-col items-center gap-3 font-display text-3xl font-semibold tracking-tight">
                <img src="{{ asset('images/hoot-head.svg') }}" alt="" class="size-16">
                Hoot
            </a>

            <div class="mt-8 rounded-2xl bg-night-900 p-6 inset-ring inset-ring-white/5 sm:p-8">
                @if ($heading)
                    <h1 class="font-display text-xl font-semibold tracking-tight">{{ $heading }}</h1>
                @endif
                @if ($description)
                    <p class="mt-1 text-base/7 text-pretty text-white/55 sm:text-sm/6">{{ $description }}</p>
                @endif
                <div @class(['mt-6' => $heading || $description])>
                    {{ $slot }}
                </div>
            </div>

            @isset($below)
                <div class="mt-6 text-center text-base/7 text-white/55 sm:text-sm/6">{{ $below }}</div>
            @endisset
        </div>
    </div>

    <x-toast />
</body>
</html>
