<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { index as tablesIndex, create as createRoute, edit as editRoute, destroy as destroyRoute } from '@/routes/admin/tables';

defineOptions({
    layout: AdminLayout,
});

defineProps<{
    tables: Array<{
        id: number;
        name: string;
        capacity: number;
        is_active: boolean;
    }>;
}>();

function deleteTable(id: number, name: string): void {
    if (!window.confirm(`Delete "${name}"? This cannot be undone.`)) { return; }
    router.delete(destroyRoute.url(id));
}
</script>

<template>
    <Head title="Tables" />

    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold">Restaurant Tables</h1>
            <Link
                :href="createRoute.url()"
                class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
            >
                Add Table
            </Link>
        </div>

        <div class="rounded-xl border border-sidebar-border/70 bg-card">
            <div v-if="tables.length === 0" class="p-8 text-center text-sm text-muted-foreground">
                No tables configured yet.
            </div>

            <table v-else class="w-full text-sm">
                <thead>
                    <tr class="border-b text-left text-muted-foreground">
                        <th class="px-4 py-3 font-medium">Name</th>
                        <th class="px-4 py-3 font-medium">Capacity</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="t in tables"
                        :key="t.id"
                        class="border-b last:border-0 hover:bg-muted/40"
                    >
                        <td class="px-4 py-3 font-medium">{{ t.name }}</td>
                        <td class="px-4 py-3">{{ t.capacity }}</td>
                        <td class="px-4 py-3">
                            <span
                                class="rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="t.is_active
                                    ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                                    : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400'"
                            >
                                {{ t.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <Link :href="editRoute.url(t.id)" class="text-xs text-primary underline underline-offset-2 hover:opacity-75">Edit</Link>
                                <button
                                    class="text-xs text-destructive underline underline-offset-2 hover:opacity-75"
                                    @click="deleteTable(t.id, t.name)"
                                >
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
