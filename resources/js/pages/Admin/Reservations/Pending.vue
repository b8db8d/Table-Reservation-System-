<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { confirm as confirmRoute, reject as rejectRoute } from '@/routes/admin/reservations';
import { ref } from 'vue';

defineOptions({
    layout: AdminLayout,
});

defineProps<{
    reservations: Array<{
        id: number;
        reference_number: string;
        first_name: string;
        last_name: string;
        email: string;
        phone: string;
        guest_count: number;
        reservation_date: string;
        reservation_time: string;
        notes: string | null;
        created_at: string;
    }>;
}>();

function formatDate(date: string): string {
    return new Date(date).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

function formatTime(time: string): string {
    return time.slice(0, 5);
}

const rejectingId = ref<number | null>(null);
const rejectionReason = ref('');

function confirmReservation(id: number): void {
    router.patch(confirmRoute.url(id), {}, {
        preserveScroll: true,
    });
}

function startRejecting(id: number): void {
    rejectingId.value = id;
    rejectionReason.value = '';
}

function cancelRejecting(): void {
    rejectingId.value = null;
    rejectionReason.value = '';
}

function submitRejection(id: number): void {
    router.patch(rejectRoute.url(id), { rejection_reason: rejectionReason.value }, {
        preserveScroll: true,
        onSuccess: () => cancelRejecting(),
    });
}
</script>

<template>
    <Head title="Pending Reservations" />

    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold">Pending Reservations</h1>
            <span class="rounded-full bg-amber-100 px-3 py-1 text-sm font-medium text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">
                {{ reservations.length }} pending
            </span>
        </div>

        <div class="rounded-xl border border-sidebar-border/70 bg-card">
            <div v-if="reservations.length === 0" class="p-8 text-center text-sm text-muted-foreground">
                No pending reservations.
            </div>

            <table v-else class="w-full text-sm">
                <thead>
                    <tr class="border-b text-left text-muted-foreground">
                        <th class="px-4 py-3 font-medium">Reference</th>
                        <th class="px-4 py-3 font-medium">Guest</th>
                        <th class="px-4 py-3 font-medium">Date & Time</th>
                        <th class="px-4 py-3 font-medium">Guests</th>
                        <th class="px-4 py-3 font-medium">Contact</th>
                        <th class="px-4 py-3 font-medium">Submitted</th>
                        <th class="px-4 py-3 font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="reservation in reservations"
                        :key="reservation.id"
                        class="border-b last:border-0 hover:bg-muted/40"
                    >
                        <td class="px-4 py-3 font-mono text-xs">{{ reservation.reference_number }}</td>
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ reservation.first_name }} {{ reservation.last_name }}</div>
                            <div v-if="reservation.notes" class="mt-0.5 max-w-48 truncate text-xs text-muted-foreground" :title="reservation.notes">
                                {{ reservation.notes }}
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div>{{ formatDate(reservation.reservation_date) }}</div>
                            <div class="text-xs text-muted-foreground">{{ formatTime(reservation.reservation_time) }}</div>
                        </td>
                        <td class="px-4 py-3">{{ reservation.guest_count }}</td>
                        <td class="px-4 py-3">
                            <div>{{ reservation.email }}</div>
                            <div class="text-xs text-muted-foreground">{{ reservation.phone }}</div>
                        </td>
                        <td class="px-4 py-3 text-xs text-muted-foreground">{{ formatDate(reservation.created_at) }}</td>
                        <td class="px-4 py-3">
                            <div v-if="rejectingId === reservation.id" class="flex flex-col gap-2">
                                <textarea
                                    v-model="rejectionReason"
                                    placeholder="Reason for rejection…"
                                    rows="2"
                                    class="w-full rounded-md border border-input bg-background px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-ring"
                                />
                                <div class="flex gap-2">
                                    <button
                                        class="rounded-md bg-red-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-700 disabled:opacity-50"
                                        :disabled="!rejectionReason.trim()"
                                        @click="submitRejection(reservation.id)"
                                    >
                                        Confirm Reject
                                    </button>
                                    <button
                                        class="rounded-md border border-input px-3 py-1.5 text-xs font-medium hover:bg-muted"
                                        @click="cancelRejecting"
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </div>
                            <div v-else class="flex gap-2">
                                <button
                                    class="rounded-md bg-green-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-green-700"
                                    @click="confirmReservation(reservation.id)"
                                >
                                    Confirm
                                </button>
                                <button
                                    class="rounded-md bg-red-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-700"
                                    @click="startRejecting(reservation.id)"
                                >
                                    Reject
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
