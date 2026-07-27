<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import StaffLayout from '@/layouts/StaffLayout.vue';
import type { Auth } from '@/types/auth';

defineProps<{
    auth: Auth;
    stats: {
        courts: number;
        active_sessions: number;
        pending_bookings: number;
        confirmed_bookings: number;
    };
    recentBookings: Array<{
        id: number;
        court: string | null;
        customer_name: string;
        booking_date: string | null;
        start_time: string;
        end_time: string;
        status: string;
        source: string;
    }>;
    courts: Array<{
        id: number;
        name: string;
        is_active: boolean;
        active_session: {
            id: number;
            game_type: string | null;
            planned_minutes: number;
            minutes_remaining: number;
            status: string;
            booking: string | null;
        } | null;
    }>;
    activeSessions: Array<{
        id: number;
        court: string | null;
        booking: string | null;
        game_type: string | null;
        planned_minutes: number;
        minutes_remaining: number;
        score: Record<string, unknown> | null;
    }>;
}>();

const statusClasses: Record<string, string> = {
    pending: 'bg-amber-400/15 text-amber-200 ring-1 ring-amber-400/30',
    confirmed: 'bg-emerald-400/15 text-emerald-200 ring-1 ring-emerald-400/30',
    cancelled: 'bg-rose-400/15 text-rose-200 ring-1 ring-rose-400/30',
    completed: 'bg-slate-400/15 text-slate-200 ring-1 ring-slate-400/30',
};
</script>

<template>
    <Head title="Staff dashboard" />

    <StaffLayout :auth="auth">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-3xl border border-white/10 bg-white/5 p-5">
                <p class="text-sm text-slate-300">Courts</p>
                <p class="mt-2 text-3xl font-semibold text-white">{{ stats.courts }}</p>
            </div>
            <div class="rounded-3xl border border-white/10 bg-white/5 p-5">
                <p class="text-sm text-slate-300">Active sessions</p>
                <p class="mt-2 text-3xl font-semibold text-white">{{ stats.active_sessions }}</p>
            </div>
            <div class="rounded-3xl border border-white/10 bg-white/5 p-5">
                <p class="text-sm text-slate-300">Pending bookings</p>
                <p class="mt-2 text-3xl font-semibold text-white">{{ stats.pending_bookings }}</p>
            </div>
            <div class="rounded-3xl border border-white/10 bg-white/5 p-5">
                <p class="text-sm text-slate-300">Confirmed bookings</p>
                <p class="mt-2 text-3xl font-semibold text-white">{{ stats.confirmed_bookings }}</p>
            </div>
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-[1.4fr_0.9fr]">
            <section class="rounded-3xl border border-white/10 bg-white/5 p-5">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-white">Recent bookings</h2>
                        <p class="text-sm text-slate-300">Latest reservations flowing through the system.</p>
                    </div>
                </div>

                <div class="mt-4 overflow-hidden rounded-2xl border border-white/10">
                    <table class="min-w-full divide-y divide-white/10 text-left text-sm">
                        <thead class="bg-white/5 text-slate-300">
                            <tr>
                                <th class="px-4 py-3 font-medium">Booking</th>
                                <th class="px-4 py-3 font-medium">Court</th>
                                <th class="px-4 py-3 font-medium">Schedule</th>
                                <th class="px-4 py-3 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10 bg-slate-950/30">
                            <tr v-for="booking in recentBookings" :key="booking.id">
                                <td class="px-4 py-3 text-white">
                                    <div class="font-medium">{{ booking.customer_name }}</div>
                                    <div class="text-xs text-slate-400">{{ booking.source }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-200">{{ booking.court ?? 'Unassigned' }}</td>
                                <td class="px-4 py-3 text-slate-200">
                                    {{ booking.booking_date }} · {{ booking.start_time }} - {{ booking.end_time }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold capitalize" :class="statusClasses[booking.status] ?? statusClasses.completed">
                                        {{ booking.status }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="recentBookings.length === 0">
                                <td colspan="4" class="px-4 py-8 text-center text-slate-400">No bookings yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="space-y-6">
                <div class="rounded-3xl border border-white/10 bg-white/5 p-5">
                    <h2 class="text-lg font-semibold text-white">Live court board</h2>
                    <div class="mt-4 space-y-3">
                        <article v-for="session in activeSessions" :key="session.id" class="rounded-2xl border border-cyan-400/20 bg-cyan-400/10 p-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="font-semibold text-white">{{ session.court }}</p>
                                    <p class="text-sm text-cyan-100/80">{{ session.booking ?? 'Walk-in session' }}</p>
                                </div>
                                <span class="rounded-full bg-cyan-400/20 px-3 py-1 text-xs font-semibold text-cyan-100">
                                    {{ session.minutes_remaining }} min left
                                </span>
                            </div>
                            <p class="mt-3 text-sm text-slate-200">{{ session.game_type ?? 'Game in progress' }}</p>
                        </article>
                        <p v-if="activeSessions.length === 0" class="rounded-2xl border border-dashed border-white/15 px-4 py-8 text-center text-sm text-slate-400">
                            No active sessions right now.
                        </p>
                    </div>
                </div>

                <div class="rounded-3xl border border-white/10 bg-white/5 p-5">
                    <h2 class="text-lg font-semibold text-white">Courts</h2>
                    <div class="mt-4 space-y-3">
                        <article v-for="court in courts" :key="court.id" class="rounded-2xl border border-white/10 bg-slate-950/30 p-4">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="font-semibold text-white">{{ court.name }}</p>
                                    <p class="text-sm text-slate-400">{{ court.is_active ? 'Active' : 'Inactive' }}</p>
                                </div>
                                <span v-if="court.active_session" class="rounded-full bg-emerald-400/15 px-3 py-1 text-xs font-semibold text-emerald-200">
                                    Occupied
                                </span>
                                <span v-else class="rounded-full bg-slate-400/15 px-3 py-1 text-xs font-semibold text-slate-200">
                                    Available
                                </span>
                            </div>
                            <p v-if="court.active_session" class="mt-3 text-sm text-slate-300">
                                {{ court.active_session.booking ?? 'Walk-in' }} · {{ court.active_session.minutes_remaining }} min remaining
                            </p>
                        </article>
                    </div>
                </div>
            </section>
        </div>
    </StaffLayout>
</template>