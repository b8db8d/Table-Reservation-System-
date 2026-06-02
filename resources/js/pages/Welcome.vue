<script setup lang="ts">
import { Form, Head, Link, useHttp } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import { useEchoPublic } from '@laravel/echo-vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Spinner } from '@/components/ui/spinner'
import { store } from '@/actions/App/Http/Controllers/ReservationController'
import { dashboard, login, register } from '@/routes'

interface OperatingHourEntry {
    isClosed: boolean
    openTime: string | null
    closeTime: string | null
}

interface HoneypotData {
    enabled: boolean
    nameFieldName: string
    validFromFieldName: string
    encryptedValidFrom: string
}

interface AvailabilityTable {
    id: number
    name: string
    capacity: number
}

interface AvailabilityGroup {
    id: number
    name: string | null
    capacity: number
    min_guests: number
}

interface AvailabilityResult {
    available: boolean
    individual_tables: AvailabilityTable[]
    joining_groups: AvailabilityGroup[]
}

const props = defineProps<{
    canRegister: boolean
    operatingHours: Record<string, OperatingHourEntry>
    honeypot: HoneypotData
}>()

const today = new Date().toISOString().split('T')[0]

const selectedDate = ref('')
const selectedTime = ref('')
const selectedGuests = ref(2)

const availability = ref<AvailabilityResult | null>(null)
const availabilityChecked = ref(false)

const http = useHttp({})

const dayOfWeekForDate = (dateString: string): number => {
    const [year, month, day] = dateString.split('-').map(Number)
    return new Date(year, month - 1, day).getDay()
}

const hoursForSelectedDate = computed((): OperatingHourEntry | null => {
    if (!selectedDate.value) return null
    const dow = dayOfWeekForDate(selectedDate.value)
    return props.operatingHours[dow] ?? null
})

const closedDayError = computed((): string | null => {
    if (!selectedDate.value) return null
    const hours = hoursForSelectedDate.value
    if (hours?.isClosed) return 'The restaurant is closed on this day.'
    return null
})

const timeSlots = computed((): string[] => {
    const hours = hoursForSelectedDate.value
    if (!hours || hours.isClosed || !hours.openTime || !hours.closeTime) return []

    const slots: string[] = []
    const [openH, openM] = hours.openTime.split(':').map(Number)
    const [closeH, closeM] = hours.closeTime.split(':').map(Number)

    let h = openH
    let m = openM

    while (h < closeH || (h === closeH && m < closeM)) {
        slots.push(`${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`)
        m += 30
        if (m >= 60) {
            h++
            m = 0
        }
    }

    return slots
})

watch(selectedDate, () => {
    selectedTime.value = ''
    availability.value = null
    availabilityChecked.value = false
})

watch([selectedTime, selectedGuests], () => {
    availability.value = null
    availabilityChecked.value = false
})

function checkAvailability() {
    if (!selectedDate.value || !selectedTime.value || closedDayError.value) return

    http.get(
        `/api/availability?date=${selectedDate.value}&time=${selectedTime.value}&guests=${selectedGuests.value}`,
        {
            onSuccess: (response: AvailabilityResult) => {
                availability.value = response
                availabilityChecked.value = true
            },
        },
    )
}

useEchoPublic('availability', 'AvailabilityUpdated', () => {
    if (availabilityChecked.value) {
        availability.value = null
        availabilityChecked.value = false
        checkAvailability()
    }
})

const canSubmit = computed(
    () =>
        availabilityChecked.value &&
        availability.value?.available === true &&
        !http.processing,
)

const availabilityMessage = computed((): string | null => {
    if (!availabilityChecked.value || !availability.value) return null

    const { available, individual_tables, joining_groups } = availability.value

    if (!available) return 'No tables available for this slot.'

    const parts: string[] = []

    if (individual_tables.length > 0) {
        parts.push(
            `${individual_tables.length} table${individual_tables.length > 1 ? 's' : ''} available`,
        )
    }

    if (joining_groups.length > 0) {
        const groupDesc = joining_groups
            .map((g) => `1 combined table for ${g.min_guests}–${g.capacity} people`)
            .join(', ')
        parts.push(groupDesc)
    }

    return parts.join('; ')
})
</script>

<template>
    <Head title="Book a Table" />

    <div class="min-h-screen bg-gray-50 dark:bg-gray-950">
        <header class="border-b bg-white dark:border-gray-800 dark:bg-gray-900">
            <div class="mx-auto flex max-w-3xl items-center justify-between px-4 py-4">
                <span class="text-lg font-semibold text-gray-900 dark:text-gray-100">Restaurant</span>
                <nav class="flex items-center gap-3 text-sm">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100"
                    >
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100"
                        >
                            Log in
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="register()"
                            class="rounded-md bg-gray-900 px-3 py-1.5 text-white hover:bg-gray-700 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200"
                        >
                            Register
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-3xl px-4 py-10">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Book a Table</h1>
                <p class="mt-1 text-gray-500 dark:text-gray-400">Reserve your spot in a few simple steps.</p>
            </div>

            <!-- Slot Selection -->
            <section class="mb-6 rounded-xl border bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h2 class="mb-4 text-base font-semibold text-gray-900 dark:text-gray-100">Choose date, time &amp; guests</h2>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="grid gap-1.5">
                        <Label for="date">Date</Label>
                        <Input
                            id="date"
                            v-model="selectedDate"
                            type="date"
                            :min="today"
                            :aria-invalid="closedDayError ? 'true' : undefined"
                        />
                        <p v-if="closedDayError" class="text-sm text-destructive">{{ closedDayError }}</p>
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="time">Time</Label>
                        <select
                            id="time"
                            v-model="selectedTime"
                            :disabled="!selectedDate || !!closedDayError || timeSlots.length === 0"
                            class="border-input focus-visible:ring-ring/50 h-9 w-full rounded-md border bg-transparent px-3 py-1 text-sm shadow-xs outline-none transition-[color,box-shadow] focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50 dark:bg-input/30"
                        >
                            <option value="" disabled>Select a time</option>
                            <option v-for="slot in timeSlots" :key="slot" :value="slot">{{ slot }}</option>
                        </select>
                        <p
                            v-if="selectedDate && !closedDayError && timeSlots.length === 0"
                            class="text-sm text-muted-foreground"
                        >
                            No available times for this day.
                        </p>
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="guests">Guests</Label>
                        <select
                            id="guests"
                            v-model.number="selectedGuests"
                            class="border-input focus-visible:ring-ring/50 h-9 w-full rounded-md border bg-transparent px-3 py-1 text-sm shadow-xs outline-none transition-[color,box-shadow] focus-visible:ring-[3px] dark:bg-input/30"
                        >
                            <option v-for="n in 8" :key="n" :value="n">{{ n }} {{ n === 1 ? 'guest' : 'guests' }}</option>
                        </select>
                    </div>
                </div>

                <div class="mt-4 flex items-center gap-4">
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="!selectedDate || !selectedTime || !!closedDayError || http.processing"
                        @click="checkAvailability"
                    >
                        <Spinner v-if="http.processing" />
                        Check availability
                    </Button>

                    <p
                        v-if="availabilityChecked && availability"
                        :class="[
                            'text-sm font-medium',
                            availability.available ? 'text-green-600 dark:text-green-400' : 'text-destructive',
                        ]"
                    >
                        {{ availabilityMessage }}
                    </p>
                </div>
            </section>

            <!-- Booking Form -->
            <section class="rounded-xl border bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h2 class="mb-4 text-base font-semibold text-gray-900 dark:text-gray-100">Your details</h2>

                <Form
                    v-bind="store.form()"
                    v-slot="{ errors, processing }"
                    class="grid gap-4"
                >
                    <!-- Hidden slot fields -->
                    <input type="hidden" name="reservation_date" :value="selectedDate" />
                    <input type="hidden" name="reservation_time" :value="selectedTime" />
                    <input type="hidden" name="guest_count" :value="selectedGuests" />

                    <!-- Honeypot fields -->
                    <template v-if="honeypot.enabled">
                        <input
                            type="text"
                            :name="honeypot.nameFieldName"
                            value=""
                            style="display: none"
                            tabindex="-1"
                            autocomplete="off"
                        />
                        <input type="hidden" :name="honeypot.validFromFieldName" :value="honeypot.encryptedValidFrom" />
                    </template>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <Label for="first_name">First name</Label>
                            <Input id="first_name" name="first_name" type="text" autocomplete="given-name" required />
                            <InputError :message="errors.first_name" />
                        </div>

                        <div class="grid gap-1.5">
                            <Label for="last_name">Last name</Label>
                            <Input id="last_name" name="last_name" type="text" autocomplete="family-name" required />
                            <InputError :message="errors.last_name" />
                        </div>
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="email">Email address</Label>
                        <Input id="email" name="email" type="email" autocomplete="email" required />
                        <InputError :message="errors.email" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="phone">Phone number</Label>
                        <Input id="phone" name="phone" type="tel" autocomplete="tel" required />
                        <InputError :message="errors.phone" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="notes">Notes <span class="text-muted-foreground text-xs">(optional)</span></Label>
                        <textarea
                            id="notes"
                            name="notes"
                            rows="3"
                            class="border-input focus-visible:ring-ring/50 w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none transition-[color,box-shadow] focus-visible:ring-[3px] dark:bg-input/30"
                            placeholder="Any dietary requirements or special requests…"
                        />
                        <InputError :message="errors.notes" />
                    </div>

                    <div>
                        <p v-if="!availabilityChecked" class="mb-3 text-sm text-muted-foreground">
                            Please check availability above before submitting.
                        </p>
                        <p v-else-if="availability && !availability.available" class="mb-3 text-sm text-destructive">
                            No tables are available for the selected slot. Please choose a different date or time.
                        </p>

                        <Button
                            type="submit"
                            :disabled="!canSubmit || processing"
                        >
                            <Spinner v-if="processing" />
                            Request reservation
                        </Button>
                    </div>
                </Form>
            </section>
        </main>
    </div>
</template>
