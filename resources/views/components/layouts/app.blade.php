@props(['title' => null])
<!DOCTYPE html>
<html lang="en" class="scheme-only-dark bg-night-950 antialiased" data-timezone="{{ \App\Timezone::for(request()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $title ? $title.' · Possum' : 'Possum' }}</title>
    <meta name="description" content="When the movies and shows I follow come out, on every service, in my timezone.">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#1d1022">

    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="alternate" type="text/calendar" title="Possum" href="{{ route('calendar') }}">

    @fonts

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-white">
    <div class="relative isolate flex min-h-dvh flex-col">
        {{-- The icon's plum sky, fading into the night. --}}
        <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[40rem] bg-linear-to-b from-midnight-900/70 to-transparent"></div>

        <x-site-header />

        <main class="flex-1 pt-10 pb-24 sm:pt-12">
            <div class="mx-auto max-w-[96rem] px-4 sm:px-6 lg:px-10">
                {{ $slot }}
            </div>
        </main>

        <x-site-footer />
    </div>

    <x-search-dialog />

    @if (session('status'))
        <p role="status" data-toast class="fixed inset-x-4 bottom-4 z-50 mx-auto w-fit max-w-[calc(100%-2rem)] transition-opacity duration-500 data-hidden:opacity-0 rounded-full bg-night-700 px-4 py-2 text-base/6 text-white inset-ring inset-ring-white/10 sm:bottom-6 sm:text-sm/6">
            {{ session('status') }}
        </p>
    @endif
</body>
</html>
