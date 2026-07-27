<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { reactive } from 'vue';
import StaffLayout from '@/layouts/StaffLayout.vue';
import type { Auth } from '@/types/auth';

type Court = {
    id: number;
    name: string;
    is_active: boolean;
    bookings_count: number;
    sessions_count: number;
    active_session: {
        booking: string | null;
        minutes_remaining: number;
    } | null;
};

const props = defineProps<{
    auth: Auth;
    courts: Court[];
}>();

const createForm = useForm({
    name: '',
    is_active: true,
});

const editForms = reactive<Record<number, ReturnType<typeof useForm>>>({});

props.courts.forEach((court) => {
    editForms[court.id] = useForm({
        name: court.name,
        is_active: court.is_active,
    });
});

function createCourt() {
    createForm.post('/staff/courts', { preserveScroll: true });
}

function saveCourt(courtId: number) {
    editForms[courtId].patch(`/staff/courts/${courtId}`, { preserveScroll: true });
}
</script>

<template>
    <Head title="Courts" />

    <StaffLayout :auth="auth">
        <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
            <section class="rounded-3xl border border-white/10 bg-white/5 p-5">
                <h2 class="text-lg font-semibold text-white">Courts management</h2>
                <p class="text-sm text-slate-300">Create courts, rename them, and toggle whether they are active.</p>

                <div class="mt-4 space-y-4">
                    <div v-for="court in courts" :key="court.id" class="rounded-2xl border border-white/10 bg-slate-950/30 p-4">
                        <div class="grid gap-3 md:grid-cols-[1fr_auto_auto] md:items-end">
                            <label class="block text-sm text-slate-200">
                                Court name
                                <input v-model="editForms[court.id].name" type="text" class="mt-2 w-full rounded-2xl border border-white/10 bg-slate-950/60 px-4 py-3 text-slate-100" />
                            </label>
                            <label class="flex items-center gap-3 rounded-2xl border border-white/10 px-4 py-3 text-sm text-slate-200">
                                <input v-model="editForms[court.id].is_active" type="checkbox" class="h-4 w-4 rounded border-white/20 bg-slate-950/60 text-cyan-400" />
                                Active
                            </label>
                            <button type="button" class="rounded-2xl bg-cyan-400 px-4 py-3 text-sm font-semibold text-slate-950 hover:bg-cyan-300 disabled:opacity-70" :disabled="editForms[court.id].processing" @click="saveCourt(court.id)">
                                {{ editForms[court.id].processing ? 'Saving…' : 'Save' }}
                            </button>
                        </div>

                        <div class="mt-3 flex flex-wrap gap-2 text-xs text-slate-300">
                            <span class="rounded-full border border-white/10 px-3 py-1">{{ court.bookings_count }} bookings</span>
                            <span class="rounded-full border border-white/10 px-3 py-1">{{ court.sessions_count }} sessions</span>
                            <span v-if="court.active_session" class="rounded-full border border-cyan-400/30 px-3 py-1 text-cyan-200">
                                Live: {{ court.active_session.booking ?? 'Walk-in' }} · {{ court.active_session.minutes_remaining }} min
                            </span>
                        </div>
                    </div>
                </div>
            </section>

            <aside class="space-y-6">
                <section class="rounded-3xl border border-white/10 bg-white/5 p-5">
                    <h2 class="text-lg font-semibold text-white">Add court</h2>
                    <div class="mt-4 space-y-4">
                        <label class="block text-sm text-slate-200">
                            Name
                            <input v-model="createForm.name" type="text" class="mt-2 w-full rounded-2xl border border-white/10 bg-slate-950/60 px-4 py-3 text-slate-100" placeholder="Court 3" />
                        </label>
                        <label class="flex items-center gap-3 rounded-2xl border border-white/10 px-4 py-3 text-sm text-slate-200">
                            <input v-model="createForm.is_active" type="checkbox" class="h-4 w-4 rounded border-white/20 bg-slate-950/60 text-cyan-400" />
                            Active on creation
                        </label>
                        <button type="button" class="inline-flex w-full items-center justify-center rounded-2xl bg-cyan-400 px-4 py-3 text-sm font-semibold text-slate-950 hover:bg-cyan-300 disabled:opacity-70" :disabled="createForm.processing" @click="createCourt">
                            {{ createForm.processing ? 'Creating…' : 'Create court' }}
                        </button>
                    </div>
                </section>
            </aside>
        </div>
    </StaffLayout>
</template>