# 02 · Requirements

## Functional requirements

### Website
- FR-1: Public marketing page introducing QCourts Pickleball and the sport itself
- FR-2: Display grand opening status/announcement
- FR-3: Show location with a map and directions link
- FR-4: Let a visitor leave contact details to be notified at opening (interim, pre-booking-system state)
- FR-5: Once the backend exists, let a visitor view court availability and book a slot online

### Booking system
- FR-6: Support two booking sources: **online** (customer-initiated) and **walk-in** (staff-initiated)
- FR-7: Prevent double-booking — no two bookings may overlap on the same court
- FR-8: Online bookings start as `pending`; walk-in bookings are auto-`confirmed`
- FR-9: Support cancelling a booking
- FR-10: Support rescheduling a booking (change time), re-checking for conflicts
- FR-11: List bookings filterable by date and by court

### Live court/session monitoring (mobile app)
- FR-12: Staff can start a live session on a court, optionally linked to an existing booking, or standalone for walk-ins
- FR-13: A court can only have one active session at a time
- FR-14: Track planned duration and compute minutes remaining in real time
- FR-15: Staff can end a session manually
- FR-16: Track game type (e.g. singles/doubles) per session
- FR-17: Track and update score during an active session
- FR-18: Provide a live view of all currently-active sessions across all courts (the "court board")

## Non-functional requirements

- NFR-1: **Low-connectivity tolerance** — development workflow should not assume constant internet; production hosting should be reliable even if the developer's local connection isn't
- NFR-2: **Solo-maintainable** — prefer Laravel/framework conventions over custom abstractions; minimize moving parts
- NFR-3: **Small scale, not enterprise scale** — 1–2 courts, one location; do not over-engineer for multi-tenant or multi-location use unless explicitly requested later
- NFR-4: **Mobile-first for staff app** — the Flutter app is used on a phone/tablet courtside, so UI must be usable one-handed, in outdoor lighting, without requiring precise typing
- NFR-5: **Data integrity over convenience** — booking conflict checks happen server-side, never trusted from the client alone
- NFR-6: **Accessible, plain-language website copy** — audience may be unfamiliar with pickleball as a sport

## Explicitly out of scope (for now)

- Payments / online payment collection
- Multi-location support
- Customer accounts / login on the website
- Tournament or league management
- Staff scheduling / payroll

These may become requirements later, but nothing currently being built
should assume they're needed yet.
