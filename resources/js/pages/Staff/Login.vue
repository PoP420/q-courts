<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    email: '',
    password: '',
    remember: true,
});

function submit() {
    form.post('/staff/login', {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Staff login" />

    <div class="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(34,211,238,0.2),_transparent_35%),linear-gradient(180deg,#020617_0%,#0f172a_100%)] px-6 py-12 text-slate-100">
        <div class="mx-auto flex min-h-[calc(100vh-6rem)] w-full max-w-md items-center">
            <div class="w-full rounded-3xl border border-white/10 bg-white/5 p-8 shadow-2xl shadow-cyan-950/30 backdrop-blur">
                <p class="text-xs uppercase tracking-[0.3em] text-cyan-300/80">QCourts staff</p>
                <h1 class="mt-3 text-3xl font-semibold text-white">Sign in to the dashboard</h1>
                <p class="mt-2 text-sm text-slate-300">Use a staff or owner account to manage bookings and court operations.</p>

                <form class="mt-8 space-y-5" @submit.prevent="submit">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-200" for="email">Email</label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            autocomplete="email"
                            class="w-full rounded-2xl border border-white/10 bg-slate-950/60 px-4 py-3 text-slate-100 outline-none transition placeholder:text-slate-500 focus:border-cyan-300/50"
                            placeholder="staff@qcourts.local"
                        />
                        <p v-if="form.errors.email" class="mt-2 text-sm text-rose-300">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-200" for="password">Password</label>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            autocomplete="current-password"
                            class="w-full rounded-2xl border border-white/10 bg-slate-950/60 px-4 py-3 text-slate-100 outline-none transition placeholder:text-slate-500 focus:border-cyan-300/50"
                            placeholder="••••••••"
                        />
                        <p v-if="form.errors.password" class="mt-2 text-sm text-rose-300">{{ form.errors.password }}</p>
                    </div>

                    <label class="flex items-center gap-3 text-sm text-slate-300">
                        <input v-model="form.remember" type="checkbox" class="h-4 w-4 rounded border-white/20 bg-slate-950/60 text-cyan-400 focus:ring-cyan-400" />
                        Remember me
                    </label>

                    <button
                        type="submit"
                        class="inline-flex w-full items-center justify-center rounded-2xl bg-cyan-400 px-4 py-3 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300 disabled:cursor-not-allowed disabled:opacity-70"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Signing in…' : 'Sign in' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>