<x-layouts.app title="Settings">
    @php
        $section = 'grid grid-cols-1 gap-x-16 gap-y-6 border-t border-white/5 py-10 first:border-t-0 first:pt-0 lg:grid-cols-[minmax(0,20rem)_minmax(0,36rem)]';
        $intro = 'mt-1 max-w-[40ch] text-base/7 text-pretty text-white/50 sm:text-sm/6';
    @endphp

    <h1 class="font-display text-3xl font-semibold tracking-tight">Settings</h1>
    <p class="mt-1 text-sm/6 text-white/40">Logged in as {{ $user->email }}</p>

    <div class="mt-12">
        <section aria-labelledby="profile" class="{{ $section }}">
            <div>
                <h2 id="profile" class="font-display text-lg font-semibold tracking-tight">Profile</h2>
                <p class="{{ $intro }}">If you change your email, we send a link to the new address to confirm it.</p>
            </div>
            <form method="post" action="{{ route('user-profile-information.update') }}" class="flex flex-col gap-5">
                @csrf
                @method('put')
                <x-field name="name" label="Name" :value="$user->name" bag="updateProfileInformation" required autocomplete="name" />
                <x-field name="email" type="email" label="Email" :value="$user->email" bag="updateProfileInformation" required autocomplete="email" />
                <x-button class="self-start">Save</x-button>
            </form>
        </section>

        <section id="favourites" aria-labelledby="favourites-title" class="{{ $section }} scroll-mt-24">
            <div>
                <h2 id="favourites-title" class="font-display text-lg font-semibold tracking-tight">Favourites</h2>
                <p class="{{ $intro }}">The shows and movies that are part of your taste, in your order. A favourite doesn’t need to be followed: nothing about it shows up in For you unless you follow it too.</p>
            </div>
            <div class="flex flex-col gap-8">
                <livewire:favourites-list type="show" />
                <livewire:favourites-list type="movie" />
            </div>
        </section>

        <section aria-labelledby="password" class="{{ $section }}">
            <div>
                <h2 id="password" class="font-display text-lg font-semibold tracking-tight">Password</h2>
                <p class="{{ $intro }}">At least 8 characters.</p>
            </div>
            <form method="post" action="{{ route('user-password.update') }}" class="flex flex-col gap-5">
                @csrf
                @method('put')
                <x-field name="current_password" id="current-password" type="password" label="Current password" bag="updatePassword" required autocomplete="current-password" />
                <x-field name="password" id="new-password" type="password" label="New password" bag="updatePassword" required autocomplete="new-password" />
                <x-field name="password_confirmation" id="new-password-confirmation" type="password" label="New password again" bag="updatePassword" required autocomplete="new-password" />
                <x-button class="self-start">Change password</x-button>
            </form>
        </section>

        <section id="calendar" aria-labelledby="calendar-title" class="{{ $section }} scroll-mt-24">
            <div>
                <h2 id="calendar-title" class="font-display text-lg font-semibold tracking-tight">Calendar feed</h2>
                <p class="{{ $intro }}">Anyone with this link can see what you follow. If it got out, make a new one; the old link stops working.</p>
            </div>
            <div>
                <p class="rounded-lg bg-white/5 px-3 py-2 font-mono text-sm/6 break-all text-white/70 inset-ring inset-ring-white/10">{{ $user->calendarUrl() }}</p>
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <a href="{{ $user->calendarSubscribeUrl() }}" class="rounded-lg bg-white px-3.5 py-2 text-base/6 font-semibold text-night-950 hover:bg-midnight-100 sm:text-sm/6">Subscribe</a>
                    <button type="button" data-copy="{{ $user->calendarUrl() }}" class="rounded-lg bg-white/8 px-3.5 py-2 text-base/6 font-medium hover:bg-white/12 sm:text-sm/6">Copy link</button>
                    <form method="post" action="{{ route('settings.calendar') }}" class="sm:ml-auto">
                        @csrf
                        <button type="submit" class="px-2 py-2 text-base/6 text-white/55 hover:text-white sm:text-sm/6">Make a new link</button>
                    </form>
                </div>
            </div>
        </section>

        <section aria-labelledby="session" class="{{ $section }}">
            <div>
                <h2 id="session" class="font-display text-lg font-semibold tracking-tight">Log out</h2>
                <p class="{{ $intro }}">On this device. Your follows stay as they are.</p>
            </div>
            <form method="post" action="{{ route('logout') }}">
                @csrf
                <x-button variant="secondary">Log out</x-button>
            </form>
        </section>

        <section aria-labelledby="delete" class="{{ $section }}">
            <div>
                <h2 id="delete" class="font-display text-lg font-semibold tracking-tight">Delete account</h2>
                <p class="{{ $intro }}">Your account, what you follow, your favourites and your calendar feed are deleted right away. This can’t be undone.</p>
            </div>
            <form method="post" action="{{ route('settings.destroy') }}" class="flex flex-col gap-5">
                @csrf
                @method('delete')
                <x-field name="password" id="delete-password" type="password" label="Your password, to confirm" bag="deleteAccount" required autocomplete="current-password" />
                <x-button variant="danger" class="self-start">Delete my account</x-button>
            </form>
        </section>
    </div>
</x-layouts.app>
