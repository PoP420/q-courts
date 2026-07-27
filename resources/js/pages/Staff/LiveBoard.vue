<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import StaffLayout from '@/layouts/StaffLayout.vue';
import type { Auth } from '@/types/auth';

type Court = {
    id: number;
    name: string;
    is_active: boolean;
    active_session: {
        id: number;
        booking: string | null;
        game_type: string | null;
        planned_minutes: number;
        minutes_remaining: number;
        score: Record<string, unknown> | null;
    } | null;
};

type Session = {
    id: number;
    court: string | null;
    booking: string | null;
    game_type: string | null;
    planned_minutes: number;
    minutes_remaining: number;
    score: Record<string, unknown> | null;
    started_at: string | null;
};

defineProps<{
    auth: Auth;
    courts: Court[];
    activeSessions: Session[];
}>();

function refreshBoard() {
    router.reload({ only: ['courts', 'activeSessions'] });
}
</script>

<template>
    <Head title="Live court board" />

    <StaffLayout :auth="auth">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-white">Live court board</h2>
                <p class="text-sm text-slate-300">Mirrors the active-court state staff sees on mobile.</p>
            </div>

            <button type="button" class="rounded-full border border-cyan-400/30 px-4 py-2 text-sm font-semibold text-cyan-200 hover:bg-cyan-400/10" @click="refreshBoard">
                Refresh
            </button>
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
            <section class="rounded-3xl border border-white/10 bg-white/5 p-5">
                <h3 class="text-base font-semibold text-white">Active sessions</h3>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <article v-for="session in activeSessions" :key="session.id" class="rounded-2xl border border-cyan-400/20 bg-cyan-400/10 p-4">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-lg font-semibold text-white">{{ session.court }}</p>
                                <p class="text-sm text-cyan-100/80">{{ session.booking ?? 'Walk-in session' }}</p>
                            </div>
                            <span class="rounded-full bg-cyan-400/20 px-3 py-1 text-xs font-semibold text-cyan-100">
                                {{ session.minutes_remaining }} min left
                            </span>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-3 text-sm text-slate-200">
                            <div class="rounded-2xl border border-white/10 bg-slate-950/25 p-3">
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Game</p>
                                <p class="mt-1 font-medium">{{ session.game_type ?? 'Unknown' }}</p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-slate-950/25 p-3">
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Planned</p>
                                <p class="mt-1 font-medium">{{ session.planned_minutes }} min</p>
                            </div>
                        </div>

                        <pre v-if="session.score" class="mt-4 overflow-x-auto rounded-2xl border border-white/10 bg-slate-950/35 p-3 text-xs text-slate-200">{{ session.score }}</pre>
                    </article>

                    <p v-if="activeSessions.length === 0" class="rounded-2xl border border-dashed border-white/15 px-4 py-8 text-center text-sm text-slate-400 md:col-span-2">
                        No active sessions right now.
                    </p>
                </div>
            </section>

            <aside class="rounded-3xl border border-white/10 bg-white/5 p-5">
                <h3 class="text-base font-semibold text-white">Court status</h3>
                <div class="mt-4 space-y-3">
                    <article v-for="court in courts" :key="court.id" class="rounded-2xl border border-white/10 bg-slate-950/30 p-4">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="font-semibold text-white">{{ court.name }}</p>
                                <p class="text-sm text-slate-400">{{ court.is_active ? 'Active' : 'Inactive' }}</p>
                            </div>
                            <span v-if="court.active_session" class="rounded-full bg-emerald-400/15 px-3 py-1 text-xs font-semibold text-emerald-200">Occupied</span>
                            <span v-else class="rounded-full bg-slate-400/15 px-3 py-1 text-xs font-semibold text-slate-200">Available</span>
                        </div>

                        <p v-if="court.active_session" class="mt-3 text-sm text-slate-300">
                            {{ court.active_session.booking ?? 'Walk-in' }} · {{ court.active_session.minutes_remaining }} min remaining
                        </p>
                    </article>
                </div>
            </aside>
        </div>
    </StaffLayout>
</template>