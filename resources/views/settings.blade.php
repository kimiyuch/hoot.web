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
                <p class="{{ $intro }}">Your name, username and bio are on <a href="{{ $user->profileUrl() }}" class="text-white/80 underline decoration-white/30 underline-offset-2 hover:text-white">your profile</a>. Your email never is. If you change it, we send a link to the new address to confirm it.</p>
            </div>
            <form method="post" action="{{ route('user-profile-information.update') }}" class="flex flex-col gap-5">
                @csrf
                @method('put')
                <x-field name="name" label="Name" :value="$user->name" bag="updateProfileInformation" required autocomplete="name" />
                <x-field name="username" label="Username" :value="$user->username" bag="updateProfileInformation" required autocomplete="username" autocapitalize="none" spellcheck="false" maxlength="20"
                    hint="Changing it changes your profile’s address; the old one stops working." />
                <x-field name="bio" label="Bio" :value="$user->bio" bag="updateProfileInformation" maxlength="160" placeholder="What you watch, in a sentence" hint="Optional. Up to 160 characters." />
                <x-field name="email" type="email" label="Email" :value="$user->email" bag="updateProfileInformation" required autocomplete="email" />
                <x-visibility-choice :value="$user->is_private ? 'private' : 'public'" bag="updateProfileInformation" />
                <label class="flex cursor-pointer gap-3">
                    <input type="hidden" name="shares_activity" value="0">
                    <input type="checkbox" name="shares_activity" value="1" @checked(old('shares_activity', $user->shares_activity)) class="mt-1 size-4 shrink-0 accent-midnight-400">
                    <span>
                        <span class="block text-base/6 font-medium sm:text-sm/6">Share my activity</span>
                        <span class="block text-sm/5 text-pretty text-white/50">What you follow, start, finish, rate and favourite shows up for your followers under “From people you follow”. Turned off, nothing new is shared and what was shared is hidden.</span>
                    </span>
                </label>
                <x-button class="self-start">Save</x-button>
            </form>
        </section>

        <section id="invite" aria-labelledby="invite-title" class="{{ $section }} scroll-mt-24">
            <div>
                <h2 id="invite-title" class="font-display text-lg font-semibold tracking-tight">Invite friends</h2>
                <p class="{{ $intro }}">Send this link to a friend. They sign up through it and follow you once they’ve confirmed their email@if ($user->is_private), no request needed@endif.</p>
            </div>
            <x-invite-link :user="$user" class="self-start" />
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

        @if ($user->is_private || $requests->isNotEmpty())
            <section id="requests" aria-labelledby="requests-title" class="{{ $section }} scroll-mt-24">
                <div>
                    <h2 id="requests-title" class="font-display text-lg font-semibold tracking-tight">Follow requests</h2>
                    <p class="{{ $intro }}">Your profile is private, so people ask before following you. Accepted, they see your favourites, what you watch and follow, and your activity. Declining isn’t told to them.</p>
                </div>
                @if ($requests->isEmpty())
                    <p class="rounded-lg px-3 py-3 text-sm/6 text-white/40 inset-ring inset-ring-white/5 lg:self-start">No requests right now.</p>
                @else
                    <ul role="list" class="divide-y divide-white/5 lg:-mt-4">
                        @foreach ($requests as $person)
                            <x-person-row :person="$person">
                                <x-slot:actions>
                                    <livewire:person-actions :key="'request-'.$person->id" :user-id="$person->id" context="requests" />
                                </x-slot:actions>
                            </x-person-row>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endif

        <section id="blocked" aria-labelledby="blocked-title" class="{{ $section }} scroll-mt-24">
            <div>
                <h2 id="blocked-title" class="font-display text-lg font-semibold tracking-tight">Blocked people</h2>
                <p class="{{ $intro }}">You don’t see each other’s profiles, lists or search results, and neither can follow the other. Block someone from the menu on their profile. They aren’t told.</p>
            </div>
            @if ($blocked->isEmpty())
                <p class="rounded-lg px-3 py-3 text-sm/6 text-white/40 inset-ring inset-ring-white/5 lg:self-start">You haven’t blocked anyone.</p>
            @else
                <ul role="list" class="divide-y divide-white/5 lg:-mt-4">
                    @foreach ($blocked as $person)
                        <x-person-row :person="$person">
                            <x-slot:actions>
                                <livewire:person-actions :key="'blocked-'.$person->id" :user-id="$person->id" context="blocked" />
                            </x-slot:actions>
                        </x-person-row>
                    @endforeach
                </ul>
            @endif
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

        <section id="hidden" aria-labelledby="hidden-title" class="{{ $section }} scroll-mt-24">
            <div>
                <h2 id="hidden-title" class="font-display text-lg font-semibold tracking-tight">Hide from Discover</h2>
                <p class="{{ $intro }}">Never see these in Discover’s premieres and catalog. Search, For you and profiles still show everything.</p>
            </div>
            <form method="post" action="{{ route('settings.hidden') }}" class="flex flex-col gap-5">
                @csrf
                @method('put')
                @php $hiddenNow = old('hidden', \App\Services\Hidden::for($user)); @endphp
                <div class="grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2">
                    @foreach (config('hoot.hideable') as $key => $option)
                        <label class="flex cursor-pointer gap-3">
                            <input type="checkbox" name="hidden[]" value="{{ $key }}" @checked(in_array($key, $hiddenNow, true)) class="mt-1 size-4 shrink-0 accent-midnight-400">
                            <span>
                                <span class="block text-base/6 font-medium sm:text-sm/6">{{ $option['label'] }}</span>
                                @isset($option['hint'])
                                    <span class="block text-sm/5 text-pretty text-white/50">{{ $option['hint'] }}</span>
                                @endisset
                            </span>
                        </label>
                    @endforeach
                </div>
                <x-button variant="secondary" class="self-start">Save</x-button>
            </form>
        </section>

        <section id="extras" aria-labelledby="extras-title" class="{{ $section }} scroll-mt-24">
            <div>
                <h2 id="extras-title" class="font-display text-lg font-semibold tracking-tight">Extras</h2>
                <p class="{{ $intro }}">Off unless you set them, and only for you.</p>
            </div>
            <form method="post" action="{{ route('settings.extras') }}" class="flex flex-col gap-5">
                @csrf
                @method('put')
                <x-field name="episode_search_url" type="url" label="Episode search link" :value="$user->episode_search_url" bag="extras"
                    placeholder="https://example.org/search?q={query}" autocomplete="off" autocapitalize="none" spellcheck="false" maxlength="500"
                    hint="A search of your choice, a small link next to every episode that’s out. {query} becomes the show and episode, like “ted lasso s04e03”. Leave it empty for no link." />
                <x-button variant="secondary" class="self-start">Save</x-button>
            </form>
        </section>

        <section id="data" aria-labelledby="data-title" class="{{ $section }} scroll-mt-24">
            <div>
                <h2 id="data-title" class="font-display text-lg font-semibold tracking-tight">Your data</h2>
                <p class="{{ $intro }}">Everything Hoot keeps about you, as a JSON file: your account, library, ratings, the people you follow and who follows you, and your activity. What’s stored and who sees it is in the <a href="{{ route('privacy') }}" class="text-white/80 underline decoration-white/30 underline-offset-2 hover:text-white">privacy notes</a>.</p>
            </div>
            <div>
                <a href="{{ route('settings.export') }}" class="inline-block rounded-lg bg-white/8 px-3.5 py-2 text-base/6 font-medium hover:bg-white/12 sm:text-sm/6">Download my data</a>
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
                <p class="{{ $intro }}">Your account, your library, ratings and activity, who you follow and who follows you, and your calendar feed are deleted right away. This can’t be undone.</p>
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
