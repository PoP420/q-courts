<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import type { Auth } from '@/types/auth';

defineProps<{
    auth: Auth;
}>();

function logout() {
    router.post('/staff/logout');
}
</script>

<template>
    <div class="min-h-screen bg-[#0f172a] text-slate-100">
        <div class="mx-auto flex min-h-screen w-full max-w-7xl flex-col px-6 py-6 lg:px-8">
            <header class="flex flex-col gap-4 rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-xs uppercase tracking-[0.3em] text-cyan-300/80">QCourts staff</p>
                    <h1 class="mt-1 text-2xl font-semibold text-white">Operations dashboard</h1>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <Link href="/staff" class="rounded-full border border-white/10 px-4 py-2 text-sm text-slate-100 transition hover:border-cyan-300/40 hover:text-cyan-200">
                        Dashboard
                    </Link>
                    <Link href="/staff/bookings" class="rounded-full border border-white/10 px-4 py-2 text-sm text-slate-100 transition hover:border-cyan-300/40 hover:text-cyan-200">
                        Bookings
                    </Link>
                    <Link href="/staff/courts" class="rounded-full border border-white/10 px-4 py-2 text-sm text-slate-100 transition hover:border-cyan-300/40 hover:text-cyan-200">
                        Courts
                    </Link>
                    <Link href="/staff/sessions" class="rounded-full border border-white/10 px-4 py-2 text-sm text-slate-100 transition hover:border-cyan-300/40 hover:text-cyan-200">
                        Sessions
                    </Link>
                    <Link href="/staff/board" class="rounded-full border border-white/10 px-4 py-2 text-sm text-slate-100 transition hover:border-cyan-300/40 hover:text-cyan-200">
                        Live board
                    </Link>
                    <button
                        type="button"
                        class="rounded-full bg-cyan-400 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300"
                        @click="logout"
                    >
                        Sign out
                    </button>
                </div>
            </header>

            <main class="flex-1 py-6">
                <slot />
            </main>
        </div>
    </div>
</template>