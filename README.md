# Backend API

Laravel 13 backend for the POS system.

## Branch Strategy

- `main`: production-oriented branch for Railway deploys.
- `development`: local-first branch for Docker-based development.

## Production (Railway)

This branch (main) is production-oriented and intended for deploys to Railway.

Production runtime uses:

- `Dockerfile`
- `.docker/railway/nginx.conf`
- `.docker/railway/supervisord.conf`
- `.docker/railway/start-container`

Deployment notes live in `docs/deploy-railway.md`.

## API Docs

Full API documentation lives in:

- `.docs/API_DOCS.md`
