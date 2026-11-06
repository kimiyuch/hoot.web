{{-- The flash message after a form, bottom centre. app.js hides it after a few seconds. --}}
@php
    // Fortify flashes keys instead of sentences after some of its forms.
    $status = match ($status = session('status')) {
        'verification-link-sent' => 'A new link is on its way. Check your inbox.',
        'profile-information-updated' => 'Saved.',
        'password-updated' => 'Password changed.',
        default => $status,
    };
@endphp
@if ($status)
    <p role="status" data-toast class="fixed inset-x-4 bottom-4 z-50 mx-auto w-fit max-w-[calc(100%-2rem)] transition-opacity duration-500 data-hidden:opacity-0 rounded-full bg-night-700 px-4 py-2 text-base/6 text-white inset-ring inset-ring-white/10 sm:bottom-6 sm:text-sm/6">
        {{ $status }}
    </p>
@endif
