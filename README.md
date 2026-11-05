# possum.web

Possum shows when the movies and shows I follow come out (new episodes, season premieres, cinema and digital releases) on every streaming service, in my own timezone. It also has a calendar feed. Live at [possum.kimiyu.ch](https://possum.kimiyu.ch).

A Laravel 13 app with Blade and Tailwind CSS 4, built like [croc.web](https://github.com/kimiyuch/croc.web). TV comes from [TVmaze](https://www.tvmaze.com/api) (no key needed) and movies from [TMDB](https://developer.themoviedb.org/docs), which needs a key. There's no login.

## Local development

The app runs in [Herd](https://herd.laravel.com) at `https://possum.web.test`.

```sh
composer install
npm install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite && php artisan migrate
npm run dev       # or: npm run build
php artisan possum:refresh
```

For movies, put a TMDB key in `.env` as `TMDB_TOKEN`. Either the "API Read Access Token" or the "API Key" from themoviedb.org → Settings → API works. Without a key, Possum only does TV.

## How it works

- **Following** a show or movie fetches it right away and stores it in SQLite: all of a show's episodes, and a movie's earliest cinema and digital release in any country.
- **`php artisan possum:refresh`** runs every six hours through the scheduler. It reloads the shows TVmaze reports as changed (`/updates/shows`) and any not reloaded for a week, plus all movies. It also rebuilds the Premieres page. Coming premieres come from TVmaze's full worldwide schedule (a 10+ MB response, which is why it's never loaded during a page view). Recent premieres (last 30 days) come from TVmaze's daily schedules: streaming worldwide, plus US and UK TV, which is about 90 calls and takes roughly a minute. Movies come from TMDB's discover endpoint. `--all` reloads every show.
- **Times** are stored in UTC (TVmaze's `airstamp`) and shown in the browser's timezone. `resources/js/app.js` saves the timezone in a `tz` cookie. Until that cookie exists, `POSSUM_TIMEZONE` is used. Movie releases have a date but no time.
- **Discover → Any time** asks TMDB's discover endpoint live (each page cached for six hours): movies or shows since a year (the current year by default), by service, sort and minimum rating. Which service has what depends on the country, so it has a country picker (`POSSUM_REGION`, default CH). Shows found there are followed through TVmaze (matched by TheTVDB or IMDb id), because the episode dates come from TVmaze.
- **Services** are a fixed list in `config/possum.php` that maps TMDB provider ids and TVmaze channel names to one service, so "Apple TV" means the same everywhere. Logos come from TMDB.
- **Ratings** are TVmaze's for shows and TMDB's for movies (hidden with fewer than 10 votes).
- **API calls:** pages only read the database and cache. The APIs are called by the refresh, when following something, by search (cached for a day) and by Browse (cached for six hours).
- **Calendar feed:** `/calendar.ics` covers the last 30 days and the next 120.

## Deploying

```sh
npm run build     # if you changed the design; commit public/build
./deploy.sh
```

`deploy.sh` runs the tests, exports the committed code, installs production dependencies, syncs everything to Hostpoint over SSH and runs the migrations. It leaves the server's `.env`, `public/.htaccess`, `storage/` and `database/database.sqlite` alone. GitHub Actions only runs the tests.

On the server, add `TMDB_TOKEN` to `.env` and set up a cron job in the Hostpoint panel:

```
*/15 * * * * cd ~/www/possum.kimiyu.ch && php artisan schedule:run >/dev/null 2>&1
```

## Where things are

| | |
|---|---|
| `routes/web.php`, `app/Http/Controllers/PossumController.php` | The pages, following and unfollowing, the calendar feed |
| `routes/console.php` | `possum:refresh` and its schedule |
| `app/Services/TvMaze.php`, `app/Services/Tmdb.php` | The API clients |
| `app/Services/Library.php` | Following, and refreshing what's followed |
| `app/Services/Timeline.php` | The Upcoming page's sections (Just released, Today, Tomorrow, This week, Later) |
| `app/Services/Premieres.php` | The Premieres page, cached between refreshes |
| `app/Services/Browse.php` | The Browse page |
| `app/Services/StreamingServices.php` | Matching TVmaze channels and TMDB providers to the services in `config/possum.php` |
| `app/Services/Calendar.php` | The iCal feed |
| `resources/css/app.css` | Theme colours (`night` for the dark background, `midnight` for the late-night plum, `calendar` for the red of the calendar page, `star` for ratings) and fonts |
| `config/possum.php` | Timezone, TMDB key, services, Browse country and start year, how far ahead and back to look |
| `public/images/possum.png` | The icon, rendered by `scripts/make-icon.mjs` (`node scripts/make-icon.mjs`, needs Google Chrome) into `artwork/` |

Data: TVmaze is CC BY-SA, and TMDB requires credit. Both are credited in the footer.
