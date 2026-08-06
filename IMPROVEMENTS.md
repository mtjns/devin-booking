# Děvín Booking — Security, Deployment & Maintainability Assessment

## Context

`devin-booking` is a Laravel 12 + FilamentPHP 3 cabin-rental booking system. It exposes a
public booking form (Livewire) with live availability, an admin panel, automatic
deposit/reminder handling, and Fio Bank payment import. It runs as a Docker Compose stack
(`app`, `queue`, `scheduler`, `db`, `backup`, `meilisearch`, `caddy`) with Caddy terminating
TLS in front of an nginx + php-fpm container.

This document reviews the app across **security, deployment, and ease of maintenance**.
Findings are prioritized by impact; each references the relevant file. Infra-level
recommendations are included since the highest-value fixes live there.

---

## P0 — Fix before/at next deploy

### 1. Fio Bank API token is logged in plaintext
`app/Services/FioBankApiClient.php:31-33,73` builds the request URL with the secret token
embedded in the path (`/last/{token}/transactions.json`) — this is how Fio's API works — but
then logs the **full URL** via `Log::channel('bookings')->info("...: {$url}")`. The bank token
that can read all account transactions ends up in `storage/logs` and in any log aggregation.
- **Fix:** log the endpoint name only, never the interpolated URL. Redact the token
  (e.g. log `/last/***/transactions.json`). Audit the `bookings` log channel and rotate the
  Fio token if these logs have left the server.

### 2. Reverse-proxy trust is disabled → broken client IP + HTTPS detection
`.env.deploy:76` ships `TRUSTED_PROXIES=` (empty), and `bootstrap/app.php:19-30` only trusts
proxies when the var is non-empty. Behind Caddy (`compose.yaml:184`, `Caddyfile:6`
`reverse_proxy app:80`), this means:
- **All requests appear to come from Caddy's container IP.** Every IP-based rate limit
  collapses to a single bucket — `BookingForm` (`app/Livewire/BookingForm.php:116,124`) and the
  availability limiter (`AppServiceProvider.php:39-41`) throttle *all users together* or not at
  all. Spam protection is effectively defeated.
- Laravel can't see the original `https` scheme, so generated URLs and the `Secure` cookie
  flag are unreliable.
- **Fix:** set `TRUSTED_PROXIES` to Caddy's network (the compose network is trusted; `*` is
  acceptable here because nothing but Caddy can reach `app:80`, but scoping to the Docker subnet
  is cleaner). Document this in `DEPLOY.md`.

### 3. HTTPS is not enforced at the app layer
`app/Providers/AppServiceProvider.php:32-35` has `URL::forceScheme('https')` **commented out**.
Combined with #2, absolute URLs (emails with payment links, password flows) can be generated as
`http`. Caddy redirects browsers, but server-generated links and cookie security shouldn't
depend on that.
- **Fix:** force HTTPS in production (uncomment, guarded by `APP_ENV`), *after* trusted proxies
  are set so scheme detection is correct.

### 4. Named `vendor` / `storage` volumes ship stale code on redeploy
`compose.yaml:16-17,49-51,80-82` mount named volumes `vendor-data:/var/www/html/vendor` and
`storage-data:/var/www/html/storage` over paths that are **baked into the image** at build time
(`Dockerfile:32` runs `composer install`; `npm run build` writes to `public/build`, not
storage). Docker seeds a named volume from the image only on **first creation** — after that the
volume persists. So rebuilding the image with new dependencies and running `up` will keep the
**old `vendor/`**, silently running stale/incompatible code against new application source.
- **Fix:** don't volume-mount `vendor` at all (it belongs to the immutable image). For
  `storage`, mount only the sub-paths that must persist (`storage/app`, `storage/logs`) rather
  than the whole tree, or make redeploys explicitly refresh it. Add this pitfall to `DEPLOY.md`.

---

## P1 — Important, do soon

### 5. No HTTP security headers
Neither `Caddyfile` nor `deploy/nginx.conf` sets `Strict-Transport-Security`, `X-Content-Type-Options`,
`X-Frame-Options`/`frame-ancestors`, `Referrer-Policy`, or a CSP. Caddy is the natural place.
- **Fix:** add a `header` block in the `Caddyfile` (HSTS with `preload`, `X-Content-Type-Options: nosniff`,
  `Referrer-Policy`, `X-Frame-Options: SAMEORIGIN`). Introduce a CSP in report-only mode first
  because Filament/Livewire/Vite need allowances.

### 6. No brute-force protection on the admin login
The Filament panel (`app/Providers/Filament/AdminPanelProvider.php:29 path('admin')`,
`->login()`) has no login throttle; the public booking form is carefully rate-limited but the
privileged entry point is not.
- **Fix:** add a `throttle` middleware to the panel (or Filament's login throttling) keyed by IP
  + email; consider moving the panel off the default `/admin` path.

### 7. Deployment relies on manual, ordered, error-prone steps
`docker-entrypoint.sh` starts supervisord but never runs `migrate`, `config:cache`,
`route:cache`, `view:cache`, `filament:optimize`, or `storage:link`. `DEPLOY.md:141` literally
says "You may need to run this twice… run it again until it succeeds." Every deploy is a manual
sequence a human can get wrong, and config is re-parsed from env on every request (slow).
- **Fix:** add a one-shot init step that runs migrations **once** (only the `app` service, not
  `queue`/`scheduler` — guard against concurrent migration) and builds the framework caches.
  Options: an init/one-shot container, or an entrypoint branch gated to a single role. Document
  the single source of truth in `DEPLOY.md`.

### 8. No opcache / production PHP tuning
The image installs php-fpm but configures no `opcache` (`Dockerfile` has no
`opcache.enable=1`, `opcache.validate_timestamps=0`, memory sizing). For a production PHP app
this is a large, free latency win.
- **Fix:** add a production `php.ini`/opcache config layer in the Dockerfile.

### 9. Resource limits are ignored by plain `docker compose`
`deploy.resources.reservations` (and, depending on version, some limits) under `deploy:` are a
Swarm construct. On a single-host `docker compose up` these are silently partially ignored, so
the memory/CPU guarantees you think you have may not exist.
- **Fix:** use `mem_limit` / `cpus` (Compose spec) or confirm the running Compose version honors
  `deploy.resources.limits`; drop `reservations` unless using Swarm.

### 10. No app-level healthcheck; caddy `depends_on: app` doesn't wait for readiness
`compose.yaml:187` `caddy depends_on app` has no `condition: service_healthy`, and the `app`
service defines no `healthcheck`, even though a good `/health` endpoint already exists
(`routes/web.php:18-48`) plus Laravel's `/up` (`bootstrap/app.php:14`).
- **Fix:** add a `healthcheck` to the `app` service hitting `/health` (or `/up`) and make Caddy
  wait on it.

---

## P2 — Maintainability

### 11. Essentially no automated tests, despite complex money/availability logic
`tests/` contains only `TestCase.php` — **zero real tests**. The riskiest code (overlap/capacity
race handling and DB lock in `BookingForm::submitReservation`, price/deposit/variable-symbol
computation in the `Booking` model, Fio payment matching, pending-booking cancellation) is
entirely unguarded. This is the biggest long-term maintenance liability.
- **Fix:** add feature tests for: availability/overlap and `bed_capacity` enforcement, whole-cabin
  blocking, deposit/total/variable-symbol computation, Fio transaction → booking matching with
  `processed_transactions` dedupe, and the pending-window cancellation command.

### 12. No CI/CD pipeline
No `.github/workflows`. `laravel/pint` is already a dev dependency but nothing runs it; nothing
runs the (future) test suite or builds the image.
- **Fix:** add a GitHub Actions workflow: `composer install`, `pint --test`, `php artisan test`
  (against a MySQL service), and optionally a Docker build.

### 13. Committed cruft and secrets-adjacent files
- Stray empty/tracked files: `booking`, `pendingWindow`, `supervisord.pid`,
  `Fio_API_dokumentace_14-03-2026.pdf...Zone.Identifier` (a Windows artifact) — all tracked
  (`git ls-files`). `supervisord.pid` in particular should never be committed.
- A **1.2 MB PDF** (`Fio_API_dokumentace_14-03-2026.pdf`) is committed, bloating the repo and the
  Docker build context.
- `.dev.env` is committed with a real `APP_KEY` and dev DB passwords. Low risk (local only) but
  a bad pattern — dev keys shouldn't live in git.
- `.env.deploy` embeds a real address in `ADMIN_CRASH_EMAILS`.
- **Fix:** `git rm` the stray files and add them to `.gitignore`; move the PDF to external docs
  or `.dockerignore` it; rename `.env.deploy` → `.env.example` (composer's `setup` script at
  `composer.json` already expects `.env.example`), and strip real values to placeholders.

### 14. Image hygiene / supply chain
- `Dockerfile:1` base `php:8.4-fpm-alpine` is **not digest-pinned**, while `compose.yaml`
  pins mysql/meilisearch by digest — inconsistent.
- Build tools `git`, `nodejs`, `npm` remain in the final runtime image (`Dockerfile:3-18,34-35`);
  a multi-stage build (build assets in a node stage, copy `public/build` into a lean php-fpm
  runtime) would shrink the image and cut attack surface.
- Note: README/composer say PHP 8.2+, image is 8.4 — fine, but keep them intentionally aligned.
- **Fix:** digest-pin the base image; split frontend build into a multi-stage build.

### 15. Backups are single-location and unverified
`compose.yaml:127-143` dumps MySQL nightly to a host bind mount `./backups`, rotated at 30 days.
There is no offsite copy and no documented restore test — a host failure loses both DB and
backups together.
- **Fix:** ship dumps offsite (the `AWS_*`/S3 config is already scaffolded in env), and document
  a periodic restore drill in `MAINTENANCE.md`.

---

## Lower-priority / notes
- `unserialize()` on queue payload in `AppServiceProvider.php:66` operates on framework-internal
  data (low risk), but worth a comment noting the assumption.
- `/health` (`routes/web.php`) performs a DB + cache write on every unauthenticated hit — fine at
  current scale, but cache-writing on a public endpoint is a minor amplification vector; the
  availability limiter doesn't cover it.
- Mixed Czech/English in code comments/UI is fine functionally; just call it out for onboarding.

---

## Suggested remediation order
1. **P0 #1–#4** (token logging, trusted proxies, force HTTPS, vendor-volume footgun) — small,
   high-impact, prevents an active secret leak and a silent stale-deploy class of bugs.
2. **P1 #5–#10** (headers, admin throttle, automated migrate+cache in deploy, opcache,
   healthcheck, resource limits).
3. **P2 #11–#15** (tests first, then CI, then repo cleanup, image hardening, backup offsite).

## Verification (for whoever implements later)
- **#2/#3:** after setting trusted proxies, confirm `request()->ip()` in a temp log reflects the
  real client (not the Caddy IP) and that `url()->current()` is `https`. Re-test that two
  different client IPs get independent rate-limit buckets on the booking form.
- **#1:** grep `storage/logs` after a Fio run to confirm no token substring appears.
- **#4/#7:** rebuild the image with a changed dependency, redeploy, and confirm the running
  `vendor/` and migrations reflect the new build (no stale volume).
- **#5:** `curl -I https://<domain>` shows the new security headers.
- **#11/#12:** `php artisan test` green in CI against a MySQL service; `pint --test` clean.
