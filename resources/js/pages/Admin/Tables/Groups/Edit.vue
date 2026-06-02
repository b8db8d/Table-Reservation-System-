<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { update as updateRoute, index as indexRoute } from '@/routes/admin/tables/groups';

defineOptions({
    layout: AdminLayout,
});

const props = defineProps<{
    group: {
        id: number;
        name: string | null;
        min_guests: number;
        table_ids: number[];
    };
    availableTables: Array<{ id: number; name: string; capacity: number }>;
}>();

const form = useForm({
    name: props.group.name ?? '',
    min_guests: props.group.min_guests,
    table_ids: [...props.group.table_ids],
});

function toggleTable(id: number): void {
    const idx = form.table_ids.indexOf(id);
    if (idx === -1) {
        form.table_ids.push(id);
    } else {
        form.table_ids.splice(idx, 1);
    }
}

function submit(): void {
    form.patch(updateRoute.url(props.group.id));
}
</script>

<template>
    <Head title="Edit Joining Group" />

    <div class="flex flex-col gap-6 p-6">
        <h1 class="text-xl font-semibold">Edit Joining Group</h1>

        <form class="max-w-lg" @submit.prevent="submit">
            <div class="rounded-xl border border-sidebar-border/70 bg-card p-6">
                <div class="flex flex-col gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Name <span class="text-muted-foreground">(optional)</span></label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="h-9 rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                        <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Min Guests to Trigger Joining</label>
                        <input
                            v-model.number="form.min_guests"
                            type="number"
                            min="1"
                            max="50"
                            class="h-9 rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                        <p v-if="form.errors.min_guests" class="text-xs text-destructive">{{ form.errors.min_guests }}</p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Tables</label>
                        <div class="flex flex-col gap-2">
                            <label
                                v-for="t in availableTables"
                                :key="t.id"
                                class="flex cursor-pointer items-center gap-2 rounded-md border border-input px-3 py-2 text-sm hover:bg-muted"
                                :class="{ 'border-primary bg-primary/5': form.table_ids.includes(t.id) }"
                            >
                                <input
                                    type="checkbox"
                                    :value="t.id"
                                    :checked="form.table_ids.includes(t.id)"
                                    class="h-4 w-4 rounded border-input"
                                    @change="toggleTable(t.id)"
                                />
                                {{ t.name }} — capacity {{ t.capacity }}
                            </label>
                        </div>
                        <p v-if="form.errors.table_ids" class="text-xs text-destructive">{{ form.errors.table_ids }}</p>
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
