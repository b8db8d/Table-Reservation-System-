<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';

defineOptions({
    layout: AppLayout,
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
});

defineProps<{
    reservations: Array<{
        id: number;
        reference_number: string;
        reservation_date: string;
        reservation_time: string;
        guest_count: number;
        status: string;
        cancel_url: string | null;
    }>;
}>();

const statusLabels: Record<string, string> = {
    pending: 'Pending',
    confirmed: 'Confirmed',
    rejected: 'Rejected',
    cancelled: 'Cancelled',
};

const statusClasses: Record<string, string> = {
    pending: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
    confirmed: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    rejected: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    cancelled: 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400',
};

function formatDate(date: string): string {
    return new Date(date).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}
</script>

<template>
    <Head title="My Reservations" />

    <div class="flex flex-col gap-6 p-6">
        <h1 class="text-xl font-semibold">My Reservations</h1>

        <div class="rounded-xl border border-sidebar-border/70 bg-card">
            <div v-if="reservations.length === 0" class="p-8 text-center text-sm text-muted-foreground">
                You have no reservations yet.
            </div>

            <table v-else class="w-full text-sm">
                <thead>
                    <tr class="border-b text-left text-muted-foreground">
                        <th class="px-4 py-3 font-medium">Reference</th>
                        <th class="px-4 py-3 font-medium">Date</th>
                        <th class="px-4 py-3 font-medium">Time</th>
                        <th class="px-4 py-3 font-medium">Guests</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="r in reservations"
                        :key="r.id"
                        class="border-b last:border-0 hover:bg-muted/40"
                    >
                        <td class="px-4 py-3 font-mono text-xs">{{ r.reference_number }}</td>
                        <td class="px-4 py-3">{{ formatDate(r.reservation_date) }}</td>
                        <td class="px-4 py-3">{{ r.reservation_time }}</td>
                        <td class="px-4 py-3">{{ r.guest_count }}</td>
                        <td class="px-4 py-3">
                            <span
                                class="rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="statusClasses[r.status]"
                            >
                                {{ statusLabels[r.status] }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <a
                                v-if="r.cancel_url"
                                :href="r.cancel_url"
                                class="text-xs text-destructive underline underline-offset-2 hover:opacity-75"
                            >
                                Cancel
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
