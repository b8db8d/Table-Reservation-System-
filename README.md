# Table Reservation System

The application was coded by Claude Code using the [workflow](https://github.com/LaravelDaily/AI-Workflows-For-Laravel) created by [Povilas Korop](https://github.com/PovilasKorop).
The creation context is located in the [docs/](./docs/).

**Portfolio project.** Automated tests (manual, E2E, integration) live in a separate repository.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.4 · Laravel 13 · Laravel Fortify |
| Frontend | Vue 3 · Inertia.js v3 · Tailwind CSS 4 · shadcn/vue |
| Database | MySQL 8.0 |
| Real-time | Laravel Reverb (WebSockets) |
| Email (dev) | Mailpit (SMTP trap) |
| Containerisation | Docker + Docker Compose |
| Key packages | `spatie/laravel-permission` · `spatie/laravel-activitylog` · `spatie/laravel-query-builder` · `spatie/laravel-honeypot` · `propaganistas/laravel-phone` |

---

## Running with Docker

### Prerequisites

- **Docker**
- `make`

### First-time setup

```bash
make setup
```

This single command:

1. Copies `.env.docker.example` → `.env`
2. Builds and starts all containers
3. Runs migrations and seeds the database

### Daily use

```bash
make up       # start containers
make down     # stop containers
make status   # show container status
```

---

## Testing

Automated tests (Playwright) are maintained in a **separate repository** dedicated to QA. This repository contains the application under test only.

The application is intentionally configured with `HONEYPOT_ENABLED=false` and `APP_ENV=testing` in the Docker environment to allow automated E2E and API tests to run without friction.
