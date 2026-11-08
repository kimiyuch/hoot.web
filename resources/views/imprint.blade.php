{{-- Who runs Hoot and how to reach them. Public; the details are in config/hoot.php ('operator'). --}}
@php
    $operator = config('hoot.operator');
    $contact = config('hoot.report_email');
    $h2 = 'mt-14 font-display text-xl font-semibold tracking-tight text-white';
    $p = 'mt-4 text-base/7 text-pretty text-white/70';
    $link = 'text-white underline decoration-white/30 underline-offset-2 hover:decoration-white';
@endphp
<x-layouts.page title="Imprint · Hoot" description="Who runs Hoot and how to reach them.">
    <h1 class="font-display text-4xl font-semibold tracking-tight text-balance">Imprint</h1>
    <p class="mt-8 text-lg/8 text-pretty text-white/75">Hoot is a small side project, free and without ads, run by:</p>

    <address class="mt-6 text-base/7 text-white/80 not-italic">
        {{ $operator['brand'] }}<br>
        {{ $operator['name'] }}<br>
        {{ $operator['street'] }}<br>
        {{ $operator['postcode'] }} {{ $operator['town'] }}<br>
        {{ $operator['country'] }}
        @if ($contact)
            <br><a href="mailto:{{ $contact }}" class="{{ $link }}">{{ $contact }}</a>
        @endif
    </address>

    <h2 class="{{ $h2 }}">Responsible</h2>
    <p class="{{ $p }}">{{ $operator['name'] }}, at the address above, is responsible for this website, its content and, under Swiss data protection law and the GDPR, for the data you give Hoot. What that is and who sees it is in <a href="{{ route('privacy') }}" class="{{ $link }}">Privacy and rules</a>.</p>

    <h2 class="{{ $h2 }}">Content and links</h2>
    <p class="{{ $p }}">Episode and release dates, descriptions, ratings, posters and streaming services come from <a href="https://www.tvmaze.com" target="_blank" rel="noopener" class="{{ $link }}">TVmaze</a> and <a href="https://www.themoviedb.org" target="_blank" rel="noopener" class="{{ $link }}">TMDB</a> and can be wrong or out of date. Links to streaming services and other sites lead to pages Hoot has no say over. Profiles and bios are written by the people who use Hoot; you can report one from the ⋯ menu on it.</p>
</x-layouts.page>
