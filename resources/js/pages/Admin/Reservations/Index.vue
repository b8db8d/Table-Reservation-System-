<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { index as reservationsIndex, edit as editRoute, destroy as destroyRoute } from '@/routes/admin/reservations';
import { ref, watch } from 'vue';

defineOptions({
    layout: AdminLayout,
});

const props = defineProps<{
    reservations: {
        data: Array<{
            id: number;
            reference_number: string;
            first_name: string;
            last_name: string;
            email: string;
            phone: string;
            guest_count: number;
            reservation_date: string;
            reservation_time: string;
            status: string;
            created_at: string;
        }>;
        links: Array<{ url: string | null; label: string; active: boolean }>;
        total: number;
        last_page: number;
        current_page: number;
        per_page: number;
    };
    filters: Record<string, string>;
    sort: string;
    statuses: string[];
    canDelete: boolean;
}>();

const search = ref(props.filters.search ?? '');
const statusFilter = ref(props.filters.status ?? '');
const dateFrom = ref(props.filters.date_from ?? '');
const dateTo = ref(props.filters.date_to ?? '');
const currentSort = ref(props.sort);

let searchTimer: ReturnType<typeof setTimeout>;

watch(search, (value) => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => applyFilters(), 400);
});

watch([statusFilter, dateFrom, dateTo], () => applyFilters());

function applyFilters(): void {
    const filter: Record<string, string> = {};
    if (search.value) { filter.search = search.value; }
    if (statusFilter.value) { filter.status = statusFilter.value; }
    if (dateFrom.value) { filter.date_from = dateFrom.value; }
    if (dateTo.value) { filter.date_to = dateTo.value; }

    router.get(reservationsIndex.url(), { filter, sort: currentSort.value }, {
        preserveState: true,
        replace: true,
    });
}

function sortBy(column: string): void {
    currentSort.value = currentSort.value === column ? `-${column}` : column;
    applyFilters();
}

function sortIcon(column: string): string {
    if (currentSort.value === column) { return '↑'; }
    if (currentSort.value === `-${column}`) { return '↓'; }
    return '↕';
}

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

function deleteReservation(id: number, reference: string): void {
    if (!window.confirm(`Delete reservation ${reference}? This cannot be undone.`)) { return; }
    router.delete(destroyRoute.url(id));
}

function formatDate(date: string): string {
    return new Date(date).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}
</script>

<template>
    <Head title="All Reservations" />

    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold">All Reservations</h1>
            <span class="text-sm text-muted-foreground">{{ reservations.total }} total</span>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap gap-3">
            <input
                v-model="search"
                type="search"
                placeholder="Search name, email, reference…"
                class="h-9 rounded-md border border-input bg-background px-3 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
            />
            <select
                v-model="statusFilter"
                class="h-9 rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
            >
                <option value="">All statuses</option>
                <option v-for="s in statuses" :key="s" :value="s">{{ statusLabels[s] }}</option>
            </select>
            <input
                v-model="dateFrom"
                type="date"
                class="h-9 rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
            />
            <input
                v-model="dateTo"
                type="date"
                class="h-9 rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
            />
        </div>

        <!-- Table -->
        <div class="rounded-xl border border-sidebar-border/70 bg-card">
            <div v-if="reservations.data.length === 0" class="p-8 text-center text-sm text-muted-foreground">
                No reservations found.
            </div>

            <table v-else class="w-full text-sm">
                <thead>
                    <tr class="border-b text-left text-muted-foreground">
                        <th class="px-4 py-3 font-medium">Reference</th>
                        <th class="px-4 py-3 font-medium">Guest</th>
                        <th class="px-4 py-3 font-medium">
                            <button class="flex items-center gap-1 hover:text-foreground" @click="sortBy('reservation_date')">
                                Date {{ sortIcon('reservation_date') }}
                            </button>
                        </th>
                        <th class="px-4 py-3 font-medium">
                            <button class="flex items-center gap-1 hover:text-foreground" @click="sortBy('guest_count')">
                                Guests {{ sortIcon('guest_count') }}
                            </button>
                        </th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">
                            <button class="flex items-center gap-1 hover:text-foreground" @click="sortBy('created_at')">
                                Submitted {{ sortIcon('created_at') }}
                            </button>
                        </th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="r in reservations.data"
                        :key="r.id"
                        class="border-b last:border-0 hover:bg-muted/40"
                    >
                        <td class="px-4 py-3 font-mono text-xs">{{ r.reference_number }}</td>
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ r.first_name }} {{ r.last_name }}</div>
                            <div class="text-xs text-muted-foreground">{{ r.email }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div>{{ formatDate(r.reservation_date) }}</div>
                            <div class="text-xs text-muted-foreground">{{ r.reservation_time.slice(0, 5) }}</div>
                        </td>
                        <td class="px-4 py-3">{{ r.guest_count }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="statusClasses[r.status]">
                                {{ statusLabels[r.status] }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs text-muted-foreground">{{ formatDate(r.created_at) }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <Link :href="editRoute.url(r.id)" class="text-xs text-primary underline underline-offset-2 hover:opacity-75">Edit</Link>
                                <button
                                    v-if="canDelete"
                                    class="text-xs text-destructive underline underline-offset-2 hover:opacity-75"
                                    @click="deleteReservation(r.id, r.reference_number)"
                                >
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="reservations.last_page > 1" class="flex items-center justify-center gap-1">
            <template v-for="link in reservations.links" :key="link.label">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    class="rounded-md px-3 py-1.5 text-sm"
                    :class="link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'"
                    v-html="link.label"
                />
                <span
                    v-else
                    class="rounded-md px-3 py-1.5 text-sm text-muted-foreground opacity-50"
                    v-html="link.label"
                />
            </template>
        </div>
    </div>
</template>
