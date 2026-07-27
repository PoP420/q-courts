<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, reactive, ref, watch } from 'vue';

interface NotifyForm {
    name: string;
    phone: string;
}

interface Court {
    id: number;
    name: string;
}

interface Booking {
    id: number;
    court_id: number;
    customer_name: string;
    customer_phone: string;
    booking_date: string;
    start_time: string;
    end_time: string;
    status: string;
}

interface CourtAvailability {
    id: number;
    name: string;
    is_active: boolean;
    available: boolean;
    reason: 'inactive' | 'occupied' | 'booked' | null;
}

interface BookingForm {
    court_id: number | '';
    booking_date: string;
    start_time: string;
    end_time: string;
    customer_name: string;
    customer_phone: string;
}

const notifyForm = reactive<NotifyForm>({
    name: '',
    phone: '',
});

const notifyErrors = ref<Partial<Record<keyof NotifyForm, string>>>({});
const notifySubmitting = ref(false);
const notifySuccess = ref(false);

const courts = ref<Court[]>([]);
const availability = ref<Booking[]>([]);

const bookingForm = reactive<BookingForm>({
    court_id: '',
    booking_date: '',
    start_time: '09:00',
    end_time: '10:00',
    customer_name: '',
    customer_phone: '',
});

const bookingErrors = ref<Partial<Record<keyof BookingForm, string>>>({});
const bookingSubmitting = ref(false);
const bookingSuccess = ref(false);
const slotAvailability = ref<{
    checking: boolean;
    available: boolean | null;
    reason: CourtAvailability['reason'];
}>({
    checking: false,
    available: null,
    reason: null,
});

const canLoadAvailability = computed(() => Boolean(bookingForm.court_id && bookingForm.booking_date));
const canCheckSlotAvailability = computed(() => Boolean(
    bookingForm.court_id
    && bookingForm.booking_date
    && bookingForm.start_time
    && bookingForm.end_time
    && bookingForm.start_time < bookingForm.end_time,
));

const slotReasonMessage = computed(() => {
    if (slotAvailability.value.reason === 'inactive') {
        return 'This court is inactive for booking.';
    }
    if (slotAvailability.value.reason === 'occupied') {
        return 'This court is occupied by an active session during that time.';
    }
    if (slotAvailability.value.reason === 'booked') {
        return 'This time range is already booked on this court.';
    }

    return 'This time slot is unavailable.';
});

function validateNotifyForm(): boolean {
    notifyErrors.value = {};

    if (!notifyForm.name.trim()) {
        notifyErrors.value.name = 'Please enter your full name.';
    }

    if (!notifyForm.phone.trim()) {
        notifyErrors.value.phone = 'Please enter your phone number.';
    } else if (!/^[0-9+\-\s()]{7,20}$/.test(notifyForm.phone.trim())) {
        notifyErrors.value.phone = 'Please enter a valid phone number.';
    }

    return Object.keys(notifyErrors.value).length === 0;
}

async function handleNotifySubmit() {
    if (!validateNotifyForm()) {
        return;
    }

    notifySubmitting.value = true;
    notifySuccess.value = false;

    try {
        await new Promise(resolve => setTimeout(resolve, 1500));
        notifySuccess.value = true;
        notifyForm.name = '';
        notifyForm.phone = '';
    } catch {
        notifyErrors.value.name = 'Something went wrong. Please try again.';
    } finally {
        notifySubmitting.value = false;
    }
}

function escapeHtml(s: string): string {
    const replacements: Record<string, string> = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
    };

    return s.replace(/[&<>"']/g, m => replacements[m] ?? m);
}

function validateBookingForm(): boolean {
    bookingErrors.value = {};

    if (!bookingForm.court_id) {
        bookingErrors.value.court_id = 'Please select a court.';
    }
    if (!bookingForm.booking_date) {
        bookingErrors.value.booking_date = 'Please select a date.';
    }
    if (!bookingForm.customer_name.trim()) {
        bookingErrors.value.customer_name = 'Please enter your full name.';
    }
    if (!bookingForm.customer_phone.trim()) {
        bookingErrors.value.customer_phone = 'Please enter your phone number.';
    } else if (!/^[0-9+\-\s()]{7,20}$/.test(bookingForm.customer_phone.trim())) {
        bookingErrors.value.customer_phone = 'Please enter a valid phone number.';
    }
    if (bookingForm.start_time >= bookingForm.end_time) {
        bookingErrors.value.end_time = 'End time must be after start time.';
    }

    return Object.keys(bookingErrors.value).length === 0;
}

async function api(path: string, opts: RequestInit = {}) {
    try {
        const res = await fetch(`/api${path}`, {
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            ...opts
        });
        let data: unknown = null;
        try { data = await res.json(); } catch { /* no body */ }
        return { res, data };
    } catch (err) {
        throw { message: 'Network error. Please check your connection and try again.' };
    }
}

async function loadCourts() {
    try {
        const { data } = await api('/courts');
        courts.value = (data as Court[]) || [];
    } catch (err: unknown) {
        const message = err instanceof Error ? err.message : 'Failed to load courts';
        bookingErrors.value.court_id = message;
    }
}

async function loadAvailability() {
    const courtId = bookingForm.court_id;
    const date = bookingForm.booking_date;
    if (!courtId || !date) {
        availability.value = [];
        return;
    }
    try {
        const { data } = await api(`/bookings?date=${encodeURIComponent(date)}&court_id=${courtId}`);
        availability.value = (data as Booking[]) || [];
    } catch {
        availability.value = [];
    }
}

async function checkSelectedSlotAvailability() {
    if (!canCheckSlotAvailability.value) {
        slotAvailability.value = {
            checking: false,
            available: null,
            reason: null,
        };
        return;
    }

    slotAvailability.value = {
        checking: true,
        available: null,
        reason: null,
    };

    const date = encodeURIComponent(bookingForm.booking_date);
    const startTime = encodeURIComponent(bookingForm.start_time);
    const endTime = encodeURIComponent(bookingForm.end_time);

    try {
        const { data } = await api(`/courts/availability?date=${date}&start_time=${startTime}&end_time=${endTime}`);
        const courtsAvailability = (data as CourtAvailability[]) || [];
        const selectedCourt = courtsAvailability.find((court) => court.id === Number(bookingForm.court_id));

        slotAvailability.value = {
            checking: false,
            available: selectedCourt?.available ?? null,
            reason: selectedCourt?.reason ?? null,
        };
    } catch {
        slotAvailability.value = {
            checking: false,
            available: null,
            reason: null,
        };
    }
}

async function handleBookingSubmit() {
    if (!validateBookingForm()) return;

    await checkSelectedSlotAvailability();

    if (slotAvailability.value.available === false) {
        bookingErrors.value.end_time = slotReasonMessage.value;
        return;
    }

    bookingSubmitting.value = true;

    try {
        const payload = {
            court_id: bookingForm.court_id,
            customer_name: bookingForm.customer_name.trim(),
            customer_phone: bookingForm.customer_phone.trim(),
            booking_date: bookingForm.booking_date,
            start_time: bookingForm.start_time,
            end_time: bookingForm.end_time,
            source: 'online'
        };

        const { res, data } = await api('/bookings', {
            method: 'POST',
            body: JSON.stringify(payload)
        });

        if (res.ok) {
            bookingSuccess.value = true;
            bookingForm.court_id = '';
            bookingForm.customer_name = '';
            bookingForm.customer_phone = '';
            bookingForm.start_time = '09:00';
            bookingForm.end_time = '10:00';
            slotAvailability.value = {
                checking: false,
                available: null,
                reason: null,
            };
            await loadAvailability();
        } else if (res.status === 409) {
            bookingErrors.value.end_time = 'That time slot is already taken — please choose another.';
        } else if (res.status === 202) {
            bookingErrors.value.end_time = 'No available slot for that time. Your request has been queued for staff assignment.';
        } else if (res.status === 422 && data && typeof data === 'object' && 'errors' in data) {
            const errors = (data as { errors: Record<string, string[]> }).errors;
            Object.entries(errors).forEach(([key, messages]) => {
                if (key in bookingErrors.value) {
                    bookingErrors.value[key as keyof BookingForm] = messages.join(' ');
                }
            });
        } else {
            const message = (data && typeof data === 'object' && 'message' in data)
                ? String(data.message)
                : 'Something went wrong. Please try again.';
            bookingErrors.value.customer_name = message;
        }
    } catch (err: unknown) {
        const message = err instanceof Error ? err.message : 'Something went wrong. Please try again.';
        bookingErrors.value.customer_name = message;
    } finally {
        bookingSubmitting.value = false;
    }
}

onMounted(() => {
    const today = new Date().toISOString().split('T')[0];
    bookingForm.booking_date = today;
    loadCourts();
    watch(
        () => [bookingForm.court_id, bookingForm.booking_date],
        () => {
            if (canLoadAvailability.value) {
                void loadAvailability();
            }
        },
    );

    watch(
        () => [bookingForm.court_id, bookingForm.booking_date, bookingForm.start_time, bookingForm.end_time],
        () => {
            if (canCheckSlotAvailability.value) {
                void checkSelectedSlotAvailability();
            } else {
                slotAvailability.value = {
                    checking: false,
                    available: null,
                    reason: null,
                };
            }
        },
    );
});
</script>

<template>
    <Head title="QCourts Pickleball - Coming Soon to Makilala">
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
    </Head>

    <div class="welcome-page">
        <nav class="site-nav">
            <div class="nav-shell">
                <div class="brand font-display">Q<span class="accent">Courts</span></div>
                <div class="nav-links font-mono">
                    <a href="#about">The Sport</a>
                    <a href="#courts">The Courts</a>
                    <a href="#location">Location</a>
                </div>
                <a href="#notify" class="nav-cta font-mono">Get Notified</a>
            </div>
        </nav>

        <header class="hero hero-lines">
            <div class="hero-shell">
                <div class="hero-copy fade-up">
                    <p class="eyebrow font-mono">Makilala, North Cotabato</p>
                    <h1 class="font-display hero-title">
                        Pickleball is landing on
                        <span class="underline-accent">Molave Street.</span>
                    </h1>
                    <p class="hero-text">
                        Fun, fitness, and friendly competition. The fastest-growing sport in the world is coming to your neighborhood.
                    </p>
                    <div class="hero-actions">
                        <a href="#notify" class="btn-primary font-mono">Notify Me at Opening</a>
                        <a href="#location" class="btn-outline font-mono">Find the Court</a>
                    </div>
                </div>

                <div class="hero-badge float font-display">
                    <span>COMING</span>
                    <span>SOON</span>
                </div>
            </div>

            <div class="hero-stats font-mono">
                <div><span class="stat-number font-display">1-2</span>Courts opening</div>
                <div><span class="stat-number font-display">Walk-in</span>+ online booking</div>
                <div><span class="stat-number font-display">All</span>Skill levels</div>
                <div><span class="stat-number font-display">Soon</span>Grand opening</div>
            </div>
        </header>

        <section id="about" class="section section-light">
            <div class="content-shell grid-two">
                <div>
                    <p class="eyebrow font-mono coral">New to Pickleball?</p>
                    <h2 class="font-display section-title">Tennis, badminton, and ping-pong walk into a court.</h2>
                    <p class="section-text">
                        Pickleball is played with solid paddles and a perforated plastic ball on a court about a third the size of a tennis court. It is easy to pick up in minutes and hard to put down after the first rally.
                    </p>
                    <p class="section-text">
                        It is social, low-impact, and competitive at every level, which is exactly why it has become one of the fastest-growing sports on the planet.
                    </p>
                </div>

                <div class="feature-list">
                    <article class="feature-card">
                        <p class="pill">Fun</p>
                        <p class="font-display feature-title">Easy to learn, fun from rally one.</p>
                    </article>
                    <article class="feature-card">
                        <p class="pill">Fitness</p>
                        <p class="font-display feature-title">A full-body workout that does not feel like one.</p>
                    </article>
                    <article class="feature-card">
                        <p class="pill">Community</p>
                        <p class="font-display feature-title">Friendly competition, right in Makilala.</p>
                    </article>
                </div>
            </div>
        </section>

        <section id="courts" class="section section-dark">
            <div class="content-shell">
                <p class="eyebrow font-mono yellow">The Courts</p>
                <h2 class="font-display section-title light">Two courts. Every skill level welcome.</h2>

                <div class="card-grid">
                    <article class="court-card court-lines">
                        <p class="eyebrow font-mono yellow">Walk-in Play</p>
                        <p class="font-display card-title light">Show up, grab a paddle.</p>
                        <p class="card-text">
                            Open court time for drop-in games. No reservation needed, first come first served.
                        </p>
                    </article>

                    <article class="court-card court-lines">
                        <p class="eyebrow font-mono yellow">Reserved Booking</p>
                        <p class="font-display card-title light">Lock in your court time.</p>
                        <p class="card-text">
                            Online booking is on the way. Reserve your slot ahead for practices, matches, or a group session.
                        </p>
                    </article>
                </div>
            </div>
        </section>

        <section id="book" class="section section-light">
            <div class="content-shell">
                <p class="eyebrow font-mono coral">Reserve a Court</p>
                <h2 class="font-display section-title">Book your slot online.</h2>
                <p class="section-text">
                    Pick a court and a time. Online requests start as <span class="font-semibold">pending</span> — we'll confirm shortly so the court is locked in. (Already booked times will be rejected automatically.)
                </p>

                <div class="grid-two book-layout">
                    <form class="book-form" @submit.prevent="handleBookingSubmit" novalidate>
                        <div class="book-field">
                            <label class="sr-only" for="book-court">Court</label>
                            <select
                                id="book-court"
                                v-model="bookingForm.court_id"
                                name="court_id"
                                required
                                class="book-input font-mono"
                                :aria-invalid="!!bookingErrors.court_id"
                                aria-describedby="book-court-error"
                            >
                                <option value="" disabled>Select a court</option>
                                <option v-for="court in courts" :key="court.id" :value="court.id">
                                    {{ court.name }}
                                </option>
                            </select>
                            <span v-if="bookingErrors.court_id" id="book-court-error" class="book-error" role="alert">{{ bookingErrors.court_id }}</span>
                        </div>

                        <div class="book-row">
                            <div class="book-field">
                                <label class="sr-only" for="book-date">Date</label>
                                <input
                                    id="book-date"
                                    v-model="bookingForm.booking_date"
                                    type="date"
                                    name="booking_date"
                                    required
                                    class="book-input font-mono"
                                    :aria-invalid="!!bookingErrors.booking_date"
                                    aria-describedby="book-date-error"
                                />
                                <span v-if="bookingErrors.booking_date" id="book-date-error" class="book-error" role="alert">{{ bookingErrors.booking_date }}</span>
                            </div>
                            <div class="book-field">
                                <label class="sr-only" for="book-start">Start</label>
                                <input
                                    id="book-start"
                                    v-model="bookingForm.start_time"
                                    type="time"
                                    name="start_time"
                                    required
                                    class="book-input font-mono"
                                    :aria-invalid="!!bookingErrors.start_time"
                                    aria-describedby="book-start-error"
                                />
                            </div>
                        </div>

                        <div class="book-field">
                            <label class="sr-only" for="book-end">End</label>
                            <input
                                id="book-end"
                                v-model="bookingForm.end_time"
                                type="time"
                                name="end_time"
                                required
                                class="book-input font-mono"
                                :aria-invalid="!!bookingErrors.end_time"
                                aria-describedby="book-end-error"
                            />
                            <span v-if="bookingErrors.end_time" id="book-end-error" class="book-error" role="alert">{{ bookingErrors.end_time }}</span>
                        </div>

                        <div class="book-field">
                            <label class="sr-only" for="book-name">Full name</label>
                            <input
                                id="book-name"
                                v-model="bookingForm.customer_name"
                                type="text"
                                name="customer_name"
                                required
                                placeholder="Juan Dela Cruz"
                                autocomplete="name"
                                class="book-input font-mono"
                                :aria-invalid="!!bookingErrors.customer_name"
                                aria-describedby="book-name-error"
                            />
                            <span v-if="bookingErrors.customer_name" id="book-name-error" class="book-error" role="alert">{{ bookingErrors.customer_name }}</span>
                        </div>

                        <div class="book-field">
                            <label class="sr-only" for="book-phone">Phone</label>
                            <input
                                id="book-phone"
                                v-model="bookingForm.customer_phone"
                                type="tel"
                                name="customer_phone"
                                required
                                placeholder="09171234567"
                                autocomplete="tel"
                                pattern="[0-9+\-\s()]{7,20}"
                                class="book-input font-mono"
                                :aria-invalid="!!bookingErrors.customer_phone"
                                aria-describedby="book-phone-error"
                            />
                            <span v-if="bookingErrors.customer_phone" id="book-phone-error" class="book-error" role="alert">{{ bookingErrors.customer_phone }}</span>
                        </div>

                        <button
                            type="submit"
                            class="btn-primary font-mono book-submit book-span-full"
                            :disabled="bookingSubmitting || slotAvailability.checking"
                        >
                            {{ bookingSubmitting ? 'Requesting…' : (slotAvailability.checking ? 'Checking availability…' : 'Request Booking') }}
                        </button>

                        <p class="book-status font-mono book-span-full" aria-live="polite">
                            <span v-if="bookingSuccess" class="book-success">Booking request received! We'll confirm it shortly.</span>
                        </p>

                        <p v-if="slotAvailability.available === true" class="book-status font-mono book-span-full">
                            <span class="book-success">Selected slot is available.</span>
                        </p>
                        <p v-else-if="slotAvailability.available === false" class="book-status font-mono book-span-full" role="status" aria-live="polite">
                            <span class="book-error">{{ slotReasonMessage }}</span>
                        </p>
                    </form>

                    <div class="book-availability court-lines">
                        <p class="eyebrow font-mono yellow">Reserved Times</p>
                        <p class="book-availability-hint font-mono">Pick a court and date to see what's already taken.</p>
                        <ul class="book-availability-list font-mono book-availability-list-light">
                            <li v-for="booking in availability" :key="booking.id" class="book-availability-item">
                                <span>{{ booking.start_time }}–{{ booking.end_time }}</span>
                                <span class="book-availability-status">{{ booking.status }}</span>
                            </li>
                            <li v-if="!availability.length && bookingForm.court_id && bookingForm.booking_date" class="book-availability-empty book-availability-empty-success">
                                No bookings yet for this date — wide open!
                            </li>
                            <li v-if="!availability.length && (!bookingForm.court_id || !bookingForm.booking_date)" class="book-availability-empty">
                                Select a court and date to see availability.
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <section id="location" class="section section-light">
            <div class="content-shell grid-two location-grid">
                <div>
                    <p class="eyebrow font-mono coral">Find Us</p>
                    <h2 class="font-display section-title">Molave Street,<br />Barangay Poblacion.</h2>
                    <p class="section-text">Beside Victory Christian Schoolhouse, right in front of RHU Makilala.</p>
                    <p class="location-note font-mono">Makilala, North Cotabato, Philippines</p>
                    <a
                        href="https://www.google.com/maps/search/?api=1&query=Molave+Street+Barangay+Poblacion+Makilala+North+Cotabato"
                        target="_blank"
                        rel="noopener"
                        class="btn-outline font-mono inline-btn"
                    >
                        Open in Maps
                    </a>
                </div>

                <div class="map-frame">
                    <iframe
                        title="QCourts Pickleball location map"
                        width="100%"
                        height="100%"
                        style="border:0; filter:grayscale(0.15);"
                        loading="lazy"
                        src="https://www.google.com/maps?q=Molave+Street+Barangay+Poblacion+Makilala+North+Cotabato&output=embed"
                    />
                </div>
            </div>
        </section>

        <section id="notify" class="hero hero-lines notify-section">
            <div class="content-shell notify-shell">
                <p class="eyebrow font-mono yellow">Be First on Court</p>
                <h2 class="font-display section-title light">Get the grand opening date first.</h2>
                <p class="notify-text">
                    Leave your name and number. We will message you the moment courts open for booking.
                </p>

                <form class="notify-form" @submit.prevent="handleNotifySubmit">
                    <div class="notify-field">
                        <label class="sr-only" for="notify-name">Full name</label>
                        <input
                            id="notify-name"
                            v-model="notifyForm.name"
                            type="text"
                            name="name"
                            placeholder="Full name"
                            autocomplete="name"
                            required
                            :aria-invalid="!!notifyErrors.name"
                            aria-describedby="notify-name-error"
                            class="notify-input font-mono"
                        />
                        <span v-if="notifyErrors.name" id="notify-name-error" class="notify-error" role="alert">{{ notifyErrors.name }}</span>
                    </div>

                    <div class="notify-field">
                        <label class="sr-only" for="notify-phone">Phone number</label>
                        <input
                            id="notify-phone"
                            v-model="notifyForm.phone"
                            type="tel"
                            name="phone"
                            placeholder="Phone number"
                            autocomplete="tel"
                            required
                            pattern="[0-9+\-\s()]{7,20}"
                            :aria-invalid="!!notifyErrors.phone"
                            aria-describedby="notify-phone-error"
                            class="notify-input font-mono"
                        />
                        <span v-if="notifyErrors.phone" id="notify-phone-error" class="notify-error" role="alert">{{ notifyErrors.phone }}</span>
                    </div>

                    <button
                        type="submit"
                        class="btn-primary font-mono notify-submit"
                        :disabled="notifySubmitting"
                    >
                        {{ notifySubmitting ? 'Sending...' : 'Notify Me' }}
                    </button>
                </form>

                <p v-if="notifySuccess" class="notify-note font-mono notify-success">We will let you know as soon as courts open!</p>
                <p v-else class="notify-note font-mono">This form is UI-only for now. It will connect to the booking backend later.</p>
            </div>
        </section>

        <footer class="site-footer">
            <div class="content-shell footer-shell">
                <div class="brand font-display">Q<span class="accent">Courts</span> Pickleball</div>
                <p class="footer-text font-mono">Molave St., Brgy. Poblacion, Makilala, North Cotabato</p>
            </div>
        </footer>
    </div>
</template>
