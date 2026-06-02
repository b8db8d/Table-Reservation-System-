<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { store as storeRoute, index as indexRoute } from '@/routes/admin/reservations';

defineOptions({
    layout: AdminLayout,
});

const props = defineProps<{
    statuses: string[];
}>();

const statusLabels: Record<string, string> = {
    pending: 'Pending',
    confirmed: 'Confirmed',
};

const form = useForm({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    guest_count: 2,
    reservation_date: '',
    reservation_time: '',
    notes: '',
    status: 'pending',
});

function submit(): void {
    form.post(storeRoute.url());
}
</script>

<template>
    <Head title="New Reservation" />

    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold">New Reservation</h1>
        </div>

        <form class="max-w-2xl" @submit.prevent="submit">
            <div class="rounded-xl border border-sidebar-border/70 bg-card p-6">
                <div class="grid grid-cols-2 gap-4">
                    <!-- First name -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">First name</label>
                        <input
                            v-model="form.first_name"
                            type="text"
                            class="h-9 rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                        <p v-if="form.errors.first_name" class="text-xs text-destructive">{{ form.errors.first_name }}</p>
                    </div>

                    <!-- Last name -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Last name</label>
                        <input
                            v-model="form.last_name"
                            type="text"
                            class="h-9 rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                        <p v-if="form.errors.last_name" class="text-xs text-destructive">{{ form.errors.last_name }}</p>
                    </div>

                    <!-- Email -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Email</label>
                        <input
                            v-model="form.email"
                            type="email"
                            class="h-9 rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                        <p v-if="form.errors.email" class="text-xs text-destructive">{{ form.errors.email }}</p>
                    </div>

                    <!-- Phone -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Phone</label>
                        <input
                            v-model="form.phone"
                            type="tel"
                            placeholder="+1 555 000 0000"
                            class="h-9 rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                        <p v-if="form.errors.phone" class="text-xs text-destructive">{{ form.errors.phone }}</p>
                    </div>

                    <!-- Date -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Date</label>
                        <input
                            v-model="form.reservation_date"
                            type="date"
                            class="h-9 rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                        <p v-if="form.errors.reservation_date" class="text-xs text-destructive">{{ form.errors.reservation_date }}</p>
                    </div>

                    <!-- Time -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Time</label>
                        <input
                            v-model="form.reservation_time"
                            type="time"
                            class="h-9 rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                        <p v-if="form.errors.reservation_time" class="text-xs text-destructive">{{ form.errors.reservation_time }}</p>
                    </div>

                    <!-- Guest count -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Guest count</label>
                        <input
                            v-model.number="form.guest_count"
                            type="number"
                            min="1"
                            max="20"
                            class="h-9 rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                        <p v-if="form.errors.guest_count" class="text-xs text-destructive">{{ form.errors.guest_count }}</p>
                    </div>

                    <!-- Status -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Status</label>
                        <select
                            v-model="form.status"
                            class="h-9 rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                        >
                            <option v-for="s in statuses" :key="s" :value="s">{{ statusLabels[s] }}</option>
                        </select>
                        <p v-if="form.errors.status" class="text-xs text-destructive">{{ form.errors.status }}</p>
                    </div>

                    <!-- Notes -->
                    <div class="col-span-2 flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Notes <span class="text-muted-foreground">(optional)</span></label>
                        <textarea
                            v-model="form.notes"
                            rows="3"
                            class="rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                        <p v-if="form.errors.notes" class="text-xs text-destructive">{{ form.errors.notes }}</p>
                    </div>
                </div>
            </div>

            <div class="mt-4 flex gap-3">
                <button
                    type="submit"
                    class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Creating…' : 'Create reservation' }}
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
