<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { home } from '@/routes'

defineProps<{
    referenceNumber: string
}>()
</script>

<template>
    <Head title="Reservation Rejected" />

    <div class="flex min-h-screen flex-col items-center justify-center bg-gray-50 px-4 dark:bg-gray-950">
        <div class="w-full max-w-md rounded-xl border bg-white p-8 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h1 class="mb-2 text-2xl font-bold text-gray-900 dark:text-gray-100">Reservation rejected</h1>

            <p class="mb-6 text-gray-500 dark:text-gray-400">
                Reservation <span class="font-mono font-medium text-gray-700 dark:text-gray-300">{{ referenceNumber }}</span>
                has been rejected. A notification has been sent to the guest.
            </p>

            <Link
                :href="home()"
                class="text-sm text-gray-600 underline underline-offset-4 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100"
            >
                Back to homepage
            </Link>
        </div>
    </div>
</template>
