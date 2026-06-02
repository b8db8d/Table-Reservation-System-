<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { home } from '@/routes'

defineProps<{
    referenceNumber: string | null
}>()
</script>

<template>
    <Head title="Reservation Received" />

    <div class="flex min-h-screen flex-col items-center justify-center bg-gray-50 px-4 dark:bg-gray-950">
        <div class="w-full max-w-md rounded-xl border bg-white p-8 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h1 class="mb-2 text-2xl font-bold text-gray-900 dark:text-gray-100">Reservation received!</h1>

            <p v-if="referenceNumber" class="mb-4 font-mono text-sm text-gray-700 dark:text-gray-300">
                Reference: <span class="font-semibold">{{ referenceNumber }}</span>
            </p>

            <p class="mb-6 text-gray-500 dark:text-gray-400">
                You will receive a confirmation email once our team reviews your booking.
            </p>

            <Link
                :href="home()"
                class="text-sm text-gray-600 underline underline-offset-4 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100"
            >
                Make another reservation
            </Link>
        </div>
    </div>
</template>
