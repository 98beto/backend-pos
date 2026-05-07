# Deploy to Railway

## Overview

- `main` is production-oriented and deploys with the repository `Dockerfile`.
- `development` keeps the local Docker flow with `docker-compose.yml` and `Dockerfile.local`.
- Railway should run one web service from the repo and one managed PostgreSQL service.

## Required Environment Variables

Set these variables on the Railway web service:

```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:replace-me
APP_URL=https://your-service.up.railway.app

LOG_CHANNEL=stderr
LOG_LEVEL=info

DB_CONNECTION=pgsql
DB_HOST=${{Postgres.PGHOST}}
DB_PORT=${{Postgres.PGPORT}}
DB_DATABASE=${{Postgres.PGDATABASE}}
DB_USERNAME=${{Postgres.PGUSER}}
DB_PASSWORD=${{Postgres.PGPASSWORD}}

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

## First Deploy

After the first successful build, run this command from the Railway shell:

```bash
php artisan migrate --force
```

## Seeders

- Do not run development seeders in production.
- `DatabaseSeeder` skips `DeviceSeeder` automatically when `APP_ENV=production`.
- Create production devices manually after deploy with real secrets.

## Notes

- The runtime uses `nginx + php-fpm + supervisord` in one container.
- Logs are expected through `stderr` so Railway can capture them.
- `route:cache` is intentionally not used because `routes/web.php` still contains a closure.
