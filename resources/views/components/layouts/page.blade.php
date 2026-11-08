{{-- A plain public text page (privacy, imprint): logo, a way back or in, one narrow column, the site footer. --}}
@props(['title', 'description'])
<!DOCTYPE html>
<html lang="en" class="scheme-only-dark bg-night-950 antialiased">
<head>
    <x-head :title="$title" :description="$description" />
</head>
<body class="font-sans text-white">
    <header class="mx-auto flex max-w-3xl items-center justify-between gap-x-6 px-6 py-5">
        <a href="{{ url('/') }}" class="flex items-center gap-2.5 font-display text-xl font-semibold tracking-tight">
            <img src="{{ asset('images/hoot-head.svg') }}" alt="" class="size-8">
            Hoot
        </a>
        @auth
            <a href="{{ route('for-you') }}" class="text-base/6 font-medium text-white/70 hover:text-white sm:text-sm/6">Back to Hoot <span aria-hidden="true">→</span></a>
        @else
            <a href="{{ route('register') }}" class="rounded-full px-3.5 py-1.5 text-base/6 font-medium text-white inset-ring inset-ring-white/15 hover:bg-white/8 sm:text-sm/6">Sign up</a>
        @endauth
    </header>

    <main class="mx-auto max-w-3xl px-6 pt-10 pb-24">
        {{ $slot }}
    </main>

    <x-site-footer />
</body>
</html>
