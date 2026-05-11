# Děvín Booking Management System

Laravel booking and administration system for a cabin rental. The app provides a Filament admin panel, a public booking form with live availability, automatic deposit and reminder handling, and Fio Bank payment import.

## Tech Stack

- Framework: Laravel 12
- PHP: 8.2+
- Admin panel: FilamentPHP 3
- Frontend: Livewire, FullCalendar, Flatpickr, Vite, Tailwind CSS 4
- Database: MySQL
- Settings: spatie/laravel-settings
- Payments: Fio Bank API integration

## Running The App

The repository contains two Docker Compose setups:

- Production: compose.yaml
- Development: compose.dev.yaml

Production runs `app`, `queue`, `scheduler`, `db`, `backup`, `meilisearch`, and `caddy`.
Development runs `app`, `queue`, `scheduler`, `db`, `meilisearch`, and `mailpit`.

To start production locally or on a server:

`docker compose -f compose.yaml up -d`

To start the development stack:

`docker compose -f compose.dev.yaml up -d`

The production stack uses `.env`. The development stack uses `.dev.env`.

Mailpit is available in the development stack at <http://localhost:8025>.

## What The App Does

### Admin Access

Access control is based on boolean flags on the `users` table:

- `is_super_admin`
- `can_manage_users`
- `can_view_bookings`
- `can_edit_bookings`
- `can_manage_financials`

These are enforced through Laravel policies. Super admins bypass the checks.

### Bookings

Bookings are managed through `BookingResource`.

- Guest counts are tracked for graduates, students, children, externals, and dogs.
- Dogs affect pricing but do not count toward bed capacity.
- `reserve_whole` blocks overlapping public bookings.
- `total_price`, `deposit_amount`, and `variable_symbol` are handled in the `Booking` model save logic.
- If `customer_email` is missing, confirmation and deadline enforcement are disabled.
- If `total_price` is `0`, the booking is treated as paid and payment enforcement is disabled.

### Settings And Calendar

- Global pricing, bank details, capacity, pending window, and deposit percentage live in `App\Settings\GeneralSettings`.
- The settings page is `App\Filament\Pages\GeneralSettings`.
- The reservation calendar is shown on `App\Filament\Pages\Calendar` through `App\Livewire\BookingCalendarWidget`.

### Public Booking Flow

The homepage renders `resources/views/booking.blade.php`.

- A read-only FullCalendar view shows active bookings.
- A Livewire form (`App\Livewire\BookingForm`) handles reservation submission.
- Availability is served from `/api/availability`.
- The booking form enforces capacity and overlap rules server-side with rate limiting and a database lock.

The public API returns cabin rules and active bookings only; it does not expose private banking details.

### Background Jobs And Email

Email templates live in `resources/views/emails/`.

- `App\Observers\BookingObserver` queues booking confirmation and update emails after commit.
- `bookings:process-pending` sends payment warnings and cancels overdue bookings based on `pending_window`.
- `bookings:process-fio-payments` imports Fio Bank transactions and updates bookings by variable symbol.
- Processed bank transactions are stored in `processed_transactions` to prevent duplicates.
- Failed queue jobs and production exceptions can send crash notices to `ADMIN_CRASH_EMAILS`.

## Scheduled Tasks

Defined in `routes/console.php`:

- `bookings:process-pending` runs at 00:00, 08:00, 12:00, and 20:00.
- `bookings:process-fio-payments` runs every 10 minutes from 07:00 to 21:00.

## Operations Guide

Routine checks:

- `docker compose ps`
- `docker compose logs app --tail=100`
- `docker compose logs queue --tail=100`
- `docker compose logs scheduler --tail=100`
- `ls backups`

Backup files are rotated after 30 days.

If something is broken:

- Restart the app container with `docker compose restart app`.
- Check queue logs if emails are not being sent.
- Check scheduler logs if reminders or Fio imports stop running.
- Avoid destructive commands such as `docker compose down -v`, `php artisan migrate:fresh`, or `php artisan db:wipe` on production.

If you need the deployment checklist, start with `DEPLOY.md`.
