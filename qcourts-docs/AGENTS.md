# AGENTS.md — Instructions for AI Coding Agents

Read `README.md` and the numbered docs in this folder before making changes.
This file is the working-rules layer on top of that context.

## Project in one line

Laravel + Vue/Inertia booking backend, a static HTML marketing website, and
a Flutter staff app, all built by a solo developer as their own business —
optimize for simplicity and maintainability by one person, not for a team.

## Ground rules

1. **The backend is the source of truth.** Booking-conflict logic and
   session-state logic belong in the Laravel app only. Never duplicate
   scheduling/validation logic in the website JS or the Flutter app —
   they should call the API and trust its response, including its errors.
2. **Don't introduce new infrastructure without asking.** No new queues,
   caches, external services, or hosting dependencies unless the task
   explicitly calls for it. The developer has intermittent internet and
   works solo — every added moving part is a maintenance cost on them.
3. **Follow Laravel conventions over clever abstractions.** Standard
   Eloquent relationships, form request validation, resource controllers.
   If Laravel Boost's guidelines/skills are available in this environment,
   defer to them for framework-version-specific conventions.
4. **Server-side validation is non-negotiable**, especially for booking
   conflicts and session start/end — see `04-data-model.md` and
   `05-api-reference.md` for the exact rules already implemented.
5. **Match existing schema and enum values exactly** when adding features
   — e.g. booking `status` is `pending|confirmed|cancelled|completed`,
   `source` is `online|walk_in`, session `status` is `active|completed`.
   Don't rename or add values without updating `04-data-model.md` too.
6. **Keep docs in sync.** If a change affects requirements, the data
   model, the API surface, or the roadmap, update the relevant file in
   this folder in the same change — don't let this documentation drift
   from the real system.

## Stack specifics

- Backend: Laravel, scaffolded via the official **Vue starter kit**
  (Inertia.js + Vue 3 + TypeScript + shadcn/ui). Public JSON API lives
  under `routes/api.php`; any future admin UI uses the Inertia/Vue side
  the starter kit already set up — don't add a second frontend framework.
- Website: plain HTML/Tailwind (CDN), no build step, deployed statically.
  Keep it that way unless asked to change it — don't introduce a bundler
  or framework for what is currently a single static page.
- Mobile: Flutter/Dart, staff-facing only. No customer-facing mobile app
  is planned — don't build features on the assumption one exists.

## What "done" looks like for a task

- Server-side conflict/validation rules are enforced, not just assumed
- Enum values and column names match `04-data-model.md`
- New endpoints are added to `05-api-reference.md`
- New or changed scope is reflected in `06-deliverables-roadmap.md`
- No new external dependency was added without a clear reason tied to a
  requirement in `02-requirements.md`

## What NOT to do

- Don't add authentication scaffolding speculatively — it's tracked as a
  known gap (see `05-api-reference.md`) and will be requested explicitly
  when it's time (Phase 6 in the roadmap)
- Don't add payment processing — explicitly out of scope for now
- Don't assume multi-location or multi-tenant needs — this is one
  business, one location, 1–2 courts
- Don't hard-delete bookings — cancellation is a status change
  (`status: cancelled`), preserving history
