# 06 · Deliverables & Roadmap

Status as of this document's writing. Update the checkboxes as work lands.

## Phase 1 — Marketing website
- [x] Landing page: hero, sport explainer, court info, location, notify-me form
- [ ] Wire "Notify Me" form to a real endpoint (currently UI-only)
- [ ] Replace the placeholder notify form with a live booking widget once
      the backend supports it

## Phase 2 — Booking backend (core API)
- [x] Schema: `courts`, `bookings`, `court_sessions`
- [x] Models: `Court`, `Booking`, `CourtSession`
- [x] `BookingController` with server-side conflict checking
- [x] `CourtSessionController` for live session start/end/score
- [x] Laravel project scaffolded (Vue starter kit)
- [x] Laravel Boost installed (guidelines + skills + MCP)
- [x] CORS configured so the public website can call the API
- [x] Seed data for the 1–2 real courts in production
- [x] Pest feature tests covering conflict checks, session start/end/score, and cancellation
- [x] Server-side guards hardened: `booking_id` must match the session's court,
      score only on `active` sessions, `end` is idempotent (rejects already-ended)
- [x] API reference (`05-api-reference.md`) documents the new `422` responses

## Phase 3 — Booking widget on website
- [x] Availability view (pick a date + court, see reserved times)
- [x] Booking form calling `POST /api/bookings` (source: `online`)
- [x] Confirmation state / pending-approval messaging for online bookings
- [x] Static site added at `qcourts-website/index.html` (reuses the marketing page design)

## Phase 4 — Staff admin dashboard (Vue/Inertia, in the existing Laravel app)
- [x] Auth for staff/owner login
- [x] Bookings list — confirm/cancel/reschedule
- [x] Courts management
- [x] Live court board (mirrors what the mobile app shows)

## Phase 5 — Mobile app (Flutter, staff-facing)
- [ ] Live court board screen (poll `GET /api/sessions/active`)
- [ ] Start-session flow (court, game type, planned duration)
- [ ] End-session flow
- [ ] Score entry/update during an active session
- [ ] Auth (shared with admin dashboard, once that exists)

## Phase 6 — Launch readiness
- [ ] Auth/authorization across all non-public endpoints
- [ ] Backend hosting decided and deployed (not the dev laptop)
- [ ] Website deployed (Netlify)
- [ ] End-to-end test: online booking → staff confirms → session started → session ended

## Explicitly deferred (see `02-requirements.md` → Out of scope)
- Online payments
- Multi-location support
- Customer accounts on the website
- Tournament/league management
