@php
    $steps = [
        ['title' => 'Follow what you watch', 'body' => 'Search for a show or a movie, or find one on Discover, and press Follow. That’s the whole setup.'],
        ['title' => 'Hoot keeps watch', 'body' => 'Every few hours it checks TVmaze and TMDB for new episodes, dates that moved and seasons that were just announced.'],
        ['title' => 'See what’s next', 'body' => 'Your For you page lists everything that comes out, day by day, at the time it comes out where you are.'],
    ];

    $features = [
        ['icon' => 'clock', 'title' => 'In your timezone', 'body' => 'A Sunday night premiere in New York is a Monday morning in Zurich. Hoot shows it the way your clock does.'],
        ['icon' => 'tv', 'title' => 'Every service', 'body' => 'Netflix, HBO Max, Apple TV, Prime Video, Disney+ and regular TV, all in one list. No more checking five apps.'],
        ['icon' => 'film', 'title' => 'Movies too', 'body' => 'Follow a movie and see when it’s in cinemas and when you can stream it, wherever it comes out first.'],
        ['icon' => 'calendar-days', 'title' => 'In your calendar', 'body' => 'Subscribe once in Apple Calendar or Google Calendar, and every episode and release shows up there by itself.'],
        ['icon' => 'sparkles', 'title' => 'What’s premiering', 'body' => 'Discover shows the new series and movies of the next and last few weeks, by service, so nothing slips past.'],
        ['icon' => 'squares-2x2', 'title' => 'A whole season at once', 'body' => 'When all episodes drop on the same day, that’s one line, not eight.'],
        ['icon' => 'star', 'title' => 'Ratings at a glance', 'body' => 'TVmaze ratings for shows and TMDB ratings for movies, right next to the dates.'],
        ['icon' => 'moon', 'title' => 'Up at night', 'body' => 'Owls are out after dark, which is also when most episodes come out. This one never misses a night.'],
    ];

    $faqs = [
        ['question' => 'Does Hoot stream anything?', 'answer' => 'No. It tells you when and where something comes out. You watch it on the service you already have.'],
        ['question' => 'Where do the dates come from?', 'answer' => 'Episodes come from TVmaze, movies and streaming services from TMDB. When they change a date, Hoot picks it up within a few hours.'],
        ['question' => 'What does it cost?', 'answer' => 'Nothing. Hoot is a small side project by kimiyu, with no ads and no tracking.'],
        ['question' => 'Can other people see what I follow?', 'answer' => 'No. Your For you page is yours. Only your calendar feed link shows your list, and you can make a new link at any time.'],
        ['question' => 'Can I delete my account?', 'answer' => 'Yes, at any time in Settings. Your account and what you follow are deleted with it.'],
    ];

    $link = 'text-base/6 font-medium text-white/60 hover:text-white sm:text-sm/6';
@endphp
<!DOCTYPE html>
<html lang="en" class="scheme-only-dark bg-night-950 antialiased motion-safe:scroll-smooth">
<head>
    <x-head title="Hoot: never miss a new episode" index>
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="Hoot">
        <meta property="og:title" content="Hoot: never miss a new episode">
        <meta property="og:description" content="Follow shows and movies, and Hoot keeps watch: every new episode and release, on every service, in your timezone.">
        <meta property="og:url" content="{{ url('/') }}">
        <meta property="og:image" content="{{ asset('images/hoot.png') }}">
    </x-head>
</head>
<body class="font-sans text-white">
    <div class="relative isolate">
        <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[48rem] bg-linear-to-b from-midnight-900/80 to-transparent"></div>

        <header class="mx-auto flex max-w-6xl items-center justify-between gap-x-6 px-6 py-5 lg:px-8">
            <a href="{{ url('/') }}" aria-label="Homepage" class="flex items-center gap-2.5 font-display text-xl font-semibold tracking-tight">
                <img src="{{ asset('images/hoot-head.svg') }}" alt="" class="size-8">
                Hoot
            </a>
            <nav class="flex items-center gap-x-8">
                <a href="#how-it-works" class="{{ $link }} max-md:hidden">How it works</a>
                <a href="#features" class="{{ $link }} max-md:hidden">Features</a>
                <a href="#faq" class="{{ $link }} max-md:hidden">Questions</a>
                <a href="{{ route('login') }}" class="{{ $link }}">Log in</a>
                <a href="{{ route('register') }}" class="rounded-full px-3.5 py-1.5 text-base/6 font-medium text-white inset-ring inset-ring-white/15 hover:bg-white/8 sm:text-sm/6">Sign up</a>
            </nav>
        </header>

        <main>
            {{-- Hero --}}
            <section class="pt-10 pb-24 sm:pt-16 sm:pb-32">
                <div class="mx-auto max-w-6xl px-6 text-center lg:px-8">
                    <h1 class="mx-auto max-w-[16ch] font-display text-5xl font-semibold tracking-tight text-balance sm:text-7xl">
                        Never miss a <span class="text-midnight-300">new episode</span>
                    </h1>
                    <p class="mx-auto mt-6 max-w-[48ch] text-lg/8 text-pretty text-white/60 sm:text-xl/8">
                        Follow the shows and movies you watch, and Hoot keeps watch: every new episode and release, on every service, at the time it comes out where you are.
                    </p>
                    <div class="mt-10 flex flex-wrap items-center justify-center gap-x-8 gap-y-4">
                        <a href="{{ route('register') }}" class="rounded-full bg-white px-5 py-3 text-base/6 font-semibold text-night-950 shadow-sm hover:bg-midnight-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-midnight-400">
                            Create your account
                        </a>
                        <a href="#how-it-works" class="inline-flex items-center gap-x-1.5 text-base/6 font-semibold text-midnight-200 hover:text-white">
                            See how it works
                            <x-heroicon-m-arrow-right class="size-5 shrink-0 fill-current" />
                        </a>
                    </div>
                    <p class="mt-6 text-base/7 text-white/45 sm:text-sm/6">Free · Shows and movies · Works on your phone and computer</p>

                    <x-landing.app-preview class="mx-auto mt-16 max-w-5xl text-left sm:mt-20" />
                </div>
            </section>

            {{-- How it works --}}
            <section id="how-it-works" class="scroll-mt-8 pb-24 sm:pb-32">
                <div class="mx-auto max-w-6xl px-6 lg:px-8">
                    <p class="text-base/7 font-semibold text-midnight-300">How it works</p>
                    <h2 class="mt-2 max-w-[30ch] font-display text-4xl font-semibold tracking-tight text-balance sm:text-5xl">
                        Follow once, know forever
                    </h2>

                    <ol role="list" class="mt-16 grid grid-cols-1 gap-12 sm:grid-cols-3">
                        @foreach ($steps as $step)
                            <li class="border-t border-white/10 pt-6">
                                <p class="font-display text-base/7 font-semibold text-midnight-300 tabular-nums">0{{ $loop->iteration }}</p>
                                <h3 class="mt-2 text-lg/7 font-semibold">{{ $step['title'] }}</h3>
                                <p class="mt-2 text-base/7 text-pretty text-white/60">{{ $step['body'] }}</p>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </section>

            {{-- Calendar --}}
            <section class="bg-night-900 py-24 sm:py-32">
                <div class="mx-auto grid max-w-6xl grid-cols-1 items-center gap-12 px-6 lg:grid-cols-2 lg:px-8">
                    <div>
                        <p class="text-base/7 font-semibold text-calendar-400">Your calendar</p>
                        <h2 class="mt-2 max-w-[24ch] font-display text-4xl font-semibold tracking-tight text-balance sm:text-5xl">
                            Already in the calendar you use
                        </h2>
                        <p class="mt-6 max-w-[48ch] text-lg/8 text-pretty text-white/60">
                            Every account comes with its own calendar feed. Subscribe once and new episodes show up next to your dentist appointment, at the right time, updated by themselves.
                        </p>
                    </div>

                    {{-- A tear-off calendar page, for the calendar feed. --}}
                    <div class="mx-auto w-full max-w-sm overflow-hidden rounded-2xl bg-white/5 inset-ring inset-ring-white/10" aria-hidden="true">
                        <div class="bg-calendar-600 px-6 py-3 text-center text-sm/6 font-semibold tracking-wide text-white uppercase">Monday, 9 November</div>
                        <ul role="list" class="divide-y divide-white/5 px-6 py-2 text-sm/6">
                            <li class="flex items-baseline justify-between gap-4 py-3"><span class="font-medium">Gumtree Lane S04E10</span><span class="text-white/50 tabular-nums">03:00</span></li>
                            <li class="flex items-baseline justify-between gap-4 py-3"><span class="text-white/45">Dentist</span><span class="text-white/35 tabular-nums">10:30</span></li>
                            <li class="flex items-baseline justify-between gap-4 py-3"><span class="font-medium">Salt &amp; Ember on Prime Video</span><span class="text-white/50">All day</span></li>
                        </ul>
                    </div>
                </div>
            </section>

            {{-- Features --}}
            <section id="features" class="scroll-mt-8 py-24 sm:py-32">
                <div class="mx-auto max-w-6xl px-6 lg:px-8">
                    <p class="text-base/7 font-semibold text-midnight-300">Features</p>
                    <h2 class="mt-2 max-w-[30ch] font-display text-4xl font-semibold tracking-tight text-balance sm:text-5xl">
                        One list for everything you watch
                    </h2>
                    <p class="mt-6 max-w-[48ch] text-lg/8 text-pretty text-white/60">
                        No feeds, no recommendations engine, no social anything. Just what comes out next, for what you follow.
                    </p>

                    <dl class="mt-16 grid grid-cols-1 gap-x-12 gap-y-12 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($features as $feature)
                            <div>
                                <dt class="flex items-start gap-x-3 text-base/7 font-semibold">
                                    <x-dynamic-component :component="'heroicon-m-'.$feature['icon']" class="size-5 h-lh shrink-0 fill-midnight-300" />
                                    {{ $feature['title'] }}
                                </dt>
                                <dd class="mt-1 pl-8 text-base/7 text-pretty text-white/60">{{ $feature['body'] }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </section>

            {{-- FAQ --}}
            <section id="faq" class="scroll-mt-8 pb-24 sm:pb-32">
                <div class="mx-auto grid max-w-6xl grid-cols-1 gap-12 px-6 lg:grid-cols-3 lg:px-8">
                    <div>
                        <p class="text-base/7 font-semibold text-midnight-300">Questions</p>
                        <h2 class="mt-2 max-w-[30ch] font-display text-4xl font-semibold tracking-tight text-balance sm:text-5xl">Good to know</h2>
                    </div>
                    <dl class="divide-y divide-white/10 lg:col-span-2">
                        @foreach ($faqs as $faq)
                            <div class="py-6 first:pt-0 last:pb-0">
                                <dt class="text-base/7 font-semibold">{{ $faq['question'] }}</dt>
                                <dd class="mt-2 max-w-[56ch] text-base/7 text-pretty text-white/60">{{ $faq['answer'] }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </section>

            {{-- Call to action --}}
            <section class="bg-midnight-950 py-24 sm:py-32">
                <div class="mx-auto max-w-6xl px-6 text-center lg:px-8">
                    <img src="{{ asset('images/hoot-head.svg') }}" alt="" class="mx-auto size-20">
                    <h2 class="mx-auto mt-8 max-w-[30ch] font-display text-4xl font-semibold tracking-tight text-balance sm:text-5xl">
                        Let Hoot stay up for you
                    </h2>
                    <p class="mx-auto mt-6 max-w-[48ch] text-lg/8 text-pretty text-white/60">
                        Make an account, follow a few shows, and the next episode is never a surprise again.
                    </p>
                    <div class="mt-10 flex flex-wrap items-center justify-center gap-x-8 gap-y-4">
                        <a href="{{ route('register') }}" class="rounded-full bg-white px-5 py-3 text-base/6 font-semibold text-night-950 shadow-sm hover:bg-midnight-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-midnight-400">
                            Create your account
                        </a>
                        <a href="{{ route('login') }}" class="inline-flex items-center gap-x-1.5 text-base/6 font-semibold text-midnight-200 hover:text-white">
                            Log in
                            <x-heroicon-m-arrow-right class="size-5 shrink-0 fill-current" />
                        </a>
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t border-white/5">
            <div class="mx-auto flex max-w-6xl flex-col gap-6 px-6 py-10 sm:flex-row sm:items-center sm:justify-between lg:px-8">
                <p class="text-sm/6 text-white/50">Hoot, made with <span aria-label="love">❤️</span> by <a href="https://kimiyu.ch" class="font-medium text-white/80 hover:text-white">kimiyu.ch</a></p>
                <nav class="flex flex-wrap gap-x-6 gap-y-2 text-sm/6 whitespace-nowrap text-white/50">
                    <a href="{{ route('login') }}" class="hover:text-white">Log in</a>
                    <a href="{{ route('register') }}" class="hover:text-white">Sign up</a>
                    <a href="{{ route('privacy') }}" class="hover:text-white">Privacy and rules</a>
                    <a href="{{ config('hoot.repository_url') }}" class="hover:text-white">GitHub</a>
                </nav>
            </div>
            <p class="mx-auto max-w-6xl px-6 pb-10 text-xs/5 text-pretty text-white/35 lg:px-8">
                TV data from TVmaze (CC BY-SA). Movie data and streaming services from TMDB. This product uses the TMDB API but is not endorsed or certified by TMDB.
            </p>
        </footer>
    </div>

    <x-toast />
</body>
</html>
