<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { store as staffStore, index as staffIndex } from '@/routes/admin/staff';

defineOptions({
    layout: AdminLayout,
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'staff',
});

function submit(): void {
    form.post(staffStore.url(), {
        onSuccess: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <Head title="Add Staff Account" />

    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold">Add Staff Account</h1>
            <a :href="staffIndex.url()" class="text-sm text-primary underline underline-offset-2 hover:opacity-75">
                ← Back to staff
            </a>
        </div>

        <form class="max-w-lg" @submit.prevent="submit">
            <div class="rounded-xl border border-sidebar-border/70 bg-card p-6">
                <div class="flex flex-col gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Name</label>
                        <input
                            v-model="form.name"
                            type="text"
                            autocomplete="off"
                            class="h-9 rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                        <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Email</label>
                        <input
                            v-model="form.email"
                            type="email"
                            autocomplete="off"
                            class="h-9 rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                        <p v-if="form.errors.email" class="text-xs text-destructive">{{ form.errors.email }}</p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Role</label>
                        <select
                            v-model="form.role"
                            class="h-9 rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                        >
                            <option value="staff">Staff</option>
                            <option value="manager">Manager</option>
                        </select>
                        <p v-if="form.errors.role" class="text-xs text-destructive">{{ form.errors.role }}</p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Password</label>
                        <input
                            v-model="form.password"
                            type="password"
                            autocomplete="new-password"
                            class="h-9 rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                        <p v-if="form.errors.password" class="text-xs text-destructive">{{ form.errors.password }}</p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Confirm Password</label>
                        <input
                            v-model="form.password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            class="h-9 rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                    </div>
                </div>
            </div>

            <div class="mt-4 flex gap-3">
                <button
                    type="submit"
                    class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Creating…' : 'Create Account' }}
                </button>
                <a
                    :href="staffIndex.url()"
                    class="rounded-md border border-input px-4 py-2 text-sm font-medium hover:bg-muted"
                >
                    Cancel
                </a>
            </div>
        </form>
    </div>
</template>
