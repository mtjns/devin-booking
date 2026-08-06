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

---

## Appendix — Build vs. Buy, Hosting & Pricing (optimized for lowest cost)

This app is really two things: (1) a **custom shared-bed availability + pricing engine**
(per-night capacity, guest categories, whole-cabin blocking, deposits, Czech *variable
symbol*) — the genuine product, hard to buy off-the-shelf; and (2) a large pile of
**self-hosted plumbing + a poll-based bank reconciler** — which is where almost all the risk
lives and which is cheap to replace with managed pieces. Recommendation: **keep the brain,
rent the plumbing.** A full booking SaaS (Lodgify/Smoobu/Beds24) is rejected because those
model a property as one atomic unit and won't support the shared-bed-by-category model or the
Czech bank-transfer flow without giving one of them up.

### Payments — cheapest is to keep bank transfer
Bank transfer via Fio has **0% transaction fees**; every card gateway takes a cut, so a paid
gateway is a reliability/UX upgrade, not a cost saving.

| Option | Per-transaction | Monthly | Notes |
|---|---|---|---|
| **Keep Fio bank transfer** (current) | **0%** | **0 Kč** | Cheapest. Fix token-in-logs (#1) + harden matching instead of switching. |
| Comgate | 0.79–0.99% (cards) | 0–149 Kč | Cheapest CZ card gateway; free setup/payouts; prices frozen to 31 Dec 2026. |
| GoPay | low %, tiered | 0 Kč if turnover >50k/mo, else 80 Kč | Comparable. |

Variable-symbol matching is inherent to *any* bank-transfer method. A gateway only removes it
if you move to **card** payments (signed, pushed webhooks instead of 10-min polling) — worth it
only if you want cards or the polling keeps causing incidents.

### Hosting — cheapest reliable options

| Option | Cost/mo | What you get | Ops burden |
|---|---|---|---|
| **Hetzner CX22 + existing (hardened) Compose** | **~€3.79 (~$4)** | Cheapest solid EU VPS, 20 TB traffic | You patch/deploy (compose already exists) |
| **Hetzner + Ploi** | ~$12 total | Managed deploys, migrate-on-deploy, SSL, backups, monitoring | Low |
| Hetzner + Laravel Forge | ~$16 total | Same idea, 1 server on Hobby | Low |
| Laravel Cloud | $5 credit + usage (~$15–30+ realistic) | Zero-ops, but always-on web+queue+scheduler means scale-to-zero doesn't help | None |
| Full booking SaaS | ~$30–50+ | No servers | None, but **doesn't fit the model** |

### Near-free supporting services
- **Email:** Resend free tier (3,000/mo) = **$0**, or Amazon SES (~$0.10 / 1,000). Replaces raw
  SMTP with real deliverability + bounce handling. (Fixes #4 in the email/SMTP sense.)
- **Backups:** keep MySQL on the VPS; ship nightly dumps **offsite** to Backblaze B2 or a
  Hetzner Storage Box (~€0–3/mo). Cheaply fixes #15.
- **Search:** **drop Meilisearch** — a whole service + key + RAM used only for admin filtering
  of a small table. Free savings; removes a service and attack surface.
- **Managed DB (PlanetScale/DO/RDS ~$15+/mo):** skip for cost reasons; offsite dumps cover the
  real risk at this scale.

### Cheapest recommended architecture (keeps the custom model)
- **Host:** Hetzner CX22 (~$4), optionally + Ploi (+$8) to drop the babysitting
- **Payments:** keep Fio bank transfer ($0) — fix token logging (#1) + harden matching
- **Email:** Resend free tier ($0)
- **Backups:** dumps → Backblaze B2 (~$0)
- **Drop:** Meilisearch
- **Total: ~$4/mo DIY, or ~$12/mo hands-off** — vs. $30–50+ for a SaaS that wouldn't fit.

_Pricing verified August 2026; Czech gateway rates frozen to end-2026 per Comgate. Sources:
Comgate online-payments pricing, GoPay ceník, Laravel Cloud pricing, Hetzner Cloud pricing,
Ploi/Forge pricing pages, Resend/SES/Postmark pricing. Figures drift — re-check before
committing spend._

---

## Appendix — Payment migration: Fio Bank → Comgate (bank switching to ČSOB)

### Why
The bank is moving to **ČSOB**, which has no Fio-style token API, so the current
`bookings:process-fio-payments` polling reconciliation will stop working. Replace it with the
**Comgate** gateway. Chosen setup: **deposit-only** payment per booking; checkout offers **all
methods** (card, QR/bank transfer, Apple/Google Pay).

### How Comgate verifies a QR / bank-transfer payment
The QR the customer scans contains **Comgate's own collection account** and a **unique variable
symbol Comgate assigns** (the customer can't change amount, VS, or recipient). So the money lands
in Comgate's account and **Comgate** matches the incoming transfer by that VS — you never read a
bank. Flow:
1. On booking creation, the app calls Comgate to **create a payment** for the deposit, passing the
   booking's `variable_symbol` as `refId`; Comgate returns a `transId` + payment URL.
2. Customer pays (card = instant; QR/bank transfer = pending until funds arrive, seconds to hours
   unless instant payment).
3. Comgate matches the transfer on its side, flips `PENDING → PAID`, and **pushes a callback**
   (`id`/transId, `refId`, `status`) to the app — retried up to ~1,000× until it gets a 2xx.
4. The app **verifies via Comgate's `/status` endpoint** with the `transId` (never trusting raw
   callback params), then marks the booking paid.
5. Comgate **settles payouts to the ČSOB account** in daily/monthly batches. ČSOB only *receives*
   money; nothing reconciles against it — which is exactly why the missing ČSOB API stops mattering.

### Fit with the existing "QR in email" model
The confirmation email swaps the SPD QR-platba image for a **"Zaplatit zálohu" button** to the
Comgate payment URL (optionally plus a QR *of that URL* for mobile). Bank-transfer-by-scan still
exists — as a method **on Comgate's page** — but now Comgate confirms it automatically.

### Reuses the existing status machinery (minimal new code)
- `Booking::booted()/saving()` already sets `status = deposit_paid` when
  `paid_amount >= deposit_amount` and disables the deadline — **unchanged**.
- `BookingObserver::updated()` already emails `DepositFullReceived` on that transition —
  **unchanged**. The webhook just sets `paid_amount = deposit_amount` and saves.
- `booking->variable_symbol` → Comgate `refId`; `processed_transactions` → Comgate transId
  dedupe/audit (no schema change).

### Change surface
- **Add:** `comgate-payments/sdk-php`; `config/services.php` `comgate` block
  (`COMGATE_MERCHANT`/`COMGATE_SECRET`/`COMGATE_TEST`); `app/Services/ComgateClient.php`
  (create payment + status, no secret logging); `bookings.comgate_transid` / `comgate_pay_url`
  columns; payment creation in `BookingObserver::created()`; `POST /webhooks/comgate` controller.
- **Change:** `BookingCreatedConfirmation` + its blade views (pay button instead of SPD QR).
- **Remove:** `FioBankApiClient`, `ProcessFioBankPayments` + Fio test commands, the
  `bookings:process-fio-payments` schedule, `services.fio_bank`, `FIO_BANK_API_TOKEN`, and
  (optionally) `dfridrich/qr-platba`.
- **Keep:** `bookings:process-pending` (reminders/auto-cancel — bank transfer can lag/never
  arrive, so the deadline still matters).

### Cost note
This is the point where the current **0%** bank-transfer fee is traded for Comgate's
**0.79–0.99%** (cards) in exchange for reliable, automated reconciliation — unavoidable once Fio
(free polling) is gone, since ČSOB won't auto-reconcile.

### Verification (Comgate sandbox)
`COMGATE_TEST=true` + dev stack/Mailpit → book → pay (card + QR) → callback verified via `/status`
→ booking `deposit_paid` + `DepositFullReceived` email → replay callback is idempotent → unpaid
booking still auto-cancels via `bookings:process-pending` → no secrets in `bookings.log`.

_Comgate mechanics per apidoc.comgate.cz (payment process, REST API, payment methods) and
help.comgate.cz (bank transfers / variable symbol), verified August 2026._
