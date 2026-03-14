## Maintenance guide

This document describes the minimal routine tasks to keep the production server and application healthy. It assumes you followed `DEPLOY.md` for the initial setup.

### 1. Regular checks (monthly or when notified)

- **Containers running**
  - From `/srv/devin-booking`:
    ```bash
    docker compose ps
    ```
  - Confirm `app`, `queue`, `scheduler`, `db`, `backup`, and `caddy` are `running` / `Up`.

- **Health endpoint**
  - In a browser or via curl:
    ```bash
    curl -f https://your-domain.example/health
    ```
  - Should return `{"status":"ok"}` with HTTP 200.

- **Disk space**
  - Check free space:
    ```bash
    df -h
    ```
  - If any partition is above ~80%, consider cleaning old logs or copying/deleting old backups.

- **Backups**
  - List backups:
    ```bash
    cd /srv/devin-booking
    ls backups
    ```
  - You should see recent `backup-YYYY-MM-DD-HHMMSS.sql.gz` files.
  - Occasionally copy a backup off the server:
    ```bash
    scp youruser@server:/srv/devin-booking/backups/backup-YYYY-MM-DD-*.sql.gz .
    ```

### 2. Updating the application (when you push new code)

1. SSH into the server:

   ```bash
   ssh youruser@your-server
   cd /srv/devin-booking
   ```

2. Pull latest code and rebuild:

   ```bash
   git pull origin main          # or your branch
   docker compose build
   docker compose up -d
   ```

3. Run database migrations:

   ```bash
   docker compose exec app php artisan migrate --force
   ```

4. Quick sanity check:
   - Visit `https://your-domain.example`.
   - Hit `https://your-domain.example/health`.

### 3. When something is wrong

See also the "Operations Guide for Future Admins" section in `README.md`.

- **Site down / 5xx errors**
  - Check containers:
    ```bash
    cd /srv/devin-booking
    docker compose ps
    ```
  - Restart the app:
    ```bash
    docker compose restart app
    ```
  - Check logs:
    ```bash
    docker compose logs app --tail=200
    ```

- **Emails or background tasks not running**
  - Check `queue` and `scheduler`:
    ```bash
    docker compose ps
    docker compose logs queue --tail=200
    docker compose logs scheduler --tail=200
    ```

- **Suspected data issue**
  - Do **not** run destructive commands (`migrate:fresh`, `db:wipe`, manual `DROP TABLE`) on production.
  - Consider testing a restore of a recent backup in a separate test environment first.

### 4. Things that should rarely or never change

- **Production `.env`**
  - Keep it only on the server.
  - Only change values when you understand the impact (e.g. DB host, mail, URLs).

- **Docker volumes**
  - Do not run `docker compose down -v` or `docker volume rm` on `db-data` or `storage-data` in production unless you intentionally want to wipe all data and have verified backups.

By following this guide plus `DEPLOY.md`, the system should require minimal manual attention and still remain recoverable if something goes wrong.

