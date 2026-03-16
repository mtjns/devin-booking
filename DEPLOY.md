## Production deployment (Docker, single server)

This project is designed to run in production as a small Docker stack on a single Linux server, pulling code from GitHub and starting everything with `docker compose up -d`.

### 1. One-time server setup (Ubuntu)

- **Install dependencies** on the server:
  - Docker Engine
  - Docker Compose plugin (so the `docker compose` command works)
  - Git
- **Clone the repository** (example path):

```bash
sudo mkdir -p /srv
sudo chown "$USER":"$USER" /srv
cd /srv
git clone git@github.com:YOUR_USERNAME/devin-booking.git
cd devin-booking
```

#### 1.1 Basic server hardening and auto-updates

- Create a non-root user (done during Ubuntu install) and add it to the `docker` group:

  ```bash
  sudo usermod -aG docker youruser
  ```

- Configure a simple firewall (UFW) to only expose SSH and HTTP/HTTPS:

  ```bash
  sudo ufw allow OpenSSH
  sudo ufw allow 80/tcp
  sudo ufw allow 443/tcp
  sudo ufw enable
  ```

- Enable unattended security updates:

  ```bash
  sudo apt update
  sudo apt install unattended-upgrades
  sudo dpkg-reconfigure unattended-upgrades
  ```

- (Optional but recommended) enable automatic reboot after kernel/security updates by editing:

  ```bash
  sudo nano /etc/apt/apt.conf.d/50unattended-upgrades
  ```

  and setting:

  ```text
  Unattended-Upgrade::Automatic-Reboot "true";
  Unattended-Upgrade::Automatic-Reboot-Time "03:00";
  ```

- Ensure Docker starts automatically on boot:

  ```bash
  sudo systemctl enable docker
  ```

With this in place, Ubuntu will keep itself patched, and Docker (with your app stack) will come back automatically after reboots.

### 2. Create the production `.env`

On the server, copy the example file and edit it:

```bash
cp .env.deploy .env
```

Set at least:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://your-domain.example`
- `APP_KEY=` (generate once with `php artisan key:generate --show` in a temp container or locally and paste here)
- Database:
  - `DB_CONNECTION=mysql`
  - `DB_HOST=db`
  - `DB_DATABASE=devin_booking` (or your name)
  - `DB_USERNAME=devin` (or a strong user name)
  - `DB_PASSWORD=` (strong password)
- Queues / sessions / logging (example):
  - `QUEUE_CONNECTION=database`
  - `SESSION_DRIVER=database`
  - `SESSION_ENCRYPT=true`
  - `LOG_CHANNEL=stack`
  - `LOG_STACK=daily`
  - `LOG_LEVEL=info`
- Locale / timezone (already configured in code):
  - Aplikace je nastavená na češtinu (`locale = cs`) a časové pásmo `Europe/Prague`.
  - Není potřeba žádné další nastavení v `.env`, pokud chcete zachovat češtinu i v produkci.
- Mail:
  - Configure your SMTP host, user, password, and from address
- Crash emails:
  - `ADMIN_CRASH_EMAILS=you@example.com`
  - `LETSENCRYPT_ACCOUNT_EMAIL=you@example.com` (email for Let's Encrypt via Caddy)
  - `APP_PRIMARY_DOMAIN=your-domain.example` (primary domain used by Caddy for HTTPS)
- Proxies & HTTPS:
  - `TRUSTED_PROXIES=*` (If you use the built-in Caddy container, `*` is generally fine since it runs in the same Docker network. If you are behind an external proxy like Cloudflare or an external Nginx, set this to the proxy's IP address or a comma-separated list of IPs to securely detect HTTPS and client IPs.)
- Fio Bank API (optional, for automated payment processing):
  - `FIO_BANK_API_TOKEN=your_token_here` (Get a token from your Fio Bank internet banking: Settings → API → Create new token. This is optional if you manually manage all payments.)

> Keep this `.env` file on the server only. Do not commit it to Git.

### 3. Build and start the stack

From the project directory on the server:

```bash
rm compose.yml  # Remove the compose file if it exists, it is for developement only and not used in production
docker compose -f docker-compose.yml build
docker compose -f docker-compose.yml up -d
```

This will:

- Build the `devin-booking-app` image (PHP-FPM + Nginx + built frontend).
- Start:
  - `app` (web server)
  - `queue` (queue worker)
  - `scheduler` (Laravel scheduler)
  - `db` (MySQL)
  - `backup` (automated nightly DB backups)

The app will be available on port `8080` by default (see `docker-compose.yml`).

### 4. Run database migrations (first deploy)

After the containers are up:

```bash
docker compose exec app php artisan migrate --force # You may need to run this twice if the app starts before the database is fully ready. Just run it again until it succeeds.
```

Optionally seed:

```bash
docker compose exec app php artisan db:seed --force # Only if you want to add test data. Not recommended in production.
```

### 4.1 Create the first super admin user

Once migrations are complete, create your first admin account using Laravel Tinker:

```bash
docker compose -f docker-compose.yml exec app php artisan tinker
```

In the Tinker shell, paste this and replace the values with your actual credentials:

```php
App\Models\User::create([
    'name' => 'Admin',
    'email' => 'your-email@example.com',
    'password' => bcrypt('your-secure-password'),
    'is_super_admin' => true,
    'can_manage_users' => true,
    'can_view_bookings' => true,
    'can_edit_bookings' => true,
    'can_manage_financials' => true,
]);
```

Press Enter and then type `exit` to close Tinker.

You can now log in to the admin panel with the email and password you just created.

### 5. Accessing the app

- For initial testing (without HTTPS), from your browser: `http://YOUR_SERVER_IP:8080`
- In production, you can access the app via HTTPS once Caddy is configured (see below), e.g. `https://your-domain.example`.

### 6. Updating to a new version

When you push new code to GitHub and want to deploy it:

```bash
ssh youruser@your-server
cd /srv/devin-booking
git pull origin main        # or your default branch
docker compose -f docker-compose.yml build        # rebuild the app image if code or deps changed
docker compose -f docker-compose.yml up -d        # recreate containers with the new image
docker compose -f docker-compose.yml exec app php artisan migrate --force

# Optimize application (cache config, routes, and views for performance)
docker compose -f docker-compose.yml exec app php artisan optimize
docker compose -f docker-compose.yml exec app php artisan view:cache

# Restart queue worker so it picks up the new code
docker compose -f docker-compose.yml exec app php artisan queue:restart
```

### 7. Data persistence and backups

- **Database data** is stored in the `db-data` Docker volume.
- **Application storage** (e.g. files under `storage/`) is stored in the `storage-data` volume.
- **Automated backups**:
  - The `backup` service in `docker-compose.yml` connects to the `db` container once per day and creates a compressed dump in the `backups` directory (relative to the project root), with names like `backup-YYYY-MM-DD-HHMMSS.sql.gz`.
  - Old backups older than 14 days are automatically deleted inside the container to keep disk usage under control.

You should still periodically copy a backup file off the server (e.g. to your laptop or cloud storage) for disaster recovery.

#### Restoring a backup (conceptual steps)

1. **Stop writes to the database**:
   - Temporarily stop the app container or put the site in maintenance mode so no new reservations are created while you restore.
2. **Pick the backup file** you want to restore, e.g. `backups/backup-2026-03-05-030000.sql.gz`.
3. **Decompress it** on the server:

   ```bash
   gunzip backups/backup-2026-03-05-030000.sql.gz
   # leaves backups/backup-2026-03-05-030000.sql
   ```

4. **Import it into the running `db` container**:

   ```bash
   docker compose -f docker-compose.yml exec -T db mysql -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" < backups/backup-2026-03-05-030000.sql
   ```

5. **Bring the app back**:
   - `docker compose restart app queue scheduler`

Always test this process on a non-production environment at least once so you are confident it works before you need it in an emergency.

### 8. Logs and troubleshooting

- Laravel logs live inside the container under `storage/logs`.
- Docker logs:

```bash
docker compose -f docker-compose.yml logs app
docker compose -f docker-compose.yml logs queue
docker compose -f docker-compose.yml logs scheduler
```

- Restart a single service:

```bash
docker compose -f docker-compose.yml restart app
```

### 9. Testing Email Delivery & Layouts

Once your SMTP is configured in `.env`, you can safely trigger a full test of the mailing system. This will inject a temporary booking, send exactly one of every email template to an address of your choosing, and then immediately map and delete the test data.

```bash
docker compose -f docker-compose.yml exec app php artisan app:send-test-emails your@email.com
```

### 10. Automated Payment Processing (Fio Bank API)

If you have a Fio Bank account and want to automatically match incoming payments to bookings, you can integrate with the Fio Bank API. This command fetches daily transactions, matches them by variable symbol to your bookings, and updates the `paid_amount` field. The system Observer then automatically handles emails, status changes and deadline enforcement.

**Setting up the Fio Bank integration:**

1. Log into your [Fio Bank internet banking](https://www.fiobanking.cz/logon).
2. Navigate to **Settings** → **API** → **Create new token** and generate a REST API token.
3. Add the token to your `.env` file:
   ```bash
   FIO_BANK_API_TOKEN=your_generated_token_here
   ```
4. Run the database migration to create the `processed_transactions` table (this tracks which bank transactions have been processed, preventing duplicates):
   ```bash
   docker compose -f docker-compose.yml exec app php artisan migrate
   ```
5. The system is now configured. To test it manually:
   ```bash
   docker compose -f docker-compose.yml exec app php artisan bookings:process-fio-payments
   ```

**Testing with simulated payments:**

You can test the payment workflow without touching real bank data using the test command:

```bash
# Test a single payment
docker compose -f docker-compose.yml exec app php artisan bookings:test-fio-payment "VS1234567" 5000

# This will:
# - Find the booking with variable symbol "VS1234567"
# - Update its paid_amount to 5000 Kč
# - Trigger all Observer logic (emails, status changes, etc.)
# - Mark it in the processed_transactions table as a TEST transaction
```

**Testing the actual Fio Bank API connection:**

Before setting up automated processing, verify that your API token works:

```bash
# Connect to Fio Bank API and see what transactions exist (dry-run, no processing)
docker compose -f docker-compose.yml exec app php artisan bookings:test-fio-api

# This will:
# - Verify the token is configured
# - Fetch actual transactions from Fio Bank
# - Show which ones would be matched to bookings
# - Show which would be skipped and why
# - Display what status changes would be triggered

# If everything looks good, process the transactions:
docker compose -f docker-compose.yml exec app php artisan bookings:test-fio-api --process
```

**Scheduling automated processing:**

The Fio Bank payment processing is automatically scheduled in your application's scheduler (`routes/console.php`) to run daily at 1:00 AM. This is the same pattern used by the pending bookings background job (`bookings:process-pending`).

- Runs automatically every day at 1:00 AM (UTC/Prague time, depending on your `APP_TIMEZONE` setting).
- If the token is not configured, the command gracefully skips.
- If an error occurs, admins receive an email notification to `ADMIN_CRASH_EMAILS`.

You can still run it manually at any time with:

```bash
docker compose -f docker-compose.yml exec app php artisan bookings:process-fio-payments
```

To change the schedule, edit `routes/console.php` and modify the schedule definition (e.g., running it twice daily, or at a different time).

**Technical details:**

- The Fio Bank API enforces a 30-second minimum delay between requests. The command respects this rate limit automatically (use `--force` flag to skip for testing).
- Only the `last` endpoint is used, which maintains an automatic checkpoint on Fio Bank's servers to prevent re-processing old transactions.
- **Error Notifications:** If the payment processing command encounters any errors (API connectivity, exceptions, etc.), it will automatically send an email to all addresses in `ADMIN_CRASH_EMAILS` with details about what failed. This ensures you're notified immediately if something goes wrong instead of silently failing.
- For more information on how the system handles partial payments, idempotency, and validation, see the **Automated Communication & Payment Tracking** section in `README.md`.

### 11. HTTPS with Caddy (reverse proxy in Docker)

This project includes a `caddy` service in `docker-compose.yml` that can terminate HTTPS and proxy requests to the `app` container.

**Requirements:**

- A domain name (e.g. `your-domain.example`).
- DNS A-record pointing `your-domain.example` to your server's public IP.

**Steps:**

1. Edit `Caddyfile` in the project root:

   - Ensure the site block uses `{env.APP_PRIMARY_DOMAIN}` (already present). No further change is usually needed.

2. Ensure ports 80 and 443 are open on your server firewall.

3. Set `LETSENCRYPT_ACCOUNT_EMAIL` in your `.env` to a real email address for Let's Encrypt notifications.

4. Rebuild and restart the stack:

   ```bash
   docker compose -f docker-compose.yml up -d caddy
   ```

   (If the app is already running, Caddy will join the stack and start handling HTTPS.)

Once configured, your users should access the site via `https://your-domain.example` (using the value you set in `APP_PRIMARY_DOMAIN`). The app remains accessible on `http://YOUR_SERVER_IP:8080` for debugging, but in production you can restrict that port using a firewall if desired.

### 11. Security and hardening notes

- Use strong passwords for:
  - Database users
  - Any admin accounts
- Restrict direct access to the MySQL port (use firewall or avoid publishing it if not needed).
- For production, prefer accessing the app only via HTTPS through the `caddy` service, and restrict or close direct access to port 8080 from the internet.

