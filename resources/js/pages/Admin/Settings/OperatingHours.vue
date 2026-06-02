<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { update as updateRoute } from '@/routes/admin/settings/operating-hours';

defineOptions({
    layout: AdminLayout,
});

type HourRow = {
    id: number;
    day_of_week: number;
    open_time: string | null;
    close_time: string | null;
    is_closed: boolean;
};

const props = defineProps<{
    hours: HourRow[];
}>();

const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

const form = useForm({
    hours: props.hours.map((h) => ({
        day_of_week: h.day_of_week,
        is_closed: h.is_closed,
        open_time: h.open_time ?? '12:00',
        close_time: h.close_time ?? '22:00',
    })),
});

function submit(): void {
    form.patch(updateRoute.url(), { preserveScroll: true });
}
</script>

<template>
    <Head title="Operating Hours" />

    <div class="flex flex-col gap-6 p-6">
        <div>
            <h1 class="text-xl font-semibold">Operating Hours</h1>
            <p class="mt-0.5 text-sm text-muted-foreground">Configure when the restaurant is open each day of the week.</p>
        </div>

        <form @submit.prevent="submit">
            <div class="rounded-xl border border-sidebar-border/70 bg-card">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-muted-foreground">
                            <th class="px-4 py-3 font-medium">Day</th>
                            <th class="px-4 py-3 font-medium">Closed</th>
                            <th class="px-4 py-3 font-medium">Opens</th>
                            <th class="px-4 py-3 font-medium">Closes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(row, i) in form.hours"
                            :key="row.day_of_week"
                            class="border-b last:border-0"
                        >
                            <td class="px-4 py-3 font-medium">{{ dayNames[row.day_of_week] }}</td>
                            <td class="px-4 py-3">
                                <input
                                    v-model="row.is_closed"
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-input"
                                />
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-col gap-1">
                                    <input
                                        v-model="row.open_time"
                                        type="time"
                                        :disabled="row.is_closed"
                                        class="h-8 w-28 rounded-md border border-input bg-background px-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring disabled:opacity-40"
                                    />
                                    <p v-if="form.errors[`hours.${i}.open_time`]" class="text-xs text-destructive">
                                        {{ form.errors[`hours.${i}.open_time`] }}
                                    </p>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-col gap-1">
                                    <input
                                        v-model="row.close_time"
                                        type="time"
                                        :disabled="row.is_closed"
                                        class="h-8 w-28 rounded-md border border-input bg-background px-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring disabled:opacity-40"
                                    />
                                    <p v-if="form.errors[`hours.${i}.close_time`]" class="text-xs text-destructive">
                                        {{ form.errors[`hours.${i}.close_time`] }}
                                    </p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                <button
                    type="submit"
                    class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Saving…' : 'Save changes' }}
                </button>
            </div>
        </form>
    </div>
</template>
