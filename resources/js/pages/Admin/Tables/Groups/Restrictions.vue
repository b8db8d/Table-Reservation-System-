<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { index as groupsIndex } from '@/routes/admin/tables/groups';
import restrictionRoutes from '@/routes/admin/tables/groups/restrictions';

defineOptions({
    layout: AdminLayout,
});

const props = defineProps<{
    group: {
        id: number;
        name: string | null;
        tables: Array<{ id: number; name: string }>;
    };
    restrictions: Array<{
        id: number;
        day_of_week: number | null;
        start_time: string;
        end_time: string;
    }>;
}>();

const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

function dayLabel(day: number | null): string {
    return day === null ? 'Every day' : (dayNames[day] ?? String(day));
}

const form = useForm({
    day_of_week: '' as string | number,
    start_time: '',
    end_time: '',
});

function submit(): void {
    form.post(restrictionRoutes.index.url(props.group.id), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

function removeRestriction(restrictionId: number): void {
    if (!window.confirm('Remove this restriction?')) { return; }
    router.delete(restrictionRoutes.destroy.url(props.group.id, restrictionId), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="`Restrictions — ${group.name ?? 'Group #' + group.id}`" />

    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold">Restrictions</h1>
                <p class="mt-0.5 text-sm text-muted-foreground">
                    {{ group.name ?? `Group #${group.id}` }} —
                    {{ group.tables.map(t => t.name).join(', ') }}
                </p>
            </div>
            <Link :href="groupsIndex.url()" class="text-sm text-primary underline underline-offset-2 hover:opacity-75">
                ← Back to groups
            </Link>
        </div>

        <!-- Current restrictions -->
        <div class="rounded-xl border border-sidebar-border/70 bg-card">
            <div v-if="restrictions.length === 0" class="p-8 text-center text-sm text-muted-foreground">
                No restrictions yet. This group is available at all times.
            </div>

            <table v-else class="w-full text-sm">
                <thead>
                    <tr class="border-b text-left text-muted-foreground">
                        <th class="px-4 py-3 font-medium">Day</th>
                        <th class="px-4 py-3 font-medium">From</th>
                        <th class="px-4 py-3 font-medium">To</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="r in restrictions"
                        :key="r.id"
                        class="border-b last:border-0 hover:bg-muted/40"
                    >
                        <td class="px-4 py-3">{{ dayLabel(r.day_of_week) }}</td>
                        <td class="px-4 py-3">{{ r.start_time }}</td>
                        <td class="px-4 py-3">{{ r.end_time }}</td>
                        <td class="px-4 py-3">
                            <button
                                class="text-xs text-destructive underline underline-offset-2 hover:opacity-75"
                                @click="removeRestriction(r.id)"
                            >
                                Remove
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Add restriction form -->
        <div class="max-w-lg rounded-xl border border-sidebar-border/70 bg-card p-6">
            <h2 class="mb-4 text-sm font-semibold">Add Restriction</h2>

            <form class="flex flex-col gap-4" @submit.prevent="submit">
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium">Day of Week</label>
                    <select
                        v-model="form.day_of_week"
                        class="h-9 rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                    >
                        <option value="">Every day</option>
                        <option v-for="(name, idx) in dayNames" :key="idx" :value="idx">{{ name }}</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">From</label>
                        <input
                            v-model="form.start_time"
                            type="time"
                            class="h-9 rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                        <p v-if="form.errors.start_time" class="text-xs text-destructive">{{ form.errors.start_time }}</p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">To</label>
                        <input
                            v-model="form.end_time"
                            type="time"
                            class="h-9 rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                        <p v-if="form.errors.end_time" class="text-xs text-destructive">{{ form.errors.end_time }}</p>
                    </div>
                </div>

                <div>
                    <button
                        type="submit"
                        class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Adding…' : 'Add Restriction' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
