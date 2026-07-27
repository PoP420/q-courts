# 03 · System Architecture

## Principle: one backend, three faces

The booking backend is the single source of truth. The website and the
mobile app are both clients of the same API — scheduling logic, conflict
checks, and session state live in exactly one place.

```
┌─────────────────────────────────────────┐
│      Laravel Backend API (source of truth) │
│  - Courts, time slots, bookings            │
│  - Live session state (court in-use,        │
│    time remaining, game type/score)         │
└───────────┬───────────────┬─────────────────┘
            │               │
   ┌────────▼──────┐  ┌─────▼─────────┐
   │  Website        │  │  Mobile app    │
   │  (static HTML +  │  │  (Flutter)     │
   │  booking widget) │  │  staff-facing  │
   └─────────────────┘  └────────────────┘
```

## Component breakdown

### 1. Website
- Static HTML/Tailwind marketing page (already built)
- Booking widget to be added: calls the backend's `/api/bookings` and
  `/api/courts` endpoints directly from the browser
- Deployed independently of the backend (e.g. Netlify) — it's a static
  site that talks to the API over HTTP, not server-rendered by Laravel

### 2. Backend
- Laravel application, scaffolded with the official **Vue starter kit**
  (Inertia.js + Vue 3 + TypeScript + shadcn/ui components)
- The Inertia/Vue side of the starter kit is available for a future
  **staff admin dashboard** (managing courts, viewing/editing bookings)
  — it is not required for the public website or the mobile app, both of
  which talk to the plain JSON API under `/api/*`
- Core domain: `Court`, `Booking`, `CourtSession` (see `04-data-model.md`)
- Laravel Boost is installed for AI-assisted development — guidelines,
  agent skills, and an MCP server are all enabled so an AI agent can
  introspect the real schema/models instead of guessing

### 3. Mobile app
- Flutter, staff-facing only (not a customer-facing app)
- Talks to the same backend `/api/sessions/*` endpoints
- Core screens: live court board (which courts are occupied, time
  remaining), start-session flow, end-session flow, score entry

## Data flow examples

**Customer books online:**
Website booking widget → `POST /api/bookings` (source: `online`) →
backend checks for conflicts → booking created as `pending` → staff later
confirms it (via admin dashboard or direct DB access, until that flow is built).

**Walk-in customer arrives:**
Staff opens mobile app → `POST /api/bookings` (source: `walk_in`, auto-`confirmed`)
*or* skips booking entirely and goes straight to `POST /api/sessions/start`
with `booking_id: null`.

**Live court board:**
Mobile app polls `GET /api/sessions/active` → shows each occupied court,
computed `minutes_remaining`, and current game type/score.

## Deployment (planned)

- Backend: a VPS or managed PHP host reachable over the internet (not
  the developer's local machine, given the intermittent-connectivity
  constraint) — final host not yet decided
- Website: static hosting (Netlify, matching the developer's existing
  workflow for other client sites)
- Mobile app: distributed to staff devices directly (not necessarily
  published to app stores, since it's an internal tool)
