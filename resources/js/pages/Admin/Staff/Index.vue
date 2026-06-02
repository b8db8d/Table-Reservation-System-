<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { index as staffIndex, create as createRoute, toggleActive as toggleActiveRoute } from '@/routes/admin/staff';

defineOptions({
    layout: AdminLayout,
});

defineProps<{
    staff: Array<{
        id: number;
        name: string;
        email: string;
        role: string;
        is_active: boolean;
    }>;
}>();

function toggle(id: number): void {
    router.patch(toggleActiveRoute.url(id));
}
</script>

<template>
    <Head title="Staff Accounts" />

    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold">Staff Accounts</h1>
            <Link
                :href="createRoute.url()"
                class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
            >
                Add Account
            </Link>
        </div>

        <div class="rounded-xl border border-sidebar-border/70 bg-card">
            <div v-if="staff.length === 0" class="p-8 text-center text-sm text-muted-foreground">
                No staff accounts yet.
            </div>

            <table v-else class="w-full text-sm">
                <thead>
                    <tr class="border-b text-left text-muted-foreground">
                        <th class="px-4 py-3 font-medium">Name</th>
                        <th class="px-4 py-3 font-medium">Email</th>
                        <th class="px-4 py-3 font-medium">Role</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="s in staff"
                        :key="s.id"
                        class="border-b last:border-0 hover:bg-muted/40"
                    >
                        <td class="px-4 py-3 font-medium">{{ s.name }}</td>
                        <td class="px-4 py-3 text-muted-foreground">{{ s.email }}</td>
                        <td class="px-4 py-3 capitalize">{{ s.role }}</td>
                        <td class="px-4 py-3">
                            <span
                                class="rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="s.is_active
                                    ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                                    : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400'"
                            >
                                {{ s.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <button
                                class="text-xs underline underline-offset-2 hover:opacity-75"
                                :class="s.is_active ? 'text-destructive' : 'text-primary'"
                                @click="toggle(s.id)"
                            >
                                {{ s.is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
