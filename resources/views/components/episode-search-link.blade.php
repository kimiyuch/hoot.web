{{-- A small chain link to the search someone set up in Settings → Extras, for episodes that are out. --}}
@props(['template', 'show', 'episodes'])

@php $host = \App\Services\EpisodeSearch::host($template); @endphp

<a href="{{ \App\Services\EpisodeSearch::url($template, $show, $episodes) }}" target="_blank" rel="noopener noreferrer nofollow"
    title="Search on {{ $host }}" aria-label="Search for {{ $show }} on {{ $host }}"
    {{ $attributes->class('relative inline-flex shrink-0 items-center justify-center rounded text-white/25 hover:text-white/70 focus-visible:text-white/70') }}>
    <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" class="size-3.5"><path fill-rule="evenodd" d="M8.914 6.025a.75.75 0 0 1 1.06 0 3.5 3.5 0 0 1 0 4.95l-2 2a3.5 3.5 0 0 1-5.396-4.402.75.75 0 0 1 1.251.827 2 2 0 0 0 3.085 2.514l2-2a2 2 0 0 0 0-2.828.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/><path fill-rule="evenodd" d="M7.086 9.975a.75.75 0 0 1-1.06 0 3.5 3.5 0 0 1 0-4.95l2-2a3.5 3.5 0 0 1 5.396 4.402.75.75 0 0 1-1.251-.827 2 2 0 0 0-3.085-2.514l-2 2a2 2 0 0 0 0 2.828.75.75 0 0 1 0 1.06Z" clip-rule="evenodd"/></svg>
</a>
