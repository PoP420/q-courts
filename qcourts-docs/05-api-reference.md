# 05 · API Reference

Base path: `/api`. No authentication is wired up yet — see
`06-deliverables-roadmap.md` for when that's planned. All request/response
bodies are JSON.

## Courts

### `GET /api/courts`
List active courts, each with its current `active_session` if occupied.

## Bookings

### `GET /api/bookings`
Query params: `date` (YYYY-MM-DD), `court_id`. Returns non-cancelled
bookings, ordered by date then start time.

### `POST /api/bookings`
Create a booking. Body:

```json
{
  "court_id": 1,
  "customer_name": "Juan Dela Cruz",
  "customer_phone": "09171234567",
  "booking_date": "2026-08-01",
  "start_time": "14:00",
  "end_time": "15:00",
  "source": "online",
  "notes": "optional"
}
```

`status` is set automatically: `walk_in` → `confirmed`, `online` → `pending`.
Returns `409` if the slot conflicts with an existing booking on that court.

### `PATCH /api/bookings/{id}`
Update `status` and/or reschedule `start_time`/`end_time` (re-checked for
conflicts if either changes).

### `DELETE /api/bookings/{id}`
Soft-cancels a booking (sets `status: cancelled`; does not hard-delete the row).

## Live sessions

### `GET /api/sessions/active`
Returns all sessions currently `active`, each including a computed
`minutes_remaining`. This is what the mobile app's court board polls.

### `POST /api/sessions/start`
Body:

```json
{
  "court_id": 1,
  "booking_id": null,
  "game_type": "Doubles",
  "planned_minutes": 30
}
```

Returns `409` if that court already has an active session. Returns `422` if
`booking_id` is set but the referenced booking belongs to a different court
(sessions must stay on the court the booking was made for).

### `PATCH /api/sessions/{id}/end`
Body (optional): `{"score": {"team_a": 11, "team_b": 7}}`. Marks the
session `completed` and stamps `ended_at`. Returns `422` if the session is
already `completed` (idempotent guard).

### `PATCH /api/sessions/{id}/score`
Body: `{"score": {"team_a": 11, "team_b": 7}}`. Updates score on an
active session without ending it. Returns `422` if the session is not
currently `active`.

## Known gaps (tracked in the roadmap)

- No auth/authorization on any endpoint yet — required before the staff
  app or admin dashboard goes anywhere near production
- CORS is configured (`config/cors.php`): the Netlify preview pattern and
  `localhost` dev origins are allowed, and `FRONTEND_URL` sets the production
  origin. Tighten the allowed origins to the real website domain before launch.
- No endpoint yet to confirm a `pending` online booking from the admin
  dashboard (currently only reachable via the generic `PATCH /bookings/{id}`)
