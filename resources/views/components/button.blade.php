@props(['variant' => 'primary'])
<button {{ $attributes->merge(['type' => 'submit'])->class([
    'rounded-lg px-3.5 py-2 text-base/6 font-semibold focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-midnight-400 sm:text-sm/6',
    'bg-white text-night-950 hover:bg-midnight-100' => $variant === 'primary',
    'bg-white/8 font-medium text-white hover:bg-white/12' => $variant === 'secondary',
    'bg-calendar-600 text-white hover:bg-calendar-500' => $variant === 'danger',
]) }}>{{ $slot }}</button>
