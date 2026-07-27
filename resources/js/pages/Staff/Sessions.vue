<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import StaffLayout from '@/layouts/StaffLayout.vue';
import type { Auth } from '@/types/auth';

type Court = {
    id: number;
    name: string;
    is_active: boolean;
    active_session: {
        booking: string | null;
        game_type: string | null;
        minutes_remaining: number;
    } | null;
};

type ActiveSession = {
    id: number;
    court: string | null;
    booking: string | null;
    game_type: string | null;
    planned_minutes: number;
    minutes_remaining: number;
    started_at: string | null;
};

const props = defineProps<{
    auth: Auth;
    courts: Court[];
    activeSessions: ActiveSession[];
}>();

const initialCourtId = props.courts.find((court) => !court.active_session)?.id ?? props.courts[0]?.id ?? 1;

const form = useForm({
    court_id: initialCourtId,
    game_type: 'Walk-in',
    planned_minutes: 30,
});

const notice = ref('');

const selectedCourt = computed(() => props.courts.find((court) => court.id === form.court_id) ?? null);

function startSession() {
    notice.value = '';

    form.post('/staff/sessions', {
        preserveScroll: true,
        onSuccess: () => {
            notice.value = 'Walk-in session started.';
            form.game_type = 'Walk-in';
            form.planned_minutes = 30;
        },
    });
}
</script>

<template>
    <Head title="Sessions" />

    <StaffLayout :auth="auth">
        <div class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
            <section class="rounded-3xl border border-white/10 bg-white/5 p-5">
                <h2 class="text-lg font-semibold text-white">Start walk-in session</h2>
                <p class="text-sm text-slate-300">Create a live session for a customer who arrived without a booking.</p>

                <div class="mt-4 space-y-4">
                    <label class="block text-sm text-slate-200">
                        Court
                        <select v-model.number="form.court_id" class="mt-2 w-full rounded-2xl border border-white/10 bg-slate-950/60 px-4 py-3 text-slate-100">
                            <option v-for="court in courts" :key="court.id" :value="court.id" :disabled="!!court.active_session">
                                {{ court.name }}{{ court.active_session ? ' · active' : '' }}
                            </option>
                        </select>
                    </label>

                    <label class="block text-sm text-slate-200">
                        Game type
                        <input v-model="form.game_type" type="text" class="mt-2 w-full rounded-2xl border border-white/10 bg-slate-950/60 px-4 py-3 text-slate-100" placeholder="Walk-in / Singles / Doubles" />
                    </label>

                    <label class="block text-sm text-slate-200">
                        Planned minutes
                        <input v-model.number="form.planned_minutes" type="number" min="5" max="240" step="5" class="mt-2 w-full rounded-2xl border border-white/10 bg-slate-950/60 px-4 py-3 text-slate-100" />
                    </label>

                    <p v-if="form.errors.court_id" class="text-sm text-rose-300">{{ form.errors.court_id }}</p>
                    <p v-if="form.errors.game_type" class="text-sm text-rose-300">{{ form.errors.game_type }}</p>
                    <p v-if="form.errors.planned_minutes" class="text-sm text-rose-300">{{ form.errors.planned_minutes }}</p>

                    <button type="button" class="inline-flex w-full items-center justify-center rounded-2xl bg-cyan-400 px-4 py-3 text-sm font-semibold text-slate-950 hover:bg-cyan-300 disabled:cursor-not-allowed disabled:opacity-70" :disabled="form.processing" @click="startSession">
                        {{ form.processing ? 'Starting…' : 'Start session' }}
                    </button>

                    <p v-if="notice" class="rounded-2xl border border-emerald-400/20 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-200">{{ notice }}</p>

                    <div v-if="selectedCourt" class="rounded-2xl border border-white/10 bg-slate-950/30 p-4 text-sm text-slate-300">
                        <p class="font-semibold text-white">Selected court</p>
                        <p class="mt-1">{{ selectedCourt.name }}</p>
                        <p v-if="selectedCourt.active_session" class="mt-2 text-cyan-200">
                            Already live: {{ selectedCourt.active_session.game_type ?? 'Walk-in' }} · {{ selectedCourt.active_session.minutes_remaining }} min remaining
                        </p>
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border border-white/10 bg-white/5 p-5">
                <h2 class="text-lg font-semibold text-white">Active sessions</h2>
                <p class="text-sm text-slate-300">Current walk-ins and booking-backed sessions on court right now.</p>

                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    <article v-for="session in activeSessions" :key="session.id" class="rounded-2xl border border-white/10 bg-slate-950/30 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-white">{{ session.court ?? 'Unknown court' }}</p>
                                <p class="text-xs text-slate-400">Started {{ session.started_at ?? 'recently' }}</p>
                            </div>
                            <span class="rounded-full border border-cyan-400/30 px-3 py-1 text-xs font-semibold text-cyan-200">
                                {{ session.minutes_remaining }} min left
                            </span>
                        </div>

                        <dl class="mt-4 grid gap-2 text-sm text-slate-300">
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-400">Customer</dt>
                                <dd>{{ session.booking ?? 'Walk-in' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-400">Game type</dt>
                                <dd>{{ session.game_type ?? 'Walk-in' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-400">Planned</dt>
                                <dd>{{ session.planned_minutes }} min</dd>
                            </div>
                        </dl>
                    </article>

                    <p v-if="activeSessions.length === 0" class="rounded-2xl border border-dashed border-white/10 bg-slate-950/20 p-6 text-sm text-slate-400 md:col-span-2">
                        No active sessions yet.
                    </p>
                </div>
            </section>
        </div>
    </StaffLayout>
</template>