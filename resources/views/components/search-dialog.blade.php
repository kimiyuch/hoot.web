{{-- The search lightbox: opens with ⌘K / Ctrl K or the header button, results load as you type. --}}
<dialog id="search-dialog" aria-label="Search"
    class="m-0 mx-auto mt-[12vh] w-[calc(100%-2rem)] max-w-xl overflow-hidden rounded-2xl bg-night-900 p-0 text-white shadow-2xl inset-ring inset-ring-white/10 backdrop:bg-black/75">
    <form action="{{ route('search') }}" method="get" role="search" class="grid grid-cols-1 border-b border-white/8">
        <input type="search" name="q" data-search-input autocomplete="off" aria-label="Find a show or movie" placeholder="Find a show or movie"
            class="col-start-1 row-start-1 bg-transparent py-4 pr-16 pl-12 text-base text-white placeholder:text-white/35 focus:outline-none [&::-webkit-search-cancel-button]:hidden">
        <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="pointer-events-none col-start-1 row-start-1 ml-4 size-5 self-center text-white/35"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.45 4.39l3.08 3.08a.75.75 0 1 1-1.06 1.06l-3.08-3.08A7 7 0 0 1 2 9Z" clip-rule="evenodd"/></svg>
        <button type="button" data-dialog-close class="col-start-1 row-start-1 mr-3 self-center justify-self-end rounded-md px-1.5 py-0.5 text-xs/5 font-medium text-white/45 inset-ring inset-ring-white/10 hover:text-white">Esc</button>
    </form>
    <div data-search-results aria-live="polite" class="scrollbar-thin max-h-[60vh] overflow-y-auto overscroll-contain p-2">
        <p class="px-3 py-6 text-center text-sm/6 text-white/40">Search shows on TVmaze and movies on TMDB.</p>
    </div>
</dialog>
