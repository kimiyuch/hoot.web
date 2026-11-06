@props(['title' => null])
<!DOCTYPE html>
<html lang="en" class="scheme-only-dark bg-night-950 antialiased" data-timezone="{{ \App\Timezone::for(request()) }}">
<head>
    <x-head :title="$title ? $title.' · Possum' : 'Possum'" />
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

    <x-toast />
</body>
</html>
