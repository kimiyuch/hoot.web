# Deploying Hoot

Hoot runs at **https://hoot.kimiyu.ch** on Hostpoint shared hosting (PHP + SQLite, no queue worker, no Node on the server). You deploy from your Mac with `./deploy.sh`. This file covers the one-time setup, every deploy after that, the server's `.env`, and the routine admin tasks.

| | |
|---|---|
| SSH | `pumutuxu@pumutuxu.myhostpoint.ch` (your own SSH key, added in the Hostpoint panel) |
| Site folder | `/home/pumutuxu/www/hoot.kimiyu.ch` (`~/www/hoot.kimiyu.ch`) |
| Document root | `~/www/hoot.kimiyu.ch/public` |
| PHP | 8.3 or newer (`composer.json` needs `^8.3`) |
| Database | SQLite, `database/database.sqlite` on the server |

## How `deploy.sh` works

1. Refuses to run with uncommitted changes (only committed code goes out) or without `public/build/manifest.json`.
2. Runs the tests (`php artisan test`). A failing test stops the deploy.
3. Exports `HEAD` with `git archive` into a temp folder and runs `composer install --no-dev --optimize-autoloader` there.
4. Uploads it with `rsync --delete`. It leaves out `.github`, `node_modules`, `tests`, `.env`, `public/hot` and `deploy.sh`. It never overwrites or deletes the server's `public/.htaccess`, `storage/`, `bootstrap/cache/*.php` or `database/database.sqlite`.
5. On the server it runs `php artisan migrate --force`, `optimize:clear` and `optimize`.

The frontend is built **on your Mac** (`npm run build`) and `public/build` is committed. The server never runs npm.

---

## First deploy (one time)

Do these in order. Steps 1–5 prepare what `deploy.sh` never touches; it would fail at the migrate step without them.

### 1. Hostpoint panel

- **Subdomain** `hoot.kimiyu.ch`, with its document root set to `www/hoot.kimiyu.ch/public`.
- **PHP version** 8.3 (or newer) for that site. Hostpoint writes it into `public/.htaccess` (see step 4).
- **SSL**: turn on Let's Encrypt for the subdomain. Hoot's links (the calendar feed, mails) use `https://`.
- **Mailbox** for sending, e.g. `hoot@kimiyu.ch`. Note its password. You need it for `MAIL_*` in step 3.

### 2. Check SSH and PHP on the server

```sh
ssh pumutuxu@pumutuxu.myhostpoint.ch 'php -v; ls ~/www'
```

`php -v` must say 8.3 or newer: `deploy.sh` and the cron job call plain `php`. If it shows an older version, change the default PHP version for SSH in the Hostpoint panel. Otherwise use the versioned binary Hostpoint provides in the cron line in step 7.

### 3. The server's `.env`

Make an app key on your Mac (it only prints it; nothing local changes):

```sh
php artisan key:generate --show
```

Then create the folder, the empty database and the `.env` on the server:

```sh
ssh pumutuxu@pumutuxu.myhostpoint.ch 'mkdir -p ~/www/hoot.kimiyu.ch/database ~/www/hoot.kimiyu.ch/public && touch ~/www/hoot.kimiyu.ch/database/database.sqlite'
ssh pumutuxu@pumutuxu.myhostpoint.ch 'nano ~/www/hoot.kimiyu.ch/.env'
```

Paste this, filling in the key, the TMDB token (copy it from your local `.env`) and the mailbox password:

```ini
APP_NAME=Hoot
APP_ENV=production
APP_KEY=base64:…                     # from key:generate --show
APP_DEBUG=false
APP_URL=https://hoot.kimiyu.ch

APP_LOCALE=en
APP_FALLBACK_LOCALE=en

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=warning

DB_CONNECTION=sqlite

SESSION_DRIVER=database
SESSION_LIFETIME=120
CACHE_STORE=database
QUEUE_CONNECTION=sync

MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=asmtp.mail.hostpoint.ch    # check the SMTP server in the Hostpoint panel
MAIL_PORT=587
MAIL_USERNAME=hoot@kimiyu.ch
MAIL_PASSWORD=…
MAIL_FROM_ADDRESS="hoot@kimiyu.ch"
MAIL_FROM_NAME="Hoot"

HOOT_TIMEZONE=Europe/Zurich
HOOT_REGION=CH
HOOT_REPORT_EMAIL=                   # where profile reports go; empty = MAIL_FROM_ADDRESS
TMDB_TOKEN=…                         # the long "API Read Access Token"
```

Notes:
- **`APP_URL`** must be the real https address. Mail links and the calendar feed (`webcal://…`) are built from it.
- **Without a working mailer nobody can sign up**: For you only opens after the email is confirmed, and password reset is mail too.
- **`TMDB_TOKEN`**: without it, movies and Discover → Any time don't work.
- **`QUEUE_CONNECTION=sync`**: Hoot queues nothing, and there's no worker on shared hosting. `sync` makes sure nothing waits in a queue by accident.
- Sessions and cache live in SQLite (the migrations create the tables), the same as locally.

### 4. `public/.htaccess`

It isn't in git, because Hostpoint manages the PHP line in it. Check what's there:

```sh
ssh pumutuxu@pumutuxu.myhostpoint.ch 'cat ~/www/hoot.kimiyu.ch/public/.htaccess'
```

It needs Hostpoint's PHP line first (the other kimiyu sites use `Use php-fpm php83`), then Laravel's rules. If it's missing or only has the PHP line, make it this (or copy it from another kimiyu site, e.g. `~/www/croc.kimiyu.ch/public/.htaccess`):

```apache
Use php-fpm php83

<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Handle X-XSRF-Token Header
    RewriteCond %{HTTP:x-xsrf-token} .
    RewriteRule .* - [E=HTTP_X_XSRF_TOKEN:%{HTTP:X-XSRF-Token}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

If you change the PHP version in the panel later, Hostpoint rewrites the first line. Check that the rules below it survived.

### 5. Build and commit

On your Mac, in this repo:

```sh
npm run build
git status          # must be clean; commit public/build if it changed
```

### 6. Deploy

```sh
./deploy.sh
```

The first run uploads everything and creates all tables in the empty `database.sqlite`.

Then load the data once. It takes a few minutes: TVmaze's full schedule plus 30 days of daily schedules.

```sh
ssh pumutuxu@pumutuxu.myhostpoint.ch 'cd ~/www/hoot.kimiyu.ch && php artisan hoot:refresh --all'
```

On an empty database this mainly builds the premieres for Discover. With a copied database (see "Moving the local database over") it also fills in `official_url` for existing shows, the links to the streaming services.

### 7. Cron job

In the Hostpoint panel (Cron jobs), every 15 minutes:

```
*/15 * * * * cd ~/www/hoot.kimiyu.ch && php artisan schedule:run >/dev/null 2>&1
```

The scheduler then runs `hoot:refresh` every six hours (`routes/console.php`), with no overlap. Check that it's registered:

```sh
ssh pumutuxu@pumutuxu.myhostpoint.ch 'cd ~/www/hoot.kimiyu.ch && php artisan schedule:list'
```

### 8. Check it

- https://hoot.kimiyu.ch shows the landing page, and https://hoot.kimiyu.ch/up says it's up.
- **Sign up** with a real address. The confirmation mail arrives, and the link opens For you. Check the mail in Gmail and Outlook too, since it's only been checked in MailHog so far.
- **Forgot password** sends a mail.
- **Follow** a show and a movie. They show up in For you with dates.
- **Discover**: Premiering soon has entries (after step 6), and Any time loads (needs `TMDB_TOKEN`).
- **Calendar**: Subscribe in For you's sidebar opens your calendar app, and the feed has the episodes.
- **Profiles**: a second account can follow you. A private account gets a request (Settings → Follow requests). The thread shows "From people you follow".
- `/privacy` loads, and Report on a profile opens a mail to the right address.
- `storage/logs/laravel.log` on the server has no errors (see "Logs").

---

## Every deploy after that

```sh
npm run build                 # only if CSS, JS or Blade classes changed; commit public/build
git add -A && git commit -m "…"
git push                      # GitHub Actions runs the tests too
./deploy.sh
```

Migrations run by themselves. A release only needs extra steps if its commit or `PROGRESS.md` says so (e.g. "run `hoot:refresh --all` once").

### Rolling back

`deploy.sh` deploys whatever is checked out, so:

```sh
git checkout <older-sha>      # detached HEAD, working tree must be clean
./deploy.sh
git checkout main
```

Migrations aren't rolled back by this. If the release you're undoing added a migration, the newer columns and tables just stay, which is harmless for the migrations so far. Take a backup first if in doubt (see below).

---

## Moving the local database over (optional)

To start with the local accounts and follows instead of an empty database. `deploy.sh` never touches the server's file, so you copy it by hand, **before anyone signs up on the server** (it replaces everything there):

```sh
scp database/database.sqlite pumutuxu@pumutuxu.myhostpoint.ch:www/hoot.kimiyu.ch/database/database.sqlite
ssh pumutuxu@pumutuxu.myhostpoint.ch 'cd ~/www/hoot.kimiyu.ch && php artisan migrate --force && php artisan optimize:clear && php artisan hoot:refresh --all'
```

Local test accounts (`test@possum.local`) come along. Delete them in Settings, or with `php artisan tinker` on the server.

## Admin tasks

All over SSH, in `~/www/hoot.kimiyu.ch`:

| Task | Command |
|---|---|
| Refresh shows and movies now | `php artisan hoot:refresh` (`--all` reloads every show) |
| Suspend an account after a report | `php artisan hoot:suspend pip`: signed out, can't log in, hidden from everyone |
| Lift a suspension | `php artisan hoot:suspend pip --lift` |
| See the scheduled jobs | `php artisan schedule:list` |
| Clear and rebuild the caches | `php artisan optimize:clear && php artisan optimize` |
| Maintenance mode | `php artisan down` / `php artisan up` |

Reports arrive as mails at `HOOT_REPORT_EMAIL` (or `MAIL_FROM_ADDRESS`), with the profile link in them.

### Logs

```sh
ssh pumutuxu@pumutuxu.myhostpoint.ch 'tail -n 100 ~/www/hoot.kimiyu.ch/storage/logs/laravel.log'
```

### Backups

Everything that matters is the one file `database/database.sqlite` (plus `.env`). Copy both to your Mac now and then:

```sh
scp pumutuxu@pumutuxu.myhostpoint.ch:www/hoot.kimiyu.ch/database/database.sqlite ~/Backups/hoot-$(date +%F).sqlite
scp pumutuxu@pumutuxu.myhostpoint.ch:www/hoot.kimiyu.ch/.env ~/Backups/hoot-$(date +%F).env
```

Hostpoint's own backups cover the account as well.

---

## When something's wrong

| Symptom | Look at |
|---|---|
| 500 error | `storage/logs/laravel.log`. Often a missing `.env` value or a stale cache: `php artisan optimize:clear && php artisan optimize`. |
| 404 on every page but `/` | `public/.htaccess` lost Laravel's rewrite rules (step 4). |
| 403 on everything | Folder permissions: `chmod 755 ~/www/hoot.kimiyu.ch ~/www/hoot.kimiyu.ch/public`. |
| "Database is locked" / can't write | `database/` and `database.sqlite` must be writable by your user. SQLite also writes a temporary `database.sqlite-journal` next to the file. |
| No confirmation mails | `MAIL_*` in `.env`; the log shows the SMTP error. Try `php artisan tinker` → `Mail::raw('test', fn ($m) => $m->to('you@…')->subject('test'));` |
| Dates stop updating | The cron job: is it set, and does `php -v` in it give 8.3+? Run `php artisan hoot:refresh` by hand and read its output. |
| Movies or Any time empty | `TMDB_TOKEN` missing or wrong. |
| Old CSS after a deploy | `public/build` wasn't rebuilt and committed before `./deploy.sh`. |
| Times off | Times follow the browser's timezone (`tz` cookie); `HOOT_TIMEZONE` is only the fallback and the calendar feed's zone. |
