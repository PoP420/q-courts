# 04 · Data Model

## Entity relationship overview

```
Court 1───* Booking
Court 1───* CourtSession
Booking 1───0..1 CourtSession   (a session may optionally reference the booking that led to it)
```

A `CourtSession` can exist without a `Booking` (walk-in play with no
reservation). A `Booking` does not require a `CourtSession` (a booking can
be cancelled or simply never checked in).

## `courts`

| Column | Type | Notes |
|---|---|---|
| id | bigint, PK | |
| name | string | e.g. "Court 1" |
| is_active | boolean | default true |
| timestamps | | |

## `bookings`

| Column | Type | Notes |
|---|---|---|
| id | bigint, PK | |
| court_id | FK → courts | cascade delete |
| customer_name | string | |
| customer_phone | string | |
| booking_date | date | |
| start_time | time | |
| end_time | time | must be after start_time |
| status | enum | `pending`, `confirmed`, `cancelled`, `completed` |
| source | enum | `online`, `walk_in` |
| notes | text, nullable | |
| timestamps | | |

Indexed on `(court_id, booking_date)` for conflict lookups.

**Conflict rule:** two bookings on the same court and date conflict if
`start_time < other.end_time AND end_time > other.start_time`. Enforced
server-side in the booking controller, not left to the client.

## `court_sessions`

| Column | Type | Notes |
|---|---|---|
| id | bigint, PK | |
| court_id | FK → courts | cascade delete |
| booking_id | FK → bookings, nullable | null = walk-in with no prior booking |
| game_type | string, nullable | e.g. "Singles", "Doubles" |
| planned_minutes | unsigned int | default 30 |
| started_at | timestamp | |
| ended_at | timestamp, nullable | |
| score | json, nullable | e.g. `{"team_a": 11, "team_b": 7}` |
| status | enum | `active`, `completed` |
| timestamps | | |

Indexed on `(court_id, status)` — the mobile app's most frequent query is
"which sessions are active right now."

**Business rule:** a court may have at most one `active` session at a time
— enforced in `CourtSessionController::start()` before creating a new one.

**Computed field:** `minutes_remaining` (model accessor, not stored) —
`planned_minutes - minutes elapsed since started_at`, floored at zero,
returns `0` for any non-active session.
