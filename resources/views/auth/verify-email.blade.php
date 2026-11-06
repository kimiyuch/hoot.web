<x-layouts.auth title="Confirm your email" heading="Check your inbox" description="We sent a link to {{ auth()->user()->email }}. Click it to confirm your email, and your For you page opens.">
    <div class="flex flex-wrap items-center gap-x-6 gap-y-3">
        <form method="post" action="{{ route('verification.send') }}">
            @csrf
            <x-button variant="secondary">Send it again</x-button>
        </form>
        <form method="post" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-base/6 text-white/55 hover:text-white sm:text-sm/6">Log out</button>
        </form>
    </div>
</x-layouts.auth>
