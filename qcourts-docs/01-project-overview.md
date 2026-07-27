# 01 · Project Overview

## What QCourts is

QCourts Pickleball is a new pickleball court business opening in Makilala,
North Cotabato, Philippines, at Molave Street, Barangay Poblacion (beside
Victory Christian Schoolhouse, in front of RHU Makilala). It will open with
1–2 courts and support both walk-in play and online booking.

## Why this system exists

The owner is building the supporting software in-house, with AI assistance,
rather than buying an off-the-shelf court-booking SaaS. The system needs to:

- Give the business an online presence ahead of the grand opening
- Let customers book court time online, alongside walk-in play
- Let on-site staff track which courts are occupied, for how much longer,
  and what game is being played — a live "court status board"

## The three pieces

1. **Marketing website** — public-facing, promotes the grand opening,
   explains the sport for a local audience unfamiliar with pickleball,
   shows location, and (once the backend is wired in) lets people book
   a court online.
2. **Booking backend** — a Laravel API that is the single source of truth
   for courts, bookings, and live sessions. Shared by the website and the
   mobile app so there's one place scheduling logic lives.
3. **Staff mobile app** — Flutter app used on-site by staff to start/stop
   a court session, see time remaining, and log game type/score. This is
   the "time/game monitoring" requirement.

## Who uses what

| User | Touchpoint |
|---|---|
| Prospective customer | Website — learns about the sport, finds the location, books a court |
| Walk-in customer | No app — staff handles it directly via the mobile app |
| Staff on-site | Mobile app — starts/ends sessions, sees the live court board |
| Owner | All of the above, plus whatever admin/reporting view gets built on top of the backend |

## Constraints worth knowing

- Primary dev machine is a personal laptop with **intermittent internet
  connectivity** — favor tooling and workflows that don't require constant
  connectivity (e.g. SQLite over a hosted DB during development).
- This is a **solo-developer** project — favor simplicity and convention
  over configurability; avoid introducing infrastructure the owner will
  have to maintain alone (e.g. no need for Kubernetes, multi-region
  deployment, etc. at this stage).
- Target users (customers) are a **general local audience**, not
  necessarily tech-savvy — the website copy already leans toward explaining
  the sport rather than assuming familiarity with it.
