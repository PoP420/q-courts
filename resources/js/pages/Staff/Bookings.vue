<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import StaffLayout from '@/layouts/StaffLayout.vue';
import type { Auth } from '@/types/auth';

type Booking = {
    id: number;
    court_id: number;
    court_name: string | null;
    customer_name: string;
    customer_phone: string;
    booking_date: string | null;
    start_time: string;
    end_time: string;
    status: string;
    source: string;
    notes: string | null;
    queued_at: string | null;
    queue_notes: string | null;
};

type Court = {
    id: number;
    name: string;
    is_active: boolean;
};

const props = defineProps<{
    auth: Auth;
    bookings: Booking[];
    courts: Court[];
}>();

const selectedBookingId = ref<number | null>(props.bookings[0]?.id ?? null);

const rescheduleForm = useForm({
    court_id: props.bookings[0]?.court_id ?? props.courts[0]?.id ?? 1,
    booking_date: props.bookings[0]?.booking_date ?? '',
    start_time: props.bookings[0]?.start_time ?? '',
    end_time: props.bookings[0]?.end_time ?? '',
    queue_notes: props.bookings[0]?.queue_notes ?? '',
});

const availability = ref<{
    loading: boolean;
    available: boolean | null;
    reason: string | null;
}>({
    loading: false,
    available: null,
    reason: null,
});

const queuedCount = computed(() => props.bookings.filter((booking) => booking.queued_at).length);

const selectedBooking = computed(() => props.bookings.find((booking) => booking.id === selectedBookingId.value) ?? null);
const rescheduleErrors = rescheduleForm.errors as Record<string, string>;

const reasonLabel: Record<string, string> = {
    inactive: 'Court is inactive',
    occupied: 'Court is occupied by an active session',
    booked: 'Court already has a booking in that time window',
};

watch(selectedBooking, (booking) => {
    if (!booking) {
        return;
    }

    rescheduleForm.court_id = booking.court_id;
    rescheduleForm.booking_date = booking.booking_date ?? '';
    rescheduleForm.start_time = booking.start_time;
    rescheduleForm.end_time = booking.end_time;
    rescheduleForm.queue_notes = booking.queue_notes ?? '';
}, { immediate: true });

let availabilityCheckTimer: ReturnType<typeof setTimeout> | null = null;

watch(
    () => [rescheduleForm.court_id, rescheduleForm.booking_date, rescheduleForm.start_time, rescheduleForm.end_time],
    () => {
        if (availabilityCheckTimer) {
            clearTimeout(availabilityCheckTimer);
        }

        availabilityCheckTimer = setTimeout(() => {
            checkAvailability();
        }, 250);
    },
    { immediate: true },
);

function confirmBooking(bookingId: number) {
    router.patch(`/staff/bookings/${bookingId}/confirm`, {}, { preserveScroll: true });
}

function cancelBooking(bookingId: number) {
    router.patch(`/staff/bookings/${bookingId}/cancel`, {}, { preserveScroll: true });
}

function isQueued(booking: Booking | null): boolean {
    return !!booking?.queued_at;
}

function saveSchedule() {
    if (!selectedBookingId.value) {
        return;
    }

    const endpoint = isQueued(selectedBooking.value)
        ? `/staff/bookings/${selectedBookingId.value}/assign`
        : `/staff/bookings/${selectedBookingId.value}/reschedule`;

    rescheduleForm.patch(endpoint, {
        preserveScroll: true,
    });
}

async function checkAvailability() {
    if (!rescheduleForm.booking_date || !rescheduleForm.start_time || !rescheduleForm.end_time || !rescheduleForm.court_id) {
        availability.value = { loading: false, available: null, reason: null };
        return;
    }

    availability.value = { loading: true, available: null, reason: null };

    const params = new URLSearchParams({
        date: rescheduleForm.booking_date,
        start_time: rescheduleForm.start_time,
        end_time: rescheduleForm.end_time,
    });

    try {
        const response = await fetch(`/api/courts/availability?${params.toString()}`);
        if (!response.ok) {
            availability.value = { loading: false, available: null, reason: null };
            return;
        }

        const payload = (await response.json()) as Array<{ id: number; available: boolean; reason: string | null }>;
        const selected = payload.find((court) => court.id === rescheduleForm.court_id) ?? null;

        availability.value = {
            loading: false,
            available: selected?.available ?? null,
            reason: selected?.reason ?? null,
        };
    } catch {
        availability.value = { loading: false, available: null, reason: null };
    }
}

const statusClasses: Record<string, string> = {
    pending: 'bg-amber-400/15 text-amber-200 ring-1 ring-amber-400/30',
    confirmed: 'bg-emerald-400/15 text-emerald-200 ring-1 ring-emerald-400/30',
    cancelled: 'bg-rose-400/15 text-rose-200 ring-1 ring-rose-400/30',
    completed: 'bg-slate-400/15 text-slate-200 ring-1 ring-slate-400/30',
};
</script>

<template>
    <Head title="Bookings" />

    <StaffLayout :auth="auth">
        <div class="grid gap-6 xl:grid-cols-[1.6fr_0.9fr]">
            <section class="rounded-3xl border border-white/10 bg-white/5 p-5">
                <div>
                    <h2 class="text-lg font-semibold text-white">Bookings list</h2>
                    <p class="text-sm text-slate-300">Confirm, cancel, or reschedule reservations from one place.</p>
                    <p class="mt-2 inline-flex items-center rounded-full border border-amber-400/30 bg-amber-400/10 px-3 py-1 text-xs font-semibold text-amber-200">
                        Queue: {{ queuedCount }} booking{{ queuedCount === 1 ? '' : 's' }} waiting
                    </p>
                </div>

                <div class="mt-4 overflow-hidden rounded-2xl border border-white/10">
                    <table class="min-w-full divide-y divide-white/10 text-left text-sm">
                        <thead class="bg-white/5 text-slate-300">
                            <tr>
                                <th class="px-4 py-3 font-medium">Customer</th>
                                <th class="px-4 py-3 font-medium">Court</th>
                                <th class="px-4 py-3 font-medium">Schedule</th>
                                <th class="px-4 py-3 font-medium">Status</th>
                                <th class="px-4 py-3 font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10 bg-slate-950/30">
                            <tr v-for="booking in bookings" :key="booking.id" :class="selectedBookingId === booking.id ? 'bg-cyan-400/5' : ''">
                                <td class="px-4 py-4 text-white">
                                    <div class="font-medium">{{ booking.customer_name }}</div>
                                    <div class="text-xs text-slate-400">{{ booking.customer_phone }}</div>
                                    <div class="text-xs text-slate-400">{{ booking.source }}</div>
                                </td>
                                <td class="px-4 py-4 text-slate-200">{{ booking.court_name ?? 'Unassigned' }}</td>
                                <td class="px-4 py-4 text-slate-200">{{ booking.booking_date }} · {{ booking.start_time }} - {{ booking.end_time }}</td>
                                <td class="px-4 py-4">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold capitalize" :class="statusClasses[booking.status] ?? statusClasses.completed">
                                        {{ booking.status }}
                                    </span>
                                    <p v-if="booking.queued_at" class="mt-2 text-xs font-semibold uppercase tracking-wide text-amber-200">Queued</p>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex flex-wrap gap-2">
                                        <button type="button" class="rounded-full border border-emerald-400/30 px-3 py-1.5 text-xs font-semibold text-emerald-200 hover:bg-emerald-400/10" @click="confirmBooking(booking.id)">
                                            Confirm
                                        </button>
                                        <button type="button" class="rounded-full border border-rose-400/30 px-3 py-1.5 text-xs font-semibold text-rose-200 hover:bg-rose-400/10" @click="cancelBooking(booking.id)">
                                            Cancel
                                        </button>
                                        <button type="button" class="rounded-full border border-cyan-400/30 px-3 py-1.5 text-xs font-semibold text-cyan-200 hover:bg-cyan-400/10" @click="selectedBookingId = booking.id">
                                            Reschedule
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="bookings.length === 0">
                                <td colspan="5" class="px-4 py-8 text-center text-slate-400">No bookings yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <aside class="space-y-6">
                <section class="rounded-3xl border border-white/10 bg-white/5 p-5">
                    <h2 class="text-lg font-semibold text-white">Reschedule booking</h2>
                    <p class="text-sm text-slate-300">Pick a booking, adjust its slot, then save or assign to court.</p>

                    <div class="mt-4 space-y-4">
                        <label class="block text-sm text-slate-200">
                            Booking
                            <select v-model="selectedBookingId" class="mt-2 w-full rounded-2xl border border-white/10 bg-slate-950/60 px-4 py-3 text-slate-100">
                                <option v-for="booking in bookings" :key="booking.id" :value="booking.id">
                                    {{ booking.customer_name }} · {{ booking.booking_date }}
                                </option>
                            </select>
                        </label>

                        <label class="block text-sm text-slate-200">
                            Court
                            <select v-model.number="rescheduleForm.court_id" class="mt-2 w-full rounded-2xl border border-white/10 bg-slate-950/60 px-4 py-3 text-slate-100">
                                <option v-for="court in courts" :key="court.id" :value="court.id">
                                    {{ court.name }}
                                </option>
                            </select>
                        </label>

                        <label class="block text-sm text-slate-200">
                            Date
                            <input v-model="rescheduleForm.booking_date" type="date" class="mt-2 w-full rounded-2xl border border-white/10 bg-slate-950/60 px-4 py-3 text-slate-100" />
                        </label>

                        <div class="grid grid-cols-2 gap-3">
                            <label class="block text-sm text-slate-200">
                                Start
                                <input v-model="rescheduleForm.start_time" type="time" class="mt-2 w-full rounded-2xl border border-white/10 bg-slate-950/60 px-4 py-3 text-slate-100" />
                            </label>
                            <label class="block text-sm text-slate-200">
                                End
                                <input v-model="rescheduleForm.end_time" type="time" class="mt-2 w-full rounded-2xl border border-white/10 bg-slate-950/60 px-4 py-3 text-slate-100" />
                            </label>
                        </div>

                        <p v-if="rescheduleErrors.slot" class="text-sm text-rose-300">{{ rescheduleErrors.slot }}</p>

                        <div v-if="availability.loading" class="rounded-2xl border border-white/10 bg-slate-950/30 px-4 py-3 text-xs text-slate-300">
                            Checking availability...
                        </div>
                        <div v-else-if="availability.available === true" class="rounded-2xl border border-emerald-400/30 bg-emerald-400/10 px-4 py-3 text-xs font-semibold text-emerald-200">
                            Slot is available for this court.
                        </div>
                        <div v-else-if="availability.available === false" class="rounded-2xl border border-rose-400/30 bg-rose-400/10 px-4 py-3 text-xs font-semibold text-rose-200">
                            Slot unavailable: {{ reasonLabel[availability.reason ?? 'booked'] ?? 'Court is unavailable' }}
                        </div>

                        <label v-if="selectedBooking?.queued_at" class="block text-sm text-slate-200">
                            Queue notes
                            <textarea v-model="rescheduleForm.queue_notes" rows="2" class="mt-2 w-full rounded-2xl border border-white/10 bg-slate-950/60 px-4 py-3 text-slate-100" placeholder="Optional notes for staff assignment"></textarea>
                        </label>

                        <button type="button" class="inline-flex w-full items-center justify-center rounded-2xl bg-cyan-400 px-4 py-3 text-sm font-semibold text-slate-950 hover:bg-cyan-300 disabled:cursor-not-allowed disabled:opacity-70" :disabled="rescheduleForm.processing || !selectedBookingId" @click="saveSchedule">
                            {{ rescheduleForm.processing ? 'Saving…' : (selectedBooking?.queued_at ? 'Assign to court' : 'Save reschedule') }}
                        </button>
                    </div>
                </section>

                <section v-if="selectedBooking" class="rounded-3xl border border-white/10 bg-white/5 p-5">
                    <h2 class="text-lg font-semibold text-white">Selected booking</h2>
                    <p class="mt-3 text-sm text-slate-300">{{ selectedBooking.customer_name }}</p>
                    <p class="text-sm text-slate-400">{{ selectedBooking.court_name ?? 'Unassigned' }} · {{ selectedBooking.status }}</p>
                    <p v-if="selectedBooking.queued_at" class="mt-1 text-xs font-semibold uppercase tracking-wide text-amber-200">Queued at {{ selectedBooking.queued_at }}</p>
                    <p class="mt-2 text-sm text-slate-300">{{ selectedBooking.booking_date }} · {{ selectedBooking.start_time }} - {{ selectedBooking.end_time }}</p>
                    <p v-if="selectedBooking.notes" class="mt-3 rounded-2xl border border-white/10 bg-slate-950/30 p-3 text-sm text-slate-300">{{ selectedBooking.notes }}</p>
                    <p v-if="selectedBooking.queue_notes" class="mt-3 rounded-2xl border border-amber-400/20 bg-amber-400/10 p-3 text-sm text-amber-100">{{ selectedBooking.queue_notes }}</p>
                </section>
            </aside>
        </div>
    </StaffLayout>
</template>