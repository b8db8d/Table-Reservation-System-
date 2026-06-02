<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { index as groupsIndex, create as createRoute, edit as editRoute, destroy as destroyRoute } from '@/routes/admin/tables/groups';

defineOptions({
    layout: AdminLayout,
});

defineProps<{
    groups: Array<{
        id: number;
        name: string | null;
        min_guests: number;
        combined_capacity: number;
        tables: Array<{ id: number; name: string; capacity: number }>;
    }>;
}>();

function deleteGroup(id: number, name: string | null): void {
    const label = name ?? `Group #${id}`;
    if (!window.confirm(`Delete "${label}"? This cannot be undone.`)) { return; }
    router.delete(destroyRoute.url(id));
}
</script>

<template>
    <Head title="Joining Groups" />

    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold">Table Joining Groups</h1>
            <Link
                :href="createRoute.url()"
                class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
            >
                Add Group
            </Link>
        </div>

        <div class="rounded-xl border border-sidebar-border/70 bg-card">
            <div v-if="groups.length === 0" class="p-8 text-center text-sm text-muted-foreground">
                No joining groups configured yet.
            </div>

            <table v-else class="w-full text-sm">
                <thead>
                    <tr class="border-b text-left text-muted-foreground">
                        <th class="px-4 py-3 font-medium">Name</th>
                        <th class="px-4 py-3 font-medium">Tables</th>
                        <th class="px-4 py-3 font-medium">Combined Capacity</th>
                        <th class="px-4 py-3 font-medium">Min Guests</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="g in groups"
                        :key="g.id"
                        class="border-b last:border-0 hover:bg-muted/40"
                    >
                        <td class="px-4 py-3 font-medium">{{ g.name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1">
                                <span
                                    v-for="t in g.tables"
                                    :key="t.id"
                                    class="rounded-full bg-muted px-2 py-0.5 text-xs"
                                >
                                    {{ t.name }} ({{ t.capacity }})
                                </span>
                            </div>
                        </td>
                        <td class="px-4 py-3">{{ g.combined_capacity }}</td>
                        <td class="px-4 py-3">{{ g.min_guests }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <Link :href="editRoute.url(g.id)" class="text-xs text-primary underline underline-offset-2 hover:opacity-75">Edit</Link>
                                <button
                                    class="text-xs text-destructive underline underline-offset-2 hover:opacity-75"
                                    @click="deleteGroup(g.id, g.name)"
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
