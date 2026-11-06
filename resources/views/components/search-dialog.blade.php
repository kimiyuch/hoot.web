{{-- The search lightbox: opens with ⌘K / Ctrl K or the header button. The Livewire part inside loads results as you type. --}}
<dialog id="search-dialog" aria-label="Search"
    class="m-0 mx-auto mt-[12vh] w-[calc(100%-2rem)] max-w-xl overflow-hidden rounded-2xl bg-night-900 p-0 text-white shadow-2xl inset-ring inset-ring-white/10 backdrop:bg-black/75">
    <livewire:search-panel />
</dialog>
