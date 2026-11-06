# Hoot: progress

Handoff notes for the next session. The original brief is in the README's history (see "How it works" there); this file tracks what's done, what was decided and what's open.

Last updated: 2026-11-06.

## State

Working locally at https://hoot.web.test (Herd, secured with `herd secure hoot.web`). 25 tests pass (`php artisan test`), Pint is clean. `public/build` is built.

**Git:** in git since 2026-11-05 and pushed to https://github.com/kimiyuch/hoot.web (public, `main`). The tests run on GitHub Actions for every push and passed on the first push. `marceli-to` is a collaborator, so a plain `git push` works. **Not done yet:** the first deploy (`./deploy.sh`) and the Hostpoint cron job.

`.env` has a working `TMDB_TOKEN` (the long "API Read Access Token"). It has to be added to the server's `.env` by hand when deploying.

## Built

- **Renamed from Possum to Hoot** (2026-11-06): the name, the repo (`kimiyuch/hoot.web`), the local folder and Herd site (`hoot.web.test`), the subdomain in `deploy.sh` (`hoot.kimiyu.ch`), `config/hoot.php`, `HOOT_*` env vars, `hoot:refresh`, `HootController`. The new icon is a grey-lavender owl with amber eyes perched on a film clapperboard like croc's (the bear is on the slate), on the same plum. The parts are in `artwork/parts`; `python3 scripts/make-icon.py` renders everything. Tried and rejected on the way: a kookaburra, a tawny owl, a calendar page under the owl.
- **Accounts** (2026-11-06), the stackr way: Laravel Fortify with Blade views in `resources/views/auth` (log in with remember me, sign up, forgot/reset password, email verification, confirm password), card layout `components/layouts/auth`. Open sign-up; you must confirm your email before For you opens. Shows and movies are a shared catalog; who follows what is in `show_user` / `movie_user`. Unfollowing detaches, and something nobody follows any more is deleted; `hoot:refresh` only refreshes what someone follows. Each user has a secret `calendar_token`: the feed is `/calendar/{token}.ics`. **Settings** (`/settings`, your initial on the right of the header): name and email, password (Fortify's `PUT /user/…` routes), calendar link (copy, subscribe, make a new one), log out, delete account (needs the password; takes along what only that user followed). Locally there's `test@possum.local` / `possum-test-1` with the old sample follows attached.
- **Landing page** (`/`, `welcome.blade.php`; signed-in users are sent on to `/for-you`), in the kimiyu landing structure (wallaby/croc): hero with the icon, a still of For you (`components/landing/app-preview`, made-up shows), how it works, a calendar band, features, questions, a call to action and the "made with ❤️ by kimiyu.ch" footer. It's the only indexable page.
- **Mails:** Laravel's mail and notification templates are published in `resources/views/vendor/{mail,notifications}` and restyled (white card on pale plum, plum pill button, `public/images/mail-icon.png` because mail clients don't show SVG, "made with ❤️ by kimiyu.ch" footer). The wording of the confirm-email and reset-password mails is in `AppServiceProvider::configureMails()`.
- **Header** (picked with /ui ideas on 2026-11-06): open and quiet, as wide as the page: the owl head and the Hoot wordmark on the left (links to For you), For you and Discover as a small segmented control in the middle, a search button and your initial (Settings) on the right. There's no surface at the top; once you scroll it gets a glass strip (`data-header`, `data-scrolled` set by app.js). Search is a magnifier with "Search ⌘K" (just the magnifier below `lg`; it shows "Ctrl K" on Windows and Linux). The wordmark hides below `md`. Earlier tries that the user rejected: a small pill on the right, then a centred one, then a wide pill with logo and avatar inside it. The full app icon stays as the favicon.
- **Search lightbox** (`components/search-dialog`): opens with ⌘K / Ctrl K or the header button, and loads results as you type (250 ms debounce). It fetches `/search?q=…` with the `X-Hoot-Partial` header and gets back only `partials/search-results` (6 shows, 6 movies, Follow buttons, "Show all results"). The full `/search` page still works without JS.
- **For you** (`/for-you`, `for-you.blade.php`; its own route since 2026-11-06, and Fortify's home after logging in): an "Up next" card, then a timeline by day ("Today", "Tomorrow", weekday, then "Sat 21 Nov") with rows separated by dividers. The sidebar has "Just released" (last 7 days), "Following" (every followed show and movie is a `<details>`: it opens to the rating with an Unfollow button next to it, the description, and then all episodes by season, newest season first, in a scrolling box with the next episode in mint; movies show their cinema and digital dates instead) and the calendar feed (Subscribe and Copy link). Times are in the browser's timezone (`tz` cookie, set by `resources/js/app.js`).
- **Discover** (`/discover`): one page with filters in a left sidebar on desktop and in a `<dialog>` drawer on mobile. **What** (Shows/Movies), **When**: *Premiering soon* (default), *Just premiered* or *Any time*, plus Service. The service filter shows Netflix, HBO Max, Apple TV, Prime Video and Disney+ (`hoot.featured_services`) and puts the rest behind "Show more", in a small scrolling box. A selected service from the rest stays visible. The sidebar isn't sticky. Soon and new come from the premieres that `hoot:refresh` builds and caches (shows: next/last 30 days; movies: a release still to come / one in the last 30 days, so a movie can be in both). The service list then only shows services that have something, with counts. *Any time* is TMDB discover with the extra filters sort, since year (default: the current year), minimum rating and country (default CH), cached for 6 h per page (`browse:v2:` key).
- **Old URLs** redirect with 301: `/following` → `/for-you`, `/premieres?tab=…` → `/discover?when=…`, `/browse?…` → `/discover?when=any&type=movie&…`.
- **Search** (`/search`): TVmaze shows and TMDB movies, cached for a day. Usually reached through the lightbox.
- **Calendar feed** (`/calendar/{token}.ics`, one per user, no login needed; the token is the secret).
- **`php artisan hoot:refresh [--all]`**, scheduled every 6 h. It takes about 70 s, because it loads 30 past days × (worldwide streaming + US + GB) from TVmaze.
- **Ratings:** TVmaze for shows, TMDB for movies (hidden with fewer than 10 votes).
- **Services:** a fixed list in `config/hoot.php` that maps TMDB provider ids and TVmaze channel names. A movie's services are the union over all countries.
- **Following a show from Browse (TMDB):** it's looked up on TVmaze by TheTVDB or IMDb id, and `shows.tmdb_id` is stored.
- **Cards:** horizontal panels (poster | title, details, 3-line description, rating, Follow button), at most 3 per row (`components/media-card`, `media-grid`).
- **Theme:** dark plum to match the icon (`night-*` palette in `resources/css/app.css`), Figtree for headings and Inter for text. Designed with the /ui skill guidelines.

## Decisions

- Accounts since 2026-11-06 (before, there was no login). Pages are `noindex` except the landing page. Mail goes to MailHog locally (`127.0.0.1:1025`, inbox at http://127.0.0.1:8025); mails use the published, restyled templates in `resources/views/vendor/{mail,notifications}`, with the wording in `AppServiceProvider`. The server needs a real mailer in `.env` for verification and reset mails.
- The pages are Blade with a little vanilla JS, not Inertia.
- TV comes only from TVmaze. Movies come from TMDB.
- There's no region filter for release dates (the earliest release anywhere counts). Browse does need a country for its service filter, because TMDB only knows availability per country.
- Premieres only cover shows that premiered in the last 30 days. Past regular-TV premieres only come from the US and UK. Streaming premieres are covered worldwide.

## Next

- **Hoot as a network** (the next big step, asked for on 2026-11-06): follow people, see what they follow and watch, a thread of what the people you follow are up to. The plan is in [`docs/SOCIAL.md`](docs/SOCIAL.md): profiles with `/@handle`, following people, a watch status (want to watch / watching / watched / dropped), favourites (independent of following: one library row per user and show/movie with a `following` flag, so favourite-only classics aren't refreshed or in the timeline), an `activities` table read fan-out-on-read, privacy and blocking, and a suggested order (steps 1–4 are the smallest useful version). Its "Open questions" need the user's answers before building; start there.
- Deploy: first `./deploy.sh`, the Hostpoint cron job, and in the server's `.env` the `TMDB_TOKEN` and a real mailer (`MAIL_*`), otherwise nobody can confirm their account.
- Check the mails in Gmail and Outlook (only checked in Chrome via MailHog).

## On another machine

The repo doesn't carry `.env` or the SQLite database. After cloning:

```sh
git clone https://github.com/kimiyuch/hoot.web.git ~/Jamon.digital/Webroot/hoot.web
cd ~/Jamon.digital/Webroot/hoot.web
composer install && npm install
cp .env.example .env && php artisan key:generate   # then copy TMDB_TOKEN from the old machine's .env
touch database/database.sqlite && php artisan migrate
npm run build
herd secure hoot.web                                 # if Webroot is parked in Herd; otherwise: herd link hoot.web
php artisan hoot:refresh                             # about 70 s
```

The local test user (`test@possum.local`) only exists in the old machine's database: sign up again (mail goes to MailHog, if it runs there) or create one with `php artisan tinker`.

## Open ideas

- Push or email notifications, a weekly digest, Trakt/Letterboxd import, marking episodes as watched.
- Passkeys / two-factor like stackr (left out for now).
