# Possum: progress

Handoff notes for the next session. The original brief is in the README's history (see "How it works" there); this file tracks what's done, what was decided and what's open.

Last updated: 2026-11-05.

## State

Working locally at https://possum.web.test (Herd, secured with `herd secure possum.web`). 17 tests pass (`php artisan test`), Pint is clean. `public/build` is built.

**Git:** in git since 2026-11-05 and pushed to https://github.com/kimiyuch/possum.web (public, `main`). The tests run on GitHub Actions for every push and passed on the first push. `marceli-to` is a collaborator, so a plain `git push` works. **Not done yet:** the first deploy (`./deploy.sh`) and the Hostpoint cron job.

`.env` has a working `TMDB_TOKEN` (the long "API Read Access Token"). It has to be added to the server's `.env` by hand when deploying.

## Built

- **Header:** a small floating pill on the right (sticky, as wide as its contents) with For you, Discover and search; the possum head (`public/images/possum-head.svg`, source in `artwork/possum-head.svg`, no wordmark) sits fixed in the top-left corner outside it and fades out once you scroll (`data-scrolled`, set by app.js), and a text-only "Search ⌘K" button, no icon (just "Search" on mobile; it shows "Ctrl K" on Windows and Linux). The full app icon stays as the favicon.
- **Search lightbox** (`components/search-dialog`): opens with ⌘K / Ctrl K or the header button, and loads results as you type (250 ms debounce). It fetches `/search?q=…` with the `X-Possum-Partial` header and gets back only `partials/search-results` (6 shows, 6 movies, Follow buttons, "Show all results"). The full `/search` page still works without JS.
- **For you** (`/`): an "Up next" card, then a timeline by day ("Today", "Tomorrow", weekday, then "Sat 21 Nov") with rows separated by dividers. The sidebar has "Just released" (last 7 days), "Following" (every followed show and movie is a `<details>`: it opens to the rating with an Unfollow button next to it, the description, and then all episodes by season, newest season first, in a scrolling box with the next episode in mint; movies show their cinema and digital dates instead) and the calendar feed (Subscribe and Copy link). Times are in the browser's timezone (`tz` cookie, set by `resources/js/app.js`).
- **Discover** (`/discover`): one page with filters in a left sidebar on desktop and in a `<dialog>` drawer on mobile. **What** (Shows/Movies), **When**: *Premiering soon* (default), *Just premiered* or *Any time*, plus Service. The service filter shows Netflix, HBO Max, Apple TV, Prime Video and Disney+ (`possum.featured_services`) and puts the rest behind "Show more", in a small scrolling box. A selected service from the rest stays visible. The sidebar isn't sticky. Soon and new come from the premieres that `possum:refresh` builds and caches (shows: next/last 30 days; movies: a release still to come / one in the last 30 days, so a movie can be in both). The service list then only shows services that have something, with counts. *Any time* is TMDB discover with the extra filters sort, since year (default: the current year), minimum rating and country (default CH), cached for 6 h per page (`browse:v2:` key).
- **Old URLs** redirect with 301: `/following` → `/`, `/premieres?tab=…` → `/discover?when=…`, `/browse?…` → `/discover?when=any&type=movie&…`.
- **Search** (`/search`): TVmaze shows and TMDB movies, cached for a day. Usually reached through the lightbox.
- **Calendar feed** (`/calendar.ics`).
- **`php artisan possum:refresh [--all]`**, scheduled every 6 h. It takes about 70 s, because it loads 30 past days × (worldwide streaming + US + GB) from TVmaze.
- **Ratings:** TVmaze for shows, TMDB for movies (hidden with fewer than 10 votes).
- **Services:** a fixed list in `config/possum.php` that maps TMDB provider ids and TVmaze channel names. A movie's services are the union over all countries.
- **Following a show from Browse (TMDB):** it's looked up on TVmaze by TheTVDB or IMDb id, and `shows.tmdb_id` is stored.
- **Cards:** horizontal panels (poster | title, details, 3-line description, rating, Follow button), at most 3 per row (`components/media-card`, `media-grid`).
- **Theme:** dark plum to match the icon (`night-*` palette in `resources/css/app.css`), Figtree for headings and Inter for text. Designed with the /ui skill guidelines.

## Decisions

- No login (the user asked for this). Because of that, the calendar feed has no secret token. Pages are `noindex`.
- The pages are Blade with a little vanilla JS, not Inertia.
- TV comes only from TVmaze. Movies come from TMDB.
- There's no region filter for release dates (the earliest release anywhere counts). Browse does need a country for its service filter, because TMDB only knows availability per country.
- Premieres only cover shows that premiered in the last 30 days. Past regular-TV premieres only come from the US and UK. Streaming premieres are covered worldwide.

## Open ideas

- Push or email notifications, a weekly digest, Trakt/Letterboxd import, marking episodes as watched.
- Clear out the sample follows (5 shows) that were added while testing.
