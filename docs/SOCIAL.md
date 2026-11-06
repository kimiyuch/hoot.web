# Hoot as a network: plan

Status: idea, nothing built yet (2026-11-06). This is what it would take to turn Hoot from a personal release tracker into a small network for movie and TV nerds: you follow people, see what they follow and watch, and get a thread of what the people you follow are up to.

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

1. **Usernames and profiles** (`/@handle`, read-only, shows what someone follows). Small, and useful on its own for sharing.
2. **Following people**, public accounts only, block. Followers/following lists, people in search.
3. **Watch status** on shows and movies (want to watch / watching / watched / dropped, rating).
4. **The thread**: activities table, written by `Library`, shown on For you, collapsed and snapshotted.
5. **Private accounts and follow requests**, privacy policy, settings for sharing.
6. **"People you follow watch this"** on cards and in search.
7. Notifications (in-app, then an optional digest). Episode tracking. Likes/comments only if there's demand.

Steps 1–4 are the smallest version that does what the idea asks for; 5 should land before inviting strangers.

## Open questions

- Default visibility: public or private? (Letterboxd: public. Hoot so far: completely private.)
- Is the thread a third tab or part of For you?
- One "Follow" word for both, or Track for shows/movies?
- Ratings: 5 stars with halves, or 10 points? Reviews at all?
- Episode-level tracking now or later?
- Should existing users be asked to pick a username (and visibility) on their next visit, with a clear explanation that Hoot became social?
- Invite-only at first (invite links) or open sign-up as now?
