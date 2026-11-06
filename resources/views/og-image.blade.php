{{-- Source for public/images/og.png (1200×630), the link preview of the landing page. Regenerate with scripts/og-image.sh. --}}
<!DOCTYPE html>
<html lang="en" class="antialiased">
<head>
    <meta charset="utf-8">
    @fonts
    @vite(['resources/css/app.css'])
</head>
<body class="m-0">
    <div class="relative isolate flex h-[630px] w-[1200px] items-center gap-x-14 overflow-hidden bg-night-950 px-24 font-sans text-white">
        {{-- The landing page's glow from the top. --}}
        <div class="absolute inset-x-0 top-0 -z-10 h-[520px] bg-linear-to-b from-midnight-900/90 to-transparent"></div>

        <img src="{{ asset('images/hoot.png') }}" alt="" class="-ml-6 size-[24rem] shrink-0">
        <div>
            <div class="font-display text-[7rem]/none font-semibold tracking-tight">Hoot</div>
            <p class="mt-6 max-w-[16ch] font-display text-5xl/tight font-semibold tracking-tight text-balance">
                Never miss a <span class="text-midnight-300">new episode</span>
            </p>
            <p class="mt-6 max-w-[34ch] text-2xl/9 text-pretty text-white/60">Every episode and release you follow, on every service, in your timezone.</p>
            <p class="mt-4 text-2xl font-medium text-midnight-300">hoot.kimiyu.ch</p>
        </div>
    </div>
</body>
</html>
