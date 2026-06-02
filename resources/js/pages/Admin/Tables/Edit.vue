<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { update as updateRoute, index as indexRoute } from '@/routes/admin/tables';

defineOptions({
    layout: AdminLayout,
});

const props = defineProps<{
    table: {
        id: number;
        name: string;
        capacity: number;
        is_active: boolean;
    };
}>();

const form = useForm({
    name: props.table.name,
    capacity: props.table.capacity,
    is_active: props.table.is_active,
});

function submit(): void {
    form.patch(updateRoute.url(props.table.id));
}
</script>

<template>
    <Head title="Edit Table" />

    <div class="flex flex-col gap-6 p-6">
        <h1 class="text-xl font-semibold">Edit Table</h1>

        <form class="max-w-lg" @submit.prevent="submit">
            <div class="rounded-xl border border-sidebar-border/70 bg-card p-6">
                <div class="flex flex-col gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Name</label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="h-9 rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                        <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Capacity</label>
                        <input
                            v-model.number="form.capacity"
                            type="number"
                            min="1"
                            max="50"
                            class="h-9 rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                        <p v-if="form.errors.capacity" class="text-xs text-destructive">{{ form.errors.capacity }}</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <input
                            id="is_active"
                            v-model="form.is_active"
                            type="checkbox"
                            class="h-4 w-4 rounded border-input"
                        />
                        <label for="is_active" class="text-sm font-medium">Active</label>
                    </div>
                </div>
            </div>

            <div class="mt-4 flex gap-3">
                <button
                    type="submit"
                    class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Saving…' : 'Save changes' }}
                </button>
                <a
                    :href="indexRoute.url()"
                    class="rounded-md border border-input px-4 py-2 text-sm font-medium hover:bg-muted"
                >
                    Cancel
                </a>
            </div>
        </form>
    </div>
</template>
