{{-- Public or private profile, as two radio cards. Used at sign-up, in Settings and when picking a username. --}}
@props(['value' => 'public', 'bag' => 'default'])

@php
    $value = old('visibility', $value);
    $options = [
        'public' => ['Public', 'Anyone with a Hoot account can see your profile: your favourites and what you follow.'],
        'private' => ['Private', 'Only your name and username show. People ask to follow you, and only those you accept see the rest.'],
    ];
@endphp

<fieldset {{ $attributes }}>
    <legend class="text-base/6 font-medium text-white/80 sm:text-sm/6">Profile</legend>
    <div class="mt-2 grid grid-cols-1 gap-2">
        @foreach ($options as $key => [$label, $description])
            <label class="flex cursor-pointer gap-3 rounded-lg p-3 inset-ring inset-ring-white/10 hover:bg-white/3 has-checked:bg-white/5 has-checked:inset-ring-midnight-400/60">
                <input type="radio" name="visibility" value="{{ $key }}" @checked($value === $key) class="mt-1 size-4 shrink-0 accent-midnight-400">
                <span>
                    <span class="block text-base/6 font-medium sm:text-sm/6">{{ $label }}</span>
                    <span class="block text-sm/5 text-pretty text-white/50">{{ $description }}</span>
                </span>
            </label>
        @endforeach
    </div>
    @if ($message = $errors->getBag($bag)->first('visibility'))
        <p class="mt-2 text-base/6 text-calendar-400 sm:text-sm/6">{{ $message }}</p>
    @endif
</fieldset>
