<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'

const props = defineProps<{
    reservation: {
        reference_number: string
        first_name: string
        last_name: string
        reservation_date: string
        reservation_time: string
        guest_count: number
    }
    submitUrl: string
}>()

const form = useForm({ rejection_reason: '' })

function submit() {
    form.post(props.submitUrl)
}

function formatDate(date: string): string {
    return new Date(date).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    })
}
</script>

<template>
    <Head title="Reject Reservation" />

    <div class="flex min-h-screen flex-col items-center justify-center bg-gray-50 px-4 dark:bg-gray-950">
        <div class="w-full max-w-md rounded-xl border bg-white p-8 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h1 class="mb-2 text-2xl font-bold text-gray-900 dark:text-gray-100">Reject reservation</h1>

            <div class="mb-6 rounded-lg bg-gray-50 p-4 text-sm text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                <p class="font-mono text-xs text-gray-500 dark:text-gray-400">{{ reservation.reference_number }}</p>
                <p class="mt-1 font-medium">{{ reservation.first_name }} {{ reservation.last_name }}</p>
                <p>{{ formatDate(reservation.reservation_date) }} at {{ reservation.reservation_time.slice(0, 5) }} · {{ reservation.guest_count }} guests</p>
            </div>

            <form @submit.prevent="submit">
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Reason for rejection
                </label>
                <textarea
                    v-model="form.rejection_reason"
                    rows="4"
                    class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-red-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                    placeholder="e.g. No availability for the requested time…"
                />
                <p v-if="form.errors.rejection_reason" class="mt-1 text-xs text-red-600">
                    {{ form.errors.rejection_reason }}
                </p>

                <button
                    type="submit"
                    class="mt-4 w-full rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50"
                    :disabled="form.processing || !form.rejection_reason.trim()"
                >
                    {{ form.processing ? 'Rejecting…' : 'Reject reservation' }}
                </button>
            </form>
        </div>
    </div>
</template>
