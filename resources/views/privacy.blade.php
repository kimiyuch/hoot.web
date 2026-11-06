{{-- What Hoot stores, who sees what, and the few rules. Public, readable before signing up. --}}
@php
    $contact = config('hoot.report_email');
    $h2 = 'mt-14 font-display text-xl font-semibold tracking-tight text-white';
    $p = 'mt-4 text-base/7 text-pretty text-white/70';
    $ul = 'mt-4 list-disc space-y-2 pl-5 text-base/7 text-pretty text-white/70 marker:text-white/30';
    $th = 'py-2 pr-4 text-left text-sm/6 font-semibold text-white';
    $td = 'py-2 pr-4 align-top text-sm/6 text-white/70';
@endphp
<!DOCTYPE html>
<html lang="en" class="scheme-only-dark bg-night-950 antialiased">
<head>
    <x-head title="Privacy and rules · Hoot" description="What Hoot stores about you, who sees what, and the few rules." />
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
        <h1 class="font-display text-4xl font-semibold tracking-tight text-balance">Privacy and rules</h1>
        <p class="mt-2 text-sm/6 text-white/40">Last changed on 6 November 2026</p>
        <p class="mt-8 text-lg/8 text-pretty text-white/75">Hoot is a small side project by <a href="https://kimiyu.ch" class="text-white underline decoration-white/30 underline-offset-2 hover:decoration-white">kimiyu</a> in Switzerland. It keeps only what it needs to work, shows nothing about you that you didn’t choose to show, and has no ads, no analytics and no tracking.@if ($contact) Questions go to <a href="mailto:{{ $contact }}" class="text-white underline decoration-white/30 underline-offset-2 hover:decoration-white">{{ $contact }}</a>.@endif</p>

        <h2 class="{{ $h2 }}">What Hoot stores</h2>
        <ul role="list" class="{{ $ul }}">
            <li><strong class="font-medium text-white">Your account:</strong> name, username, email address, your password (only as a hash, never readable), bio, whether your profile is public or private, and whether you share your activity.</li>
            <li><strong class="font-medium text-white">Your library:</strong> the shows and movies you follow or made favourites, your watch status (with when you started and finished) and your ratings.</li>
            <li><strong class="font-medium text-white">People:</strong> who you follow, who follows you, follow requests, and who you blocked.</li>
            <li><strong class="font-medium text-white">Your activity:</strong> short entries like “followed Severance” or “finished The Bear”, shown to your followers while you share your activity.</li>
            <li><strong class="font-medium text-white">Your calendar link:</strong> a secret address for your calendar feed. Anyone with it can see what you follow, so you can make a new one in Settings.</li>
            <li><strong class="font-medium text-white">Cookies:</strong> a session cookie to keep you logged in (and a longer one if you tick “Keep me logged in”), one against forged forms, and one with your browser’s timezone so times are right. No others.</li>
        </ul>

        <h2 class="{{ $h2 }}">Who sees what</h2>
        <p class="{{ $p }}">Hoot is only for people with an account. Search engines only see the start page; profiles are hidden from them.</p>
        <div class="mt-6 overflow-x-auto">
            <table class="w-full min-w-[32rem] border-t border-white/10">
                <thead>
                    <tr class="border-b border-white/10">
                        <th class="{{ $th }}"></th>
                        <th class="{{ $th }}">Public profile</th>
                        <th class="{{ $th }}">Private profile</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <tr>
                        <td class="{{ $td }} font-medium text-white">Name, username, bio, follower counts</td>
                        <td class="{{ $td }}">Everyone on Hoot</td>
                        <td class="{{ $td }}">Everyone on Hoot</td>
                    </tr>
                    <tr>
                        <td class="{{ $td }} font-medium text-white">Favourites, what you watch and follow, ratings, who you follow and who follows you</td>
                        <td class="{{ $td }}">Everyone on Hoot</td>
                        <td class="{{ $td }}">Only followers you accepted</td>
                    </tr>
                    <tr>
                        <td class="{{ $td }} font-medium text-white">Your activity</td>
                        <td class="{{ $td }}">Your followers, while you share it</td>
                        <td class="{{ $td }}">Followers you accepted, while you share it</td>
                    </tr>
                    <tr>
                        <td class="{{ $td }} font-medium text-white">Email, password, calendar link</td>
                        <td class="{{ $td }}">Nobody</td>
                        <td class="{{ $td }}">Nobody</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p class="{{ $p }}">Someone you block can’t see your profile, lists or activity, and you don’t see theirs.</p>

        <h2 class="{{ $h2 }}">Other services</h2>
        <ul role="list" class="{{ $ul }}">
            <li>Show and episode data comes from <a href="https://www.tvmaze.com" class="text-white underline decoration-white/30 underline-offset-2">TVmaze</a>, movie data from <a href="https://www.themoviedb.org" class="text-white underline decoration-white/30 underline-offset-2">TMDB</a>. Hoot asks them from its server and tells them nothing about you.</li>
            <li>Posters are loaded by your browser straight from TVmaze’s and TMDB’s image servers, so they see your IP address, like any website you visit.</li>
            <li>Mails (confirming your address, resetting your password) go out through Hoot’s mail provider.</li>
            <li>Hoot runs at Hostpoint in Switzerland, which keeps the usual server logs for a short time.</li>
        </ul>

        <h2 class="{{ $h2 }}">Your choices</h2>
        <p class="{{ $p }}">In <a href="{{ route('settings') }}" class="text-white underline decoration-white/30 underline-offset-2">Settings</a> you can change all of the above at any time: make your profile private, stop sharing your activity, remove followers, block people, download everything Hoot has about you as a file, and delete your account. Deleting it removes your account, library, follows and activity right away.@if ($contact) For anything else, including your rights under Swiss data protection law and the GDPR, write to <a href="mailto:{{ $contact }}" class="text-white underline decoration-white/30 underline-offset-2">{{ $contact }}</a>.@endif</p>

        <h2 id="rules" class="{{ $h2 }} scroll-mt-8">The rules</h2>
        <ul role="list" class="{{ $ul }}">
            <li>Be yourself: no pretending to be someone else in your name, username or bio.</li>
            <li>No harassment, hate, threats, sexual content or spam, in a bio or anywhere else.</li>
            <li>One account per person, and not for bots.</li>
        </ul>
        <p class="{{ $p }}">You can report a profile from the ⋯ menu on it. Accounts that break the rules can be suspended or deleted. Hoot is free and comes without guarantees: dates and services come from TVmaze and TMDB and can be wrong, and the service can change or stop.</p>
    </main>

    <x-site-footer />
</body>
</html>
