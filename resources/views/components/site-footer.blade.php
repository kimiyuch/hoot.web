<footer class="border-t border-white/5">
    <div class="mx-auto flex max-w-[96rem] flex-col gap-4 px-4 py-10 sm:px-6 lg:flex-row lg:items-start lg:justify-between lg:gap-16 lg:px-10">
        <p class="max-w-[80ch] text-sm/6 text-pretty text-white/40">
            TV data from <a href="https://www.tvmaze.com" class="text-white/60 hover:text-white">TVmaze</a>
            (<a href="https://creativecommons.org/licenses/by-sa/4.0/" class="hover:text-white">CC BY-SA</a>).
            Movie data, posters and streaming services from <a href="https://www.themoviedb.org" class="text-white/60 hover:text-white">TMDB</a>.
            This product uses the TMDB API but is not endorsed or certified by TMDB.
        </p>
        <div class="flex shrink-0 flex-wrap gap-x-6 gap-y-2 text-sm/6 text-white/40">
            <a href="{{ route('calendar') }}" class="hover:text-white">Calendar feed</a>
            <a href="{{ config('possum.repository_url') }}" class="hover:text-white">GitHub</a>
            <a href="https://kimiyu.ch" class="hover:text-white">kimiyu.ch</a>
        </div>
    </div>
</footer>
