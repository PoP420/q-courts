# QCourts Booking Backend

The runnable Laravel backend lives at the **repository root** (this `qcourts`
folder), scaffolded with the official Vue starter kit (Inertia + Vue + TypeScript)
and Laravel Boost. It is the single source of truth for courts, bookings, and
live court sessions, shared by the marketing website and the staff mobile app.

This `qcourts-backend/` subfolder is just a snapshot of the QCourts-specific
source files that were merged into the real app — the canonical copies now live
at the paths below. Don't edit files here expecting the app to pick them up;
edit the ones in the Laravel app at the repo root.

## Where the code lives (in the Laravel app)

```
app/Models/Court.php
app/Models/Booking.php
app/Models/CourtSession.php
app/Http/Controllers/Api/CourtController.php
app/Http/Controllers/Api/BookingController.php
app/Http/Controllers/Api/CourtSessionController.php
database/migrations/2026_07_15_000001_create_courts_table.php
database/migrations/2026_07_15_000002_create_bookings_table.php
database/migrations/2026_07_15_000003_create_court_sessions_table.php
database/seeders/CourtSeeder.php
routes/api.php
```

## Database: PostgreSQL (via Docker)

The backend targets **PostgreSQL** (see `qcourts-docs/README.md` → Database
Connections): `qcourts` / `postgres` / `Kashitekuto09` on port 5432.

The whole stack — the PHP app **and** the Postgres database — runs in Docker
(`docker-compose.yml`). The app reaches the database over the compose network
using the service host `db`, so `.env` is set to:

```
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=qcourts
DB_USERNAME=postgres
DB_PASSWORD=Kashitekuto09
```

> Note: running `php artisan` directly on the Windows host will NOT work — this
> PHP build (Herd/php.new) has no `pdo_pgsql` driver. Use the Docker container,
> which ships `pdo_pgsql`.

## Run it (Docker — the supported way)

```bash
docker compose up -d --build
```

This builds the PHP image (PHP 8.5 + `pdo_pgsql`, with the installed `vendor/`
baked in), starts Postgres 17, runs the migrations, seeds the two courts, and
serves the API on port 8000.

```bash
curl http://localhost:8000/api/courts   # -> the two seeded courts
docker compose logs -f app               # watch the API
docker compose down                      # stop (keeps the database volume)
docker compose down -v                   # stop and wipe the database
```

Vendor packages are baked from the host's `vendor/` because Composer can't
reach Packagist in this environment — run `composer install` on a machine with
network access if you add/change dependencies.

API is live at `http://localhost:8000/api/...`

## Endpoints

| Method | Endpoint                      | Purpose                                   |
|--------|--------------------------------|--------------------------------------------|
| GET    | /api/courts                   | List courts + whether each is occupied now |
| GET    | /api/bookings?date=&court_id= | List bookings (used by website widget)     |
| POST   | /api/bookings                 | Create a booking (online or walk-in)       |
| PATCH  | /api/bookings/{id}            | Update status / reschedule                 |
| DELETE | /api/bookings/{id}            | Cancel a booking                           |
| GET    | /api/sessions/active          | Live court board (for the mobile app)      |
| POST   | /api/sessions/start           | Staff starts a session on a court          |
| PATCH  | /api/sessions/{id}/end        | Staff ends a session                       |
| PATCH  | /api/sessions/{id}/score      | Update live score during play              |

See `qcourts-docs/05-api-reference.md` for request/response details.

## Notes on the design

- **Walk-in bookings** are auto-confirmed; **online bookings** start as
  `pending` so you can approve/reject before they're locked in — change
  `BookingController::store()` if you'd rather auto-confirm both.
- **Booking conflicts** are checked server-side (`hasConflict()` in
  `BookingController`) so two people can't double-book the same court/time.
- **Sessions** are separate from bookings on purpose — a walk-in can start
  a session with no booking at all, and a booking doesn't have to turn
  into a session (no-shows happen). `booking_id` is nullable to support both.
- **CORS** is configured in `config/cors.php` (Netlify preview + localhost,
  plus `FRONTEND_URL` for production). Tighten the allowed origins before launch.
- No auth is wired up yet — add Sanctum before this goes anywhere public,
  especially for the `/sessions/*` staff endpoints.
