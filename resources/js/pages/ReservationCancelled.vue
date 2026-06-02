<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { home } from '@/routes'

defineProps<{
    message: string | null
}>()
</script>

<template>
    <Head title="Reservation Cancelled" />

    <div class="flex min-h-screen flex-col items-center justify-center bg-gray-50 px-4 dark:bg-gray-950">
        <div class="w-full max-w-md rounded-xl border bg-white p-8 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h1 class="mb-2 text-2xl font-bold text-gray-900 dark:text-gray-100">
                {{ message === 'already_cancelled' ? 'Already cancelled' : 'Reservation cancelled' }}
            </h1>

            <p class="mb-6 text-gray-500 dark:text-gray-400">
                <template v-if="message === 'already_cancelled'">
                    This reservation has already been cancelled.
                </template>
                <template v-else>
                    Your reservation has been successfully cancelled. You will receive a confirmation email shortly.
                </template>
            </p>

            <Link
                :href="home()"
                class="text-sm text-gray-600 underline underline-offset-4 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100"
            >
                Make a new reservation
            </Link>
        </div>
    </div>
</template>
