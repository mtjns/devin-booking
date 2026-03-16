# Děvín Booking Management System

This is a Laravel-based reservation and management system designed for alumni cabin bookings. It provides a secure administration panel to manage staff permissions, track reservations, visualize availability on a calendar, and configure global pricing variables.

## Tech Stack

* **Framework:** Laravel 12.x
* **PHP:** 8.5+
* **Admin Panel:** FilamentPHP v3
* **Local Development:** Laravel Sail (Docker)
* **Database:** MySQL

## Deployment Overview

### Docker Compose Files

The project uses two separate Docker setups:

#### **Production** (`docker-compose.yml`)
Optimized for production deployments with HTTPS, automated backups, and minimal overhead:
- `app` — PHP application server
- `queue` — Background task worker (processes emails, payments, warnings)
- `scheduler` — Runs scheduled tasks (payment processing, booking status checks)
- `db` — MySQL database with persistent volume
- `backup` — Automated nightly database backups (kept for 14 days)
- `meilisearch` — Full-text search engine (internal-only, no exposed port)
- `caddy` — HTTPS reverse proxy with automatic Let's Encrypt certificates

**To run:** `docker-compose -f docker-compose.yml up -d`

**Configuration:** Uses `.env` file for environment variables.

#### **Development** (`docker-compose.dev.yml`)
Lightweight development environment for local testing:
- `app` — PHP application server (port 80)
- `queue` — Background task worker
- `scheduler` — Task scheduler
- `db` — MySQL database (port 3306)
- `meilisearch` — Search engine (port 7700 exposed for debugging)
- `mailpit` — Email testing dashboard (ports 1025 for SMTP, 8025 for web UI)
- **No Caddy** — Direct HTTP access on port 80 for simplicity

**To run:** `docker-compose -f docker-compose.dev.yml up -d`

**Configuration:** Uses `.dev.env` file for environment variables.

**Mailpit:** Access the email dashboard at `http://localhost:8025` to view all emails sent during testing.

#### **Legacy Sail** (`compose.yaml`)
Original Laravel Sail configuration with Redis, full debugging tools, and Selenium for browser testing. Still available but not recommended for new development. Run with: `./vendor/bin/sail up -d`

### Key Services Explained

**Meilisearch** — Full-text search engine for booking searches and filters. Required in production if search features are enabled in the UI. Automatically synced with the database via Laravel Scout.

**Caddy** — Production-only reverse proxy that:
- Handles HTTPS with automatic Let's Encrypt certificate generation
- Routes requests to the app container
- Requires a domain name in the `Caddyfile` configuration

**Mailpit** — Development-only email interceptor that captures all emails sent by the app. Access the web UI at `http://localhost:8025` to review emails without sending them to real addresses.

**Backup Service** — Production-only automated database backups. Runs every 24 hours and stores compressed dumps in the `backups/` folder. Old backups older than 14 days are automatically deleted.

### Deployment Instructions

Step-by-step production deployment instructions live in `DEPLOY.md` and should be treated as the main source of truth for new administrators.

## Core Architecture

### 1. Granular Permissions (Role-Based Access)

We opted for a lightweight, boolean-based permission system on the `users` table rather than a heavy package like Spatie Permissions. This keeps the database simple and extremely fast.

* `is_super_admin`: Master override. Bypasses all policy checks.
* `can_manage_users`: Grants access to the Filament User Resource.
* `can_view_bookings`: Grants read-only access to reservations and the calendar.
* `can_edit_bookings`: Grants create/update/delete rights for reservations.
* `can_manage_financials`: Grants access to the global pricing settings page.

**Security Implementation:** These booleans are strictly enforced via Laravel Policies (`UserPolicy` and `BookingPolicy`). The `before()` method in each policy automatically grants access if the user is a super admin. Standalone Filament pages (like Settings) are secured via the overridden `canAccess()` method.

### 2. Bookings Management

Bookings are managed through the `BookingResource`.

* **Guest Breakdown:** Tracks integers for `graduate_count`, `student_count`, `child_count`, `external_count`, and `dog_count`. Dogs are priced separately but **do not consume bed capacity** in any availability checks.
* **Pricing & Deposits:** The `total_price` column dictates the full cost, while `paid_amount` tracks money actually received (e.g., via bank transfer). Prices are manually set by administrators.
* **Variable Symbols:** Handled automatically by the system for Czech banking compliance.
* **Exclusive Access:** The `reserve_whole` boolean allows a booking to claim the entire cabin. Overlap validation is intentionally relaxed on the backend to allow administrators to force double-bookings or maintenance blocks if necessary, but will be strictly enforced on the public frontend.

### 3. Smart Business Rules (Data Integrity)

To ensure the database remains perfectly consistent regardless of how a booking is created (via UI, API, or CLI), core business logic is embedded directly into the Model's `saving` boot method:

* **Variable Symbol Generation:** If left blank, the system automatically generates a unique 8-digit variable symbol (`YYMMXXXX`) right before the booking is saved to the database.
* **Deposit Calculation:** The system reads the global `deposit_percentage` setting. If the `paid_amount` reaches or exceeds the required deposit threshold, the system automatically transitions the status to `deposit_paid` and disables payment enforcement.
* **Zero-Price Logic:** If the `total_price` is set to `0`, the status is automatically transitioned to `deposit_paid`. Setting the price to `0` in the UI triggers a persistent modal warning the administrator of this automatic state change.
* **Safety Interlocks:** If a booking is saved without a `customer_email`, the model automatically forces the `send_confirmation_email` and `enforce_payment_deadline` toggles to `false`.

### 4. Global Settings (Spatie)

Global configuration variables (base prices, bank details, bed capacity, payment windows, and deposit percentage) are managed via `spatie/laravel-settings`.

* Settings are stored in the database but cached in memory for performance.
* Administrators manage these via the custom "General Settings" Filament page (`App\Filament\Pages\GeneralSettings`), including all per-guest prices (`graduate_price`, `student_price`, `child_price`, `external_price`, `dog_price`) and the flat `wood_price`. The UI explicitly documents that `dog_price` affects only pricing, not bed capacity.

### 5. Calendar Visualization

We use `saade/filament-fullcalendar` to visualize bookings.

* The widget is housed in `App\Livewire\BookingCalendarWidget`.
* It is displayed on a dedicated standalone page (`App\Filament\Pages\Calendar`).
* Clicking an event routes the user directly to the Filament edit form for that booking.

### 6. Automated Communication & Payment Tracking

The system features an intelligent, background-driven email engine that manages customer communication, payment deadlines, and deposit receipts. All email views are located directly in `resources/views/emails/`.

* **Asynchronous Processing:** All emails are dispatched to Laravel's database queue (`QUEUE_CONNECTION=database`). The Observer is configured with `$afterCommit = true` to prevent race conditions.
* **Smart Update Tracking:** The `App\Observers\BookingObserver` uses `wasChanged()` to detect exact field modifications and send context-aware emails:
  * **Deposit Received:** If the `status` changes to `deposit_paid`, the customer receives a success confirmation.
  * **Underpaid Notice:** If `paid_amount` increases but the status remains `pending` (under the percentage threshold), the customer receives a notice detailing the exact missing amount.
  * **General Updates:** Changes to dates, total price, or guest counts trigger a standard update email.
* **The Background Engine:** Handled by `App\Console\Commands\ProcessPendingBookings`. It evaluates pending bookings against the `pending_window` defined in the settings.
  * **3 Days Remaining:** Sends a First Warning email.
  * **1 Day Remaining:** Sends a Final Warning email.
  * **0 Days Remaining (Expired):** Changes status to `cancelled` and sends a Cancellation Notice.
* **Historical Isolation:** Bookings whose `end_date` is in the past will silently block all update logic. Both the `Booking` database model boot method and the `BookingObserver` forcefully disable automated emails and payment enforcement for past dates, preventing accidental spam or logical loops when administrators modify old archived records.
* **Automated Bank Integration:** The system includes a full Fio Bank API integration for processing payments automatically. The `App\Services\FioBankApiClient` service communicates with the Fio Bank REST API, and the `App\Console\Commands\ProcessFioBankPayments` command runs on a daily schedule (1:00 AM) to fetch new transactions and match them to bookings by variable symbol. Payment updates automatically trigger the Observer to handle status transitions and email dispatch.
* **How Bank Payments Work:** The integration fetches transactions from your Fio Bank account since the last successful download. Each transaction is matched by variable symbol (`VS` field) to a booking in the system. When a match is found, the booking's `paid_amount` field is updated to the cumulative amount shown in the bank. Customers can make multiple partial payments over time (e.g., paying 500 Kč as a deposit and 3000 Kč later as a top-up). **Overpayments are fully supported**—customers can pay more than `total_price` with no restrictions.
* **Idempotency & Crash Recovery:** All processed transactions are recorded in the `processed_transactions` table with their Fio Bank transaction ID. If the payment worker crashes after updating a booking but before completing, re-running it will safely skip the already-processed transaction and avoid double-charging or duplicate emails.
* **Payment Validation:** Only credit transactions (positive amounts) are processed. Debits, fees, refunds, and transactions without a variable symbol are automatically skipped and logged for manual review.
* **Scheduled Execution:** The payment processing runs automatically as part of your application scheduler, just like the pending bookings warning system. Setup requires only adding a Fio Bank API token to `.env`; no additional configuration needed. See **Section 10** in `DEPLOY.md` for setup instructions.

### 7. System Stability & Audit Logging

To ensure production readiness and prevent data loss from human error or concurrency issues, the system includes several fortification layers:

* **Optimistic Locking:** The Filament edit form intercepts save requests and compares database timestamps. If a background process (like an API payment) modified the booking while the administrator had the tab open, the save is halted to prevent overwriting new data.
* **Audit Trail:** All automated background actions (emails queued, warnings sent, cancellations) are written to a dedicated log file (`storage/logs/bookings.log`) rather than the standard Laravel log, ensuring a clear history of system operations.
* **Silent Updates:** Background CRON jobs utilize `updateQuietly()` and Carbon `copy()` to modify database timestamps without waking up the `BookingObserver`, preventing infinite loops and memory leaks.
* **Crash Notifications:** The system hooks into Laravel's global Exception Handler and Queue Manager. If a fatal PHP error occurs or a background queue job permanently fails, the system automatically emails the administrative team with the exact file, line, and error message.

### 8. Public Frontend & API Architecture

The public-facing reservation page utilizes a decoupled, split-interface approach to maximize both visibility and user experience.

* **Availability API Payload:** The system exposes a `/api/availability` endpoint that serves a comprehensive JSON payload. This includes `cabin_rules` (fetching dynamic pricing, capacity, and deadlines from Spatie Settings while omitting private banking details) and a list of active `bookings` including guest names and bed counts. Guests must explicitly consent to their names being visible on the public calendar before submitting a reservation.
* **Discovery View (FullCalendar):** A large, read-only FullCalendar instance sits at the top of the page. It maps the API payload into visual event blocks, allowing prospective guests to easily see who is currently booked at the cabin, how many beds remain, and which days are completely locked out.
* **Actionable Booking Form:** Below the read-only calendar is the actual reservation form, powered by Livewire. It utilizes a strict, mobile-friendly datepicker (Litepicker) that consumes the same API payload to completely disable fully occupied dates, guaranteeing users cannot select overlapping ranges.
* **Dynamic Pricing Engine:** Because the frontend receives the pricing rules via the API, the Livewire form dynamically calculates and displays the expected total price to the user in real-time as they adjust guest counts and dates.

## Operations Guide for Future Admins

This section is for the person responsible for running the system in production. It assumes the app is deployed with Docker as described in `DEPLOY.md`.

### Routine checks (weekly or when something feels off)

- **Verify containers are running**:
  - From the project directory on the server:
    - `docker compose ps`
  - You should see `app`, `queue`, `scheduler`, `db`, and `backup` with status `running` / `Up`.
- **Look at the last log lines if you suspect issues**:
  - Web / API:
    - `docker compose logs app --tail=100`
  - Background jobs:
    - `docker compose logs queue --tail=100`
  - Scheduler:
    - `docker compose logs scheduler --tail=100`
- **Confirm backups exist**:
  - On the server:
    - `ls backups`
  - You should see recent `backup-YYYY-MM-DD-HHMMSS.sql.gz` files created by the `backup` service.

### When something is broken

- **Site down or throwing 500 errors**:
  - Check containers:
    - `docker compose ps`
  - Restart the web container:
    - `docker compose restart app`
  - Inspect logs for errors:
    - `docker compose logs app --tail=200`
- **Emails not sending**:
  - Ensure `queue` is running:
    - `docker compose ps`
  - Check queue logs:
    - `docker compose logs queue --tail=200`
  - If a job permanently fails, a crash email will be sent to `ADMIN_CRASH_EMAILS`.
- **Automatic reminders/cancellations not running**:
  - Ensure `scheduler` is running:
    - `docker compose ps`
  - Check scheduler logs:
    - `docker compose logs scheduler --tail=200`
- **Testing the Mail Configuration**:
  - You can manually fire off every email template in the system to verify SMTP and design:
    - `docker compose exec app php artisan app:send-test-emails your@email.com`

If restarting a container does not resolve an issue, avoid guessing. Capture logs, verify that the database is reachable, and consider testing a restore of a recent backup in a non-production environment before making destructive changes.

### How data is persisted and protected

- **Database**:
  - Lives in the `db-data` Docker volume. Normal container restarts or server reboots do not delete this data.
  - Nightly dumps compressed to `.sql.gz` files are written into the `backups` folder by the `backup` service.
- **Application storage**:
  - Files stored under `storage/` are persisted to the `storage-data` Docker volume.
- **Source code and configuration**:
  - Application code is versioned in GitHub.
  - The production `.env` file lives only on the server and is not committed to Git.

### Things that can erase data or make the system unusable

- **Removing Docker volumes**:
  - Running `docker compose down -v` or manually deleting the `db-data` or `storage-data` volumes will permanently destroy the database or stored files. Do not do this on production unless you intentionally want a clean start and have verified backups.
- **Destructive artisan commands**:
  - Commands such as `php artisan migrate:fresh`, `php artisan db:wipe`, or manual `DROP TABLE` operations will drop or clear data. Do not run them on production.
- **Deleting the backups directory**:
  - Removing the `backups` folder or its contents will discard your backup history. Always keep at least several recent backups and periodically copy some off the server.

If you are unsure whether an operation is safe, first try it on a local copy of the project or a staging environment that uses a separate database.