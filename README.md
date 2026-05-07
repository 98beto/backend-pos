# Backend API

Laravel 13 backend for the POS system.

## Branch Strategy

- `main`: production-oriented branch for Railway deploys.
- `development`: local-first branch for Docker-based development.

## Local Docker Workflow

Local development uses:

- `docker-compose.yml`
- `Dockerfile.local`
- `.docker/local/nginx/default.conf`

Start locally with:

```bash
docker compose up --build -d
docker compose exec app php artisan migrate:fresh --seed --force
```

The API will be available at:

```text
http://localhost:8000
```

## Railway Deploy

Production deploy uses:

- `Dockerfile`
- `.docker/railway/nginx.conf`
- `.docker/railway/supervisord.conf`
- `.docker/railway/start-container`

Deployment notes live in:

- `docs/deploy-railway.md`

## API Docs

Full API documentation lives in:

- `.docs/API_DOCS.md`
