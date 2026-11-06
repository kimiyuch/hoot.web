# Hoot as a network: plan

Status: decided 2026-11-06 (see "Decisions" at the end); steps 0 (favourites), 1 (usernames and profiles), 2 (following people, blocking) and 3 (watch status, ratings) are built. This is what it would take to turn Hoot from a personal release tracker into a small network for movie and TV nerds: you follow people, see what they follow and watch, and get a thread of what the people you follow are up to.

## What it means

Today Hoot is private by design. Nobody sees anybody else, the only shared thing is the catalog of shows and movies, and the only "status" is *following* a show or movie (which really means "tell me when it comes out").

A network changes three things:

1. **People become visible.** Users get a public face: a handle, a name, a profile page with what they follow and watch.
2. **Following gets a second meaning.** You can follow a *person* as well as a *show*. The two must not be confused in the UI (see "Words" below).
3. **What you do becomes news.** Following a show, starting it, finishing it, rating it: these become events that other people see in a thread.

That brings in things Hoot doesn't have to think about today: privacy, consent, blocking, spam, and what happens to the thread when a show is deleted.

## Words

"Follow" is already taken by shows and movies, and it's the core of the app. Suggested split, to decide before building:

- Shows and movies: keep **Follow** (it means "keep me posted on releases").
- People: also **Follow**, since it's what every network calls it, but always with a person next to it ("Follow Pip"). The lists are **Followers** and **Following people**, the sidebar's "Following" (shows) stays as is.
- Alternative if that's too muddy: shows and movies get **Track** / **Tracking**. Bigger change, clearer later. Recommendation: keep Follow for both and let context do the work; revisit if testers trip over it.

## Building blocks

### 1. Profiles

- `users.username`: unique, lowercase, `[a-z0-9_]{3,20}`, chosen at sign-up (existing users pick one on their next visit). Profile URL `/@pip` (route `/{username}` with an `@` prefix, so it can't clash with pages).
- `users.bio` (short, plain text, ~160 chars), optional.
- Avatar: v1 without uploads. Use the initial like the header does now, on a colour picked from the user id (or a small set of owl variants). Uploads later (Hostpoint shared hosting: store in `storage/app/public`, resize with GD, max 1 MB).
- **Profile page** `/@pip`: name, handle, bio, follower/following counts, a Follow button, what they're watching now, what they follow (the same panel cards as Discover), their recent activity.
- Email and the calendar token stay private, always.

### 2. Following people

- Table `follows`: `follower_id`, `followed_id`, `created_at`, `accepted_at` (null = request pending), unique on the pair, both foreign keys cascade on delete.
- One-way, like Letterboxd or Instagram: I can follow you without you following me.
- **Public or private account** (`users.is_private`, default to decide):
  - Public: anyone signed in can follow you and see your profile and activity.
  - Private: following needs your approval (follow requests), and only approved followers see your activity and library. Your profile shows only name, handle and counts.
- Unfollow, remove a follower, **block** (table `blocks`: blocker_id, blocked_id). Blocking removes follows both ways and hides each from the other everywhere (profile, search, thread).
- Pages: Followers and Following lists on each profile, a "Requests" list in Settings for private accounts.

### 3. Watching (a real status)

Following a show means "tell me about releases"; it says nothing about watching. For "started watching" there has to be a watch status. Add it to the existing pivots `show_user` and `movie_user`:

- `status`: `plan` (want to watch), `watching`, `watched` (finished), `paused`, `dropped`; null means just following for releases.
- `started_at`, `finished_at`, `rating` (1–10 or 1–5 stars, to decide), maybe a short `note`.
- Optional, bigger step: per-episode tracking (`episode_user`: user_id, episode_id, watched_at). Gives "Pip watched S02E04", progress bars and "next to watch" (which overlaps with the open idea "marking episodes as watched"). Not needed for the first version; season-level or show-level status is enough to start.
- UI: the Follow button grows a small status menu (Follow → Want to watch / Watching / Watched / Dropped). The "Following" details in For you show and change the status.

### 3b. Favourites, and how they fit with following

Favourites answer a different question than following. They're three separate things a user can say about a show or movie:

| | Means | Typical for | Changes |
|---|---|---|---|
| **Follow** | "Tell me when something comes out" | Running shows, upcoming movies | The timeline, the calendar feed, the refresh |
| **Watch status** | "Where I am with it" | Anything you watch | Your progress, the thread |
| **Favourite** | "This is part of my taste" | Often finished things: *The Wire*, *Arrival* | Your profile, the thread |

They're independent. *The Wire* can be a favourite without being followed (no new episodes, it would only sit in "Following" doing nothing). *Severance* can be followed and watched and not (yet) a favourite. A heart on something you follow doesn't follow it twice.

**One library row instead of more tables.** Today a row in `show_user` / `movie_user` means "following". Turn that row into "this is in your library" and make following one of its flags:

- `following` (bool, default true for existing rows): releases go into For you and the calendar.
- `status`, `started_at`, `finished_at`, `rating` (from 3.).
- `favourite_position` (null = not a favourite; 1, 2, 3 … = its place in your favourites, so they can be ordered).

The row is deleted only when all of them are empty (not following, no status, no rating, not a favourite), and the show or movie is deleted from the catalog only when no row points at it any more. `Show::followed()` / `Movie::followed()` become `where following = true`, so **`hoot:refresh` only refreshes what someone follows**: a favourite-only classic costs no API calls after it's added. What you see of it (poster, title, year, overview) is enough from the first fetch.

**Adding a favourite** without following: the cards (search, Discover, profiles) get a heart next to Follow. Favouriting something not in the catalog yet fetches it the same way following does, but with `following = false`, so it never shows up in the timeline.

**Where favourites show:**
- **On your profile, at the top:** "Favourite shows" and "Favourite movies", in your order. This is what makes a profile feel like a person (Letterboxd's "four favourites" works for exactly this reason). Suggestion: show the first 4–6 of each prominently and the rest behind "All favourites".
- **Not in For you's timeline**, which stays about what's coming. At most a small "Favourites" entry in the sidebar or settings to manage and reorder them. Better: manage them on your own profile (drag to reorder, or up/down buttons with no JS needed).
- **In the thread:** "Pip added *The Wire* to their favourites" (one entry per add, never for removing).
- **Socially:** "2 people you follow love this" on a card, alongside "3 people you follow watch this".

**Limit or not?** An unlimited list turns into a second library. A cap (for example 10 shows and 10 movies) keeps favourites meaningful and the profile tidy. Recommendation: a cap, with the top 4 shown big.

**Without the network,** favourites still make sense on their own and could be built first: the library-row change, a heart on cards, and a favourites block in Settings or on a private profile page. It's also a good first step towards step 3 (watch status), since it does the same table change.

### 4. The thread (activity feed)

A page (or tab) that lists what the people you follow did, newest first:

- "Pip started watching *Severance*"
- "Rue followed *Dune: Part Three*"
- "Pip finished *The Bear* season 4 · ★★★★"
- "Max started following you" (in the thread or in notifications, to decide)

**Storage:** table `activities`: `id`, `user_id`, `type` (`followed`, `started`, `finished`, `rated`, `dropped`, `followed_user`), `subject_type` + `subject_id` (show, movie, episode, user), `data` (JSON: a snapshot with the title, poster URL, season, rating), `created_at`. Index on (`user_id`, `created_at`).

**Reading it:** fan-out on read: `activities where user_id in (people I follow and who accepted) order by created_at desc`, paginated with a cursor. Fine for SQLite and the expected scale (hundreds, maybe thousands of users). No queue, no websockets, which suits Hostpoint shared hosting.

**Rules to keep it calm and honest:**
- Collapse noise: following five shows in a minute becomes one entry ("Pip followed 5 shows"). Unfollowing within a few minutes removes the entry again.
- Don't post unfollows, ever.
- Backfilling: existing follows don't create activity (otherwise the first thread is a flood). Only actions from launch on.
- Per-user switch "Share my activity" (on by default for public accounts?), and per-item "don't show this" maybe later.

**Catalog deletion:** today an unfollowed show nobody else follows is deleted. Activities must survive that: keep a snapshot in `data` (title, poster, year) and treat the subject as optional when rendering, or stop deleting catalog items that have activity. Recommendation: the snapshot, plus not deleting shows that someone has a watch status on.

### 5. Finding people

- Search: the ⌘K lightbox gets a third group, People (by handle and name), next to Shows and Movies.
- On a show or movie: "3 people you follow watch this" with their avatars. Cheap to build once follows and statuses exist, and probably the most useful social bit.
- Suggestions ("people who follow what you follow") later.
- Invite link: `/@pip` shared outside, signs up and follows Pip in one go.

### 6. Notifications

- In-app first: a small dot on the avatar or a bell: new followers, follow requests, accepted requests.
- Mail: optional, off by default, a weekly digest at most. Laravel notifications with the existing mail look.
- Push: later (no app; web push would need a service worker).

### 7. Interaction (later, maybe never)

Likes on activities, comments, reviews, lists ("my favourite heist shows"). Each one adds moderation work. Suggest leaving these out of the first version and seeing whether the thread alone feels alive.

## Pages and navigation

The header has For you and Discover. Options:

- **A third tab "Friends"** (or "People"/"Thread") with the activity thread and a "find people" box. Clear, but the user wanted only two pages so far.
- **The thread inside For you**: a "From people you follow" block in the sidebar, the full thread on the profile/people page. Keeps the two-page shape.

Recommendation: start with the sidebar block on For you plus `/@handle` profiles; add a third tab only if the thread becomes something people come for. Pick with `/ui ideas` once there's data.

New routes, roughly: `/@{username}`, `/@{username}/followers`, `/@{username}/following`, `/people` (thread + search, if it becomes a page), `POST/DELETE /@{username}/follow`, settings for privacy, requests and blocks.

## Privacy, safety and law

- Hoot is in Switzerland (kimiyu.ch): the Swiss data protection act (nDSG) applies, and the GDPR for EU users. Needs a short **privacy policy** page (what's public, what's stored, who sees what, how to delete) and probably **terms** (rules for usernames and bios, that accounts can be removed).
- Watching habits are personal. Make the default visibility a deliberate choice, explain it at sign-up, and let people change it any time.
- Delete account already exists; it must also remove follows, activities, blocks.
- Data export (JSON of your library and activity) is good practice and cheap.
- Abuse: report a profile (mail to the admin is enough at this size), an admin way to suspend a user (`users.suspended_at`), rate limits on following and on sign-up (Laravel's `throttle`), reserved usernames (`admin`, `hoot`, `kimiyu`, page names).
- Landing page and indexing: today only the landing page is indexable. Public profiles could be indexable (nice for sharing, but it publishes people's taste to Google). Recommendation: `noindex` profiles at first, opt-in later.

## Technical notes

- Stays Blade + a little vanilla JS. Follow buttons post forms like today; the thread is server-rendered with "Load more" (cursor pagination).
- SQLite is fine at this scale; add indexes on `follows (followed_id)`, `activities (user_id, created_at)`.
- Activity is written in one place: `Library` (follow/unfollow, status changes) and a new `Network` service (follow/unfollow people, requests, blocks), so controllers don't create activities directly.
- Policies (`UserPolicy::view`, `viewActivity`) decide visibility in one place: blocked, private and not following, suspended.
- Tests: privacy is where bugs hurt. Feature tests for every "who can see what" case (public, private pending, private accepted, blocked both ways, suspended).
- The refresh job doesn't change. Release notifications about shows stay per user, as now.

## Suggested order

0. **Favourites** (3b), possible on their own before anything social: turns `show_user`/`movie_user` into library rows with a `following` flag, adds a heart on cards and an ordered favourites list. Also lays the ground for the watch status.
1. **Usernames and profiles** (`/@handle`, read-only, shows what someone follows). Small, and useful on its own for sharing.
2. **Following people**, public accounts only, block. Followers/following lists, people in search.
3. **Watch status** on shows and movies (want to watch / watching / watched / dropped, rating).
4. **The thread**: activities table, written by `Library`, shown on For you, collapsed and snapshotted.
5. **Private accounts and follow requests**, privacy policy, settings for sharing.
6. **"People you follow watch this"** on cards and in search.
7. Notifications (in-app, then an optional digest). Episode tracking. Likes/comments only if there's demand.

Steps 1–4 are the smallest version that does what the idea asks for; 5 should land before inviting strangers.

## Decisions (2026-11-06)

- **Visibility:** public by default, changeable in Settings and explained at sign-up.
- **Thread:** part of For you (a "From people you follow" block); a third tab only if people come for it.
- **Words:** "Follow" for both people and shows/movies; context does the work.
- **Ratings:** ½–5 stars, no reviews.
- **Episode tracking:** later. Show/movie-level watch status is enough to start.
- **Favourites:** capped at 10 shows + 10 movies (top 4 big on the profile), built first (step 0). **Built:** `favourite_position` and `following` on `show_user` / `movie_user`, a heart on every card and in For you's Following, managed (reorder, remove) in Settings → Favourites until profiles exist.
- **Existing users:** asked on their next visit to pick a username and visibility (one-time screen, required). **Built:** `/choose-username`.
- **Following private accounts (step 2):** not possible until follow requests exist (step 5): no Follow button, and `Network::canFollow` refuses. Followers from before an account went private keep seeing it.
- **Blocking (step 2):** the blocked person gets a 404 on the blocker's profile and lists; the blocker still sees who it is, with Unblock (and Settings → Blocked people). Unblocking doesn't bring follows back.
- **Finding people (step 2):** its own mode in Discover (What: Shows / Movies / People) rather than a third header tab: a name search, "Follow what you follow", popular and new. Private accounts only show up in the name search, not in those lists. For you suggests a few people until you follow someone.
- **Watch status (step 3):** shows have all five statuses, movies only Want to watch and Watched. Status and rating are independent of following and favourites. Profiles show Watching now, Watched lately and Want to watch (public like the rest of a public profile).
- **Sign-up:** stays open. Lean on rate limits, block, reserved usernames, and the report/suspend tools from step 5.
