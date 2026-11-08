{{-- Your invite link with a Copy button (Settings, Discover → People). Copying is app.js's [data-copy]. --}}
@props(['user'])
<div {{ $attributes->class('flex flex-wrap items-center gap-2') }}>
    <p class="min-w-0 flex-1 truncate rounded-lg bg-white/5 px-3 py-2 font-mono text-sm/6 text-white/70 inset-ring inset-ring-white/10">{{ $user->inviteUrl() }}</p>
    <button type="button" data-copy="{{ $user->inviteUrl() }}" class="shrink-0 rounded-lg bg-white/8 px-3.5 py-2 text-base/6 font-medium hover:bg-white/12 sm:text-sm/6">Copy link</button>
</div>
