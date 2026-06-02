# Project Phases — Table Reservation System

## Legend

- ✅ **Completed** — already exists in the codebase
- 🔲 **Pending** — not yet implemented

---

## Already Completed (Starter Kit)

The following is provided by the Laravel Vue starter kit and Fortify integration already in place:

- ✅ Laravel 13 + Inertia.js v3 + Vue 3 + Tailwind CSS 4 project scaffold
- ✅ Fortify authentication: login, registration, password reset, email verification, 2FA
- ✅ Settings pages: profile update, password change, appearance
- ✅ Base migrations: `users`, `password_reset_tokens`, `sessions`, `cache`, `jobs`, `two_factor_columns`
- ✅ Packages installed: `spatie/laravel-permission`, `spatie/laravel-activitylog`, `spatie/laravel-honeypot`, `spatie/laravel-query-builder`, `propaganistas/laravel-phone`, `laravel/reverb`
- ✅ Tests: `AuthenticationTest`, `PasswordResetTest`, `RegistrationTest`, `TwoFactorChallengeTest`, `VerificationNotificationTest`, `PasswordConfirmationTest`, `DashboardTest`, `ProfileUpdateTest`, `SecurityTest`
- ✅ `RefreshDatabase` enabled globally in `Pest.php`

---

## Phase 1: Database Structure

> All models, migrations, factories, and seeders. No controllers or UI in this phase.

### Phase 1.1: Roles & Permissions Setup

**Task:** Publish and run `spatie/laravel-permission` migrations. Create a `RolesAndPermissionsSeeder` that seeds the three roles and all named permissions.

**Roles:** `manager`, `staff`, `customer`

**Permissions:**
- `reservations.view-any`, `reservations.view`, `reservations.create`, `reservations.update`, `reservations.delete`
- `reservations.confirm`, `reservations.reject`
- `tables.manage` (CRUD for restaurant tables and joining groups)
- `operating-hours.manage`
- `staff.manage` (create/deactivate staff accounts)

**Role → Permission assignments:**
- `manager` → all permissions
- `staff` → `reservations.view-any`, `reservations.view`, `reservations.create`, `reservations.update`, `reservations.confirm`, `reservations.reject`
- `customer` → `reservations.view` (own only)

**Update `User` model:** add `HasRoles` trait from `spatie/laravel-permission`.

**Tests (`tests/Feature/Auth/RolesTest.php`):**
- Manager has all listed permissions
- Staff has only their permitted set
- Customer has no admin permissions
- Unauthenticated users have no permissions

**Status:** ✅ Completed

---

### Phase 1.2: `restaurant_tables` Table

**Task:** `php artisan make:model RestaurantTable -mf`

**Migration — `restaurant_tables`:**
```
id
name          string          — e.g. "Table 1", "T-A"
capacity      unsignedTinyInteger
is_active     boolean, default true
timestamps
```

**Factory:** `RestaurantTableFactory` — random name (`Table {n}`), capacity 2/4/6/8 (random).

**Tests (`tests/Feature/Tables/RestaurantTableTest.php`):**
- Table can be created with valid fields
- Capacity must be a positive integer
- is_active defaults to true

**Status:** ✅ Completed

---

### Phase 1.3: `table_joining_groups` and `table_joining_group_restaurant_table` Tables

**Task:** `php artisan make:model TableJoiningGroup -mf`

**Migration — `table_joining_groups`:**
```
id
name              string, nullable     — optional label
min_guests        unsignedTinyInteger  — minimum guests to trigger joining
timestamps
```

**Migration — `table_joining_group_restaurant_table` (pivot):**
```
table_joining_group_id    FK → table_joining_groups.id  (cascade delete)
restaurant_table_id       FK → restaurant_tables.id      (cascade delete)
```

**Model relationships:**
- `TableJoiningGroup` `belongsToMany` `RestaurantTable`
- `RestaurantTable` `belongsToMany` `TableJoiningGroup`

**Factory:** generates a group with `min_guests` between 5–8.

**Tests (`tests/Feature/Tables/TableJoiningGroupTest.php`):**
- Group can be created and tables attached
- A table can only belong to one group (unique per table constraint or application-level check)
- Combined capacity is computed correctly from attached tables

**Status:** ✅ Completed

---

### Phase 1.4: `joining_group_restrictions` Table

**Task:** `php artisan make:model JoiningGroupRestriction -mf`

**Migration — `joining_group_restrictions`:**
```
id
table_joining_group_id    FK → table_joining_groups.id (cascade delete)
day_of_week               unsignedTinyInteger, nullable  — 0=Sun…6=Sat; null=every day
start_time                time
end_time                  time
timestamps
```
> `day_of_week` comment: "0=Sunday, 1=Monday … 6=Saturday. Null means the restriction applies every day."

**Tests (`tests/Feature/Tables/JoiningGroupRestrictionTest.php`):**
- Restriction can be scoped to a specific day or every day (null)
- start_time must be before end_time

**Status:** ✅ Completed

---

### Phase 1.5: `operating_hours` Table

**Task:** `php artisan make:model OperatingHours -mf`

**Migration — `operating_hours`:**
```
id
day_of_week    unsignedTinyInteger   — 0=Sun…6=Sat (unique)
open_time      time, nullable        — null if closed
close_time     time, nullable
is_closed      boolean, default false
timestamps
```

**Seeder — `OperatingHoursSeeder`:** insert 7 rows (Mon–Fri 12:00–22:00, Sat–Sun 12:00–23:00).

**Tests (`tests/Feature/OperatingHoursTest.php`):**
- Seven rows exist after seeder
- Each day_of_week is unique
- A closed day returns is_closed = true

**Status:** ✅ Completed

---

### Phase 1.6: `reservations` Table

**Task:** `php artisan make:model Reservation -mf`

**Migration — `reservations`:**
```
id
reference_number      string, unique         — e.g. "RES-20250419-001234"
first_name            string
last_name             string
email                 string
phone                 string
guest_count           unsignedTinyInteger
reservation_date      date
reservation_time      time
status                string                 — 'pending'|'confirmed'|'rejected'|'cancelled'; PHP Enum expected: App\Enums\ReservationStatus
notes                 text, nullable
rejection_reason      text, nullable
user_id               FK → users.id, nullable, set null on delete   — linked customer account if registered
confirmed_by          FK → users.id, nullable, set null on delete
confirmed_at          timestamp, nullable
rejected_by           FK → users.id, nullable, set null on delete
rejected_at           timestamp, nullable
cancelled_at          timestamp, nullable
cancellation_token    string, unique, nullable  — UUID; nulled out after use
timestamps
```

**Enum stub — `app/Enums/ReservationStatus.php`:**
```php
enum ReservationStatus: string {
    case Pending   = 'pending';
    case Confirmed = 'confirmed';
    case Rejected  = 'rejected';
    case Cancelled = 'cancelled';
}
```

**Factory — `ReservationFactory`:**
- Generates realistic names, emails, phones, guest counts 1–8
- States: `pending()`, `confirmed()`, `rejected()`, `cancelled()`
- `reference_number` auto-generated as `'RES-'.date('Ymd').'-'.str_pad($id, 6, '0', STR_PAD_LEFT)`

**Tests (`tests/Feature/Reservations/ReservationModelTest.php`):**
- Reservation can be created in pending status
- `cancellation_token` is a valid UUID
- `confirmed_at` is set when status is confirmed
- `reference_number` is unique across reservations

**Status:** ✅ Completed

---

### Phase 1.7: `reservation_restaurant_table` Pivot Table

**Task:** `php artisan make:migration create_reservation_restaurant_table_table`

**Migration — `reservation_restaurant_table` (pivot):**
```
reservation_id         FK → reservations.id  (cascade delete)
restaurant_table_id    FK → restaurant_tables.id (restrict)
```

**Model relationships:**
- `Reservation` `belongsToMany` `RestaurantTable`
- `RestaurantTable` `belongsToMany` `Reservation`

**Status:** ✅ Completed

---

### Phase 1.8: `activity_log` Table (spatie/laravel-activitylog)

**Task:** Publish and run `spatie/laravel-activitylog` migrations.

```bash
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"
php artisan migrate
```

Configure `Reservation` model with `LogsActivity` trait. Log the `status`, `confirmed_by`, `rejected_by`, `rejection_reason` attributes.

**Status:** ✅ Completed

---

## Phase 2: Core Services & Application Logic

### Phase 2.1: `ReservationStatus` Enum

**Task:** Create `app/Enums/ReservationStatus.php` (stubbed in Phase 1.6 migration). Used everywhere instead of raw strings.

**Status:** ✅ Completed

---

### Phase 2.2: Availability Service

**Task:** Create `app/Services/AvailabilityService.php`.

**Method:** `getAvailableCapacity(Carbon $date, Carbon $time): array`

**Logic:**
1. Fetch all `restaurant_tables` where `is_active = true`.
2. Find tables already locked by confirmed reservations overlapping the requested slot (same date + time, status = confirmed).
3. Check active joining groups: all tables in group must be free, guest count must meet `min_guests`, no `joining_group_restrictions` block the day/time.
4. Return breakdown: individual table capacities available + joinable group capacities available.

**Tests (`tests/Feature/Services/AvailabilityServiceTest.php`):**
- Returns all tables when no reservations exist
- Excludes tables already confirmed for that slot
- Returns joined combination when individual tables are insufficient
- Excludes joining group when one of its tables is already booked
- Excludes joining group when restriction applies on that day/time
- Returns empty when no capacity available

**Status:** ✅ Completed

---

### Phase 2.3: Table Assignment Service

**Task:** Create `app/Services/TableAssignmentService.php`.

**Method:** `assignTables(Reservation $reservation): void`

**Logic:**
1. Try to find a single `RestaurantTable` with `capacity >= guest_count` that is free for the slot.
2. If not found, try to find a `TableJoiningGroup` where: combined capacity sufficient, `guest_count >= min_guests`, all tables free, no restriction blocks the slot.
3. Assign found table(s) to `$reservation->restaurantTables()`.
4. Throw `NoTableAvailableException` if nothing fits.

**Tests (`tests/Feature/Services/TableAssignmentServiceTest.php`):**
- Assigns smallest sufficient individual table
- Falls back to joining group when no individual table fits
- Throws `NoTableAvailableException` when fully booked
- Does not use a joining group below the `min_guests` threshold
- Respects joining group restrictions by day/time

**Status:** ✅ Completed

---

### Phase 2.4: Reference Number Generator

**Task:** Create `app/Services/ReferenceNumberService.php` (or an Eloquent `creating` observer on `Reservation`).

**Format:** `RES-YYYYMMDD-XXXXXX` (date + zero-padded ID or random 6-digit suffix).

**Status:** 🔲 Pending

---

## Phase 3: Public Booking Flow

### Phase 3.1: Availability API Endpoint

**Task:** `GET /api/availability?date=YYYY-MM-DD&time=HH:MM&guests=N`

Controller: `App\Http\Controllers\Api\AvailabilityController`

Returns whether the requested slot can accommodate the guest count, and how (individual table or joined group).

**Tests (`tests/Feature/Api/AvailabilityControllerTest.php`):**
- Returns available when capacity exists
- Returns unavailable when fully booked
- Validates required parameters (date, time, guests)
- Rejects dates in the past
- Rejects times outside operating hours

**Status:** ✅ Completed

---

### Phase 3.2: Public Booking Page (Vue)

**Task:** `GET /` — extend existing `Welcome.vue` or create `resources/js/pages/Booking.vue`.

**Components:**
- `Calendar` (shadcn) for date selection — disables past dates and closed days
- Time input constrained to operating hours
- Guest count selector
- Availability status display ("3 tables for 2 people available", "1 combined table for 6–8 people available")
- Reservation form: first name, last name, email, phone, guest count, notes (optional)
- Honeypot field (via `spatie/laravel-honeypot`)
- Submit button

**Wayfinder:** generate typed route function for `POST /reservations`.

**Status:** ✅ Completed

---

### Phase 3.3: Reservation Submission

**Task:** `POST /reservations` → `App\Http\Controllers\ReservationController@store`

**Logic:**
1. Validate input (phone via `propaganistas/laravel-phone`, date/time in operating hours, guest count > 0).
2. Check availability via `AvailabilityService`.
3. Create `Reservation` with status `pending`, generate `cancellation_token` (UUID), generate `reference_number`.
4. Assign tables via `TableAssignmentService`.
5. Link to `user_id` if authenticated customer.
6. Dispatch `ReservationCreated` event → triggers emails (Phase 4).
7. Rate-limit: 3 submissions per IP per 10 minutes.
8. Apply `ProtectAgainstSpam` middleware.

**Tests (`tests/Feature/Reservations/ReservationStoreTest.php`):**
- Valid submission creates reservation in pending status
- Response redirects to success page with reference number
- Invalid phone returns validation error
- Past date returns validation error
- Time outside operating hours returns validation error
- Unavailable slot returns error (no table found)
- Guest count 0 or negative returns validation error
- Rate limiter blocks 4th submission from same IP within 10 minutes
- Honeypot filled returns 422

**Status:** ✅ Completed

---

### Phase 3.4: Reservation Success Page

**Task:** `GET /reservations/success` → `resources/js/pages/ReservationSuccess.vue`

Shows reference number and "you will receive an email" message.

**Status:** ✅ Completed

---

### Phase 3.5: Guest Cancellation via Signed URL

**Task:** `GET /reservations/{reservation}/cancel?signature=…` → `App\Http\Controllers\ReservationCancellationController@cancel`

**Logic:**
1. Verify `cancellation_token` matches URL token parameter and reservation is confirmed.
2. Set status to `cancelled`, `cancelled_at = now()`, null out `cancellation_token`.
3. Release table slot.
4. Dispatch `ReservationCancelled` event → emails (Phase 4).

**Tests (`tests/Feature/Reservations/GuestCancellationTest.php`):**
- Valid token cancels reservation
- Invalid/expired token returns 403
- Already-cancelled reservation returns appropriate message
- Pending reservation (not yet confirmed) cannot be cancelled via this link
- Tables are released on successful cancellation

**Status:** ✅ Completed

---

## Phase 4: Email Notifications

> All emails are queued jobs. Use Laravel Mail with `ShouldQueue`.

### Phase 4.1: Guest Acknowledgement Email

**Mailable:** `App\Mail\ReservationAcknowledgement`

Sent to guest on submission. Includes: name, date, time, guest count, reference number, "pending staff review" note.

**Listener:** `SendAcknowledgementEmail` on `ReservationCreated` event.

**Tests (`tests/Feature/Mail/AcknowledgementEmailTest.php`):**
- Email is queued when reservation is created
- Email contains reference number
- Email is sent to the guest's address

**Status:** ✅ Completed

---

### Phase 4.2: Staff Notification Email with Deep-Links

**Mailable:** `App\Mail\StaffReservationNotification`

Sent to all users with `staff` or `manager` role on `ReservationCreated`. Includes: guest details, date/time/guests, reference number, **Confirm** deep-link (`temporarySignedRoute`, 72h expiry), **Reject** deep-link.

**Tests (`tests/Feature/Mail/StaffNotificationEmailTest.php`):**
- Email queued to all staff/manager users on new reservation
- Email contains a valid signed confirm URL
- Email contains a valid signed reject URL
- Deep-link URLs are different for each reservation

**Status:** ✅ Completed

---

### Phase 4.3: Guest Confirmation Email

**Mailable:** `App\Mail\ReservationConfirmation`

Sent to guest when reservation is confirmed. Includes: confirmed date/time/guests, reference number, **cancellation link** (signed URL with token).

**Admin BCC:** config-driven email address receives a copy.

**Listener:** `SendConfirmationEmail` on `ReservationConfirmed` event.

**Tests (`tests/Feature/Mail/ConfirmationEmailTest.php`):**
- Email queued when reservation is confirmed
- Email contains cancellation link with valid token
- Admin BCC address is included

**Status:** ✅ Completed

---

### Phase 4.4: Guest Rejection Email

**Mailable:** `App\Mail\ReservationRejection`

Sent to guest when rejected. Includes: original reservation details and rejection reason.

**Listener:** `SendRejectionEmail` on `ReservationRejected` event.

**Tests (`tests/Feature/Mail/RejectionEmailTest.php`):**
- Email queued when reservation is rejected
- Email contains the rejection reason text

**Status:** ✅ Completed

---

### Phase 4.5: Guest Cancellation Notification

**Mailable:** `App\Mail\ReservationCancelled`

Sent to guest on cancellation (Phase 3.5). Staff also receives a brief notification.

**Tests (`tests/Feature/Mail/CancellationEmailTest.php`):**
- Guest receives cancellation acknowledgement
- Staff receives cancellation notification
- Emails are queued, not sent synchronously

**Status:** ✅ Completed

---

## Phase 5: Admin Panel — Reservation Management

> All admin controllers live under `App\Http\Controllers\Admin\`. Routes are prefixed `/admin` with `['auth', 'verified', 'role:manager|staff']` middleware group.

### Phase 5.1: Admin Layout & Dashboard

**Task:**
- Admin layout Vue component (`resources/js/layouts/AdminLayout.vue`) with sidebar navigation.
- `GET /admin` → `App\Http\Controllers\Admin\DashboardController@index`
- Dashboard shows: count of pending reservations, today's confirmed reservations list, tomorrow's confirmed reservations list.

**Tests (`tests/Feature/Admin/DashboardTest.php`):**
- Manager can access `/admin`
- Staff can access `/admin`
- Customer cannot access `/admin` (403)
- Unauthenticated user is redirected to login
- Dashboard shows correct count of today's/tomorrow's confirmed reservations

**Status:** ✅ Completed

---

### Phase 5.2: Pending Reservations List

**Task:** `GET /admin/reservations/pending` → `App\Http\Controllers\Admin\ReservationController@pending`

Lists all `status = pending` reservations, sorted by `created_at` ascending. Each row has Confirm and Reject action buttons.

**Tests (`tests/Feature/Admin/PendingReservationsTest.php`):**
- Returns only pending reservations
- Rows sorted oldest first
- Staff can view this page
- Customer cannot view this page

**Status:** ✅ Completed

---

### Phase 5.3: All Reservations DataTable

**Task:** `GET /admin/reservations` → `App\Http\Controllers\Admin\ReservationController@index`

Uses `spatie/laravel-query-builder`. Supports:
- Filter by `status`
- Filter by date range (`reservation_date`)
- Search by `first_name`, `last_name`, `email`, `reference_number`
- Sort by `reservation_date`, `created_at`, `guest_count`
- Pagination

**Tests (`tests/Feature/Admin/ReservationsIndexTest.php`):**
- Returns paginated list of all reservations
- Filter by status returns only matching records
- Date range filter works
- Search by email returns correct record
- Sort by date orders results correctly
- Staff and manager can access; customers cannot

**Status:** ✅ Completed

---

### Phase 5.4: Confirm Reservation

**Task (two entry points):**

A. `PATCH /admin/reservations/{reservation}/confirm` → `ReservationController@confirm`

B. `GET /reservations/{reservation}/confirm?signature=…` → `ReservationDeepLinkController@confirm` (signed route, no login required)

**Shared logic (extracted to `ReservationConfirmationService`):**
- Status must be `pending`; idempotent if already confirmed
- Set `status = confirmed`, `confirmed_by`, `confirmed_at`
- Dispatch `ReservationConfirmed` event

**Tests (`tests/Feature/Admin/ConfirmReservationTest.php`):**
- Staff can confirm a pending reservation
- Status changes to confirmed
- `confirmed_by` is set to acting user
- Email event is dispatched
- Cannot confirm an already-confirmed reservation
- Cannot confirm a rejected/cancelled reservation
- Unsigned or expired deep-link returns 403
- Valid deep-link confirms without requiring login

**Status:** ✅ Completed

---

### Phase 5.5: Reject Reservation

**Task (two entry points):**

A. `PATCH /admin/reservations/{reservation}/reject` → `ReservationController@reject` (requires `rejection_reason` in body)

B. `GET /reservations/{reservation}/reject?signature=…` → `ReservationDeepLinkController@reject` (signed route, loads a rejection reason form page)
   `POST /reservations/{reservation}/reject` → submits reason, performs rejection

**Logic:** Set `status = rejected`, `rejected_by`, `rejected_at`, `rejection_reason`. Dispatch `ReservationRejected` event.

**Tests (`tests/Feature/Admin/RejectReservationTest.php`):**
- Staff can reject a pending reservation with a reason
- `rejection_reason` is required (missing → 422)
- Status changes to rejected
- Email event dispatched with reason
- Deep-link without login loads rejection form
- Deep-link rejects reservation when form submitted with reason
- Unsigned deep-link returns 403
- Cannot reject an already-confirmed reservation

**Status:** ✅ Completed

---

### Phase 5.6: Create Reservation (Admin)

**Task:** `GET /admin/reservations/create` + `POST /admin/reservations` → `ReservationController@create` / `store`

Same validation as public form. Staff can set status directly to `confirmed` or leave as `pending`. Skips honeypot. If confirmed immediately, dispatches `ReservationConfirmed`.

**Tests (`tests/Feature/Admin/AdminCreateReservationTest.php`):**
- Staff can create a pending reservation
- Staff can create a confirmed reservation directly
- Table availability is validated
- Confirmation email dispatched when created as confirmed
- Acknowledgement email dispatched when created as pending

**Status:** ✅ Completed

---

### Phase 5.7: Edit Reservation

**Task:** `GET /admin/reservations/{reservation}/edit` + `PATCH /admin/reservations/{reservation}` → `ReservationController@edit` / `update`

Editable fields: guest details, date, time, guest count, status. Re-runs availability check if date/time/guests change. Releases old slot, assigns new.

**Tests (`tests/Feature/Admin/EditReservationTest.php`):**
- Staff can update guest details
- Changing date/time re-validates availability
- Changing date/time reassigns tables
- Status can be changed (pending → confirmed triggers email)
- Manager can edit; staff can edit; customer cannot

**Status:** ✅ Completed

---

### Phase 5.8: Delete Reservation

**Task:** `DELETE /admin/reservations/{reservation}` → `ReservationController@destroy`

Manager-only (`can:reservations.delete` gate). Shows confirmation dialog in UI. Releases table assignment.

**Tests (`tests/Feature/Admin/DeleteReservationTest.php`):**
- Manager can delete a reservation
- Staff receives 403 when attempting delete
- Deleted reservation's tables are released

**Status:** ✅ Completed

---

## Phase 6: Floor & Table Management (Admin)

> Manager-only. Routes under `/admin/tables` with `can:tables.manage` middleware.

### Phase 6.1: Restaurant Tables CRUD

**Task:** `App\Http\Controllers\Admin\RestaurantTableController` (full resource)

- `GET /admin/tables` — list all tables with capacity, active status
- `GET /admin/tables/create` + `POST /admin/tables` — create table
- `GET /admin/tables/{table}/edit` + `PATCH /admin/tables/{table}` — update
- `DELETE /admin/tables/{table}` — delete (blocked if table has upcoming confirmed reservations)

**Tests (`tests/Feature/Admin/RestaurantTableCrudTest.php`):**
- Manager can create/edit/delete tables
- Staff cannot access table management (403)
- Cannot delete a table with upcoming confirmed reservations
- Deactivating a table (`is_active = false`) hides it from availability
- Name and capacity are required on create

**Status:** ✅ Completed

---

### Phase 6.2: Table Joining Groups CRUD

**Task:** `App\Http\Controllers\Admin\TableJoiningGroupController` (full resource)

- List all groups with their member tables and `min_guests`
- Create/edit: select tables (multi-select from active tables), set `min_guests`, optional `name`
- Delete group (also removes pivot records; restrictions cascade-deleted)
- Validate: a table may not belong to more than one group simultaneously

**Tests (`tests/Feature/Admin/TableJoiningGroupCrudTest.php`):**
- Manager can create a joining group with selected tables
- `min_guests` is required and positive
- A table already in another group cannot be added (validation error)
- Deleting a group removes pivot entries and restrictions
- Staff cannot access (403)

**Status:** ✅ Completed

---

### Phase 6.3: Joining Group Restrictions

**Task:** `App\Http\Controllers\Admin\JoiningGroupRestrictionController`

Nested under a joining group. Manager can add/remove restrictions (day of week + time range when the group is unavailable).

`GET /admin/tables/groups/{group}/restrictions` — list
`POST /admin/tables/groups/{group}/restrictions` — add restriction
`DELETE /admin/tables/groups/{group}/restrictions/{restriction}` — remove

**Tests (`tests/Feature/Admin/JoiningGroupRestrictionTest.php`):**
- Restriction can be added to a group for a specific day/time
- Restriction with `day_of_week = null` applies every day
- Duplicate restriction (same day + overlapping time) returns validation error
- `start_time` must be before `end_time`
- `AvailabilityService` excludes group during restricted window (integration test)

**Status:** ✅ Completed

---

## Phase 7: Operating Hours Management

### Phase 7.1: Operating Hours Configuration Page

**Task:** `GET /admin/settings/operating-hours` + `PATCH /admin/settings/operating-hours` → `App\Http\Controllers\Admin\OperatingHoursController`

UI: table with 7 rows (Mon–Sun), toggle is_closed, time pickers for open/close times.

**Tests (`tests/Feature/Admin/OperatingHoursTest.php`):**
- Manager can update hours for a day
- Marking a day closed sets `is_closed = true`
- `open_time` must be before `close_time`
- Changes immediately affect the public availability calendar (integration test)
- Staff cannot access (403)

**Status:** ✅ Completed

---

## Phase 8: Staff Account Management

### Phase 8.1: Staff Account CRUD (Manager Only)

**Task:** `App\Http\Controllers\Admin\StaffController`

- `GET /admin/staff` — list all staff/manager users with name, email, role, active status
- `GET /admin/staff/create` + `POST /admin/staff` — create staff account (name, email, password, role: staff/manager)
- `PATCH /admin/staff/{user}/toggle-active` — deactivate/reactivate account (soft approach: set a `is_active` boolean or revoke all roles + remove permissions)
- Staff cannot access this section

**Tests (`tests/Feature/Admin/StaffManagementTest.php`):**
- Manager can create a staff user with `staff` role
- Manager can create another manager
- Deactivated user cannot log in
- Staff cannot access `/admin/staff` (403)
- Created staff can log in and access admin panel
- Creating a staff user with a duplicate email returns validation error

**Status:** ✅ Completed

---

## Phase 9: Customer Account Features

### Phase 9.1: Customer Dashboard & Reservation History

**Task:** `GET /dashboard` → extend existing `Dashboard.vue` (already exists, currently blank)

Controller: `App\Http\Controllers\DashboardController@index` (update existing)

- Lists all reservations associated with `auth()->user()->email` (match even un-linked reservations by email)
- Shows: date, time, guest count, status badge
- Upcoming confirmed reservations show Cancel link
- Sorted by `reservation_date` descending

**Tests (`tests/Feature/Customer/CustomerDashboardTest.php`):**
- Authenticated customer sees their reservations
- Reservations are matched by email even if created before account was made
- Confirmed upcoming reservations show cancellation link
- Past/cancelled/rejected reservations do not show cancellation link
- Another customer's reservations are not visible

**Status:** ✅ Completed

---

## Phase 10: Real-Time Availability (Laravel Reverb)

### Phase 10.1: Broadcast Availability Updates

**Task:** When a reservation is confirmed or cancelled, broadcast an `AvailabilityUpdated` event on the `availability` channel.

**Frontend:** `resources/js/pages/Booking.vue` subscribes via `laravel-echo` + Reverb. When event received, re-fetches availability for the currently selected date/time/guests.

**Tests (`tests/Feature/Broadcasting/AvailabilityBroadcastTest.php`):**
- `AvailabilityUpdated` event is broadcast on reservation confirmed
- `AvailabilityUpdated` event is broadcast on reservation cancelled
- Event is on the correct public channel

**Status:** ✅ Completed

---

## Summary Table

| Phase | Description | Status |
|-------|-------------|--------|
| — | Starter kit: auth, settings, migrations | ✅ Completed |
| 1.1 | Roles & permissions setup | ✅ Completed |
| 1.2 | `restaurant_tables` model/migration/factory | ✅ Completed |
| 1.3 | `table_joining_groups` + pivot | ✅ Completed |
| 1.4 | `joining_group_restrictions` | ✅ Completed |
| 1.5 | `operating_hours` + seeder | ✅ Completed |
| 1.6 | `reservations` model/migration/factory + `ReservationStatus` enum | ✅ Completed |
| 1.7 | `reservation_restaurant_table` pivot | ✅ Completed |
| 1.8 | `activity_log` table (activitylog package) | ✅ Completed |
| 2.1 | `ReservationStatus` Enum | ✅ Completed |
| 2.2 | `AvailabilityService` | ✅ Completed |
| 2.3 | `TableAssignmentService` | ✅ Completed |
| 2.4 | Reference number generator | ✅ Completed |
| 3.1 | Availability API endpoint | ✅ Completed |
| 3.2 | Public booking page (Vue) | ✅ Completed |
| 3.3 | Reservation submission + rate limiting | ✅ Completed |
| 3.4 | Reservation success page | ✅ Completed |
| 3.5 | Guest cancellation via signed URL | ✅ Completed |
| 4.1 | Guest acknowledgement email | ✅ Completed |
| 4.2 | Staff notification email with deep-links | ✅ Completed |
| 4.3 | Guest confirmation email | ✅ Completed |
| 4.4 | Guest rejection email | ✅ Completed |
| 4.5 | Guest cancellation notification | ✅ Completed |
| 5.1 | Admin layout & dashboard | ✅ Completed |
| 5.2 | Pending reservations list | ✅ Completed |
| 5.3 | All reservations DataTable | ✅ Completed |
| 5.4 | Confirm reservation | ✅ Completed |
| 5.5 | Reject reservation | ✅ Completed |
| 5.6 | Create reservation (admin) | ✅ Completed |
| 5.7 | Edit reservation | ✅ Completed |
| 5.8 | Delete reservation (manager only) | ✅ Completed |
| 6.1 | Restaurant tables CRUD | ✅ Completed |
| 6.2 | Table joining groups CRUD | ✅ Completed |
| 6.3 | Joining group restrictions | ✅ Completed |
| 7.1 | Operating hours management | ✅ Completed |
| 8.1 | Staff account management | ✅ Completed |
| 9.1 | Customer dashboard & reservation history | ✅ Completed |
| 10.1 | Real-time availability (Reverb broadcast) | ✅ Completed |
