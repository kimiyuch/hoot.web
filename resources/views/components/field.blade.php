@props(['name', 'label', 'type' => 'text', 'value' => null, 'bag' => 'default', 'hint' => null])
{{-- A labelled input with its validation error. Old input comes back after a failed submit (never for passwords). --}}
@php
    $id = $attributes->get('id', $name);
    $error = $errors->getBag($bag)->first($name);
@endphp
<div>
    <label for="{{ $id }}" class="block text-base/6 font-medium text-white/80 sm:text-sm/6">{{ $label }}</label>
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}"
        @if ($type !== 'password') value="{{ old($name, $value) }}" @endif
        @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
        {{ $attributes->except('id')->class([
            'mt-2 block w-full rounded-lg bg-white/5 px-3 py-2 text-base/6 text-white outline-1 -outline-offset-1 placeholder:text-white/30 focus:outline-2 focus:-outline-offset-2 focus:outline-midnight-400 sm:text-sm/6',
            'outline-white/10' => ! $error,
            'outline-calendar-400/70' => $error,
        ]) }}>
    @if ($error)
        <p id="{{ $id }}-error" class="mt-2 text-base/6 text-calendar-400 sm:text-sm/6">{{ $error }}</p>
    @elseif ($hint)
        <p class="mt-2 text-base/6 text-white/40 sm:text-sm/6">{{ $hint }}</p>
    @endif
</div>
