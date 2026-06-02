<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import { dashboard } from '@/routes/admin';

defineOptions({
    layout: AdminLayout,
});

defineProps<{
    pendingCount: number;
    todayReservations: Array<{
        id: number;
        reference_number: string;
        first_name: string;
        last_name: string;
        guest_count: number;
        reservation_time: string;
    }>;
    tomorrowReservations: Array<{
        id: number;
        reference_number: string;
        first_name: string;
        last_name: string;
        guest_count: number;
        reservation_time: string;
    }>;
}>();
</script>

<template>
    <Head title="Admin Dashboard" />

    <div class="flex flex-col gap-6 p-6">
        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-xl border border-sidebar-border/70 bg-card p-6">
                <p class="text-sm text-muted-foreground">Pending Reservations</p>
                <p class="mt-1 text-3xl font-bold">{{ pendingCount }}</p>
            </div>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <div class="rounded-xl border border-sidebar-border/70 bg-card p-6">
                <h2 class="mb-4 font-semibold">Today's Reservations</h2>
                <div v-if="todayReservations.length === 0" class="text-sm text-muted-foreground">
                    No confirmed reservations today.
                </div>
                <table v-else class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-muted-foreground">
                            <th class="pb-2 font-medium">Time</th>
                            <th class="pb-2 font-medium">Guest</th>
                            <th class="pb-2 font-medium">Ref</th>
                            <th class="pb-2 text-right font-medium">Guests</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="r in todayReservations" :key="r.id" class="border-b last:border-0">
                            <td class="py-2">{{ r.reservation_time }}</td>
                            <td class="py-2">{{ r.first_name }} {{ r.last_name }}</td>
                            <td class="py-2 font-mono text-xs">{{ r.reference_number }}</td>
                            <td class="py-2 text-right">{{ r.guest_count }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="rounded-xl border border-sidebar-border/70 bg-card p-6">
                <h2 class="mb-4 font-semibold">Tomorrow's Reservations</h2>
                <div v-if="tomorrowReservations.length === 0" class="text-sm text-muted-foreground">
                    No confirmed reservations tomorrow.
                </div>
                <table v-else class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-muted-foreground">
                            <th class="pb-2 font-medium">Time</th>
                            <th class="pb-2 font-medium">Guest</th>
                            <th class="pb-2 font-medium">Ref</th>
                            <th class="pb-2 text-right font-medium">Guests</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="r in tomorrowReservations" :key="r.id" class="border-b last:border-0">
                            <td class="py-2">{{ r.reservation_time }}</td>
                            <td class="py-2">{{ r.first_name }} {{ r.last_name }}</td>
                            <td class="py-2 font-mono text-xs">{{ r.reference_number }}</td>
                            <td class="py-2 text-right">{{ r.guest_count }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
