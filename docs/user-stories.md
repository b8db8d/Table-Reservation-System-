# User Stories — Table Reservation System

## Overview

This document contains user stories for a restaurant Table Reservation System that allows customers to book tables online and staff to manage reservations through an admin panel.

**User Types:**
- **Guest** — Unauthenticated visitor making or viewing a reservation
- **Customer** — Optionally registered user with booking history access
- **Staff** — Restaurant employee who can confirm/reject reservations
- **Manager** — Full-access admin who manages floors, tables, and staff

---

## 1. Public Booking Flow

### US-1.1: Browse Availability
**As a** Guest or Customer
**I want to** view available tables for a specific date and time
**So that** I can find a suitable slot before committing to a reservation

**Acceptance Criteria:**
- [ ] Interactive calendar allows selecting any future date
- [ ] After selecting a date, user picks any time within operating hours
- [ ] System displays available table capacity for the selected slot (e.g., "5 tables for 2 people, 2 tables for 4 people")
- [ ] Past dates and times are not selectable
- [ ] Availability updates dynamically when date/time changes
- [ ] Joined table combinations are shown when applicable (e.g., "1 combined table for 6–8 people")

**Expected Result:** Guest sees real-time availability for their chosen date and time.

---

### US-1.2: Submit a Reservation Request
**As a** Guest or Customer
**I want to** submit a reservation request with my contact details
**So that** the restaurant can confirm my booking

**Acceptance Criteria:**
- [ ] Reservation form collects:
  - First and Last Name
  - Email address
  - Phone number
  - Number of guests
- [ ] Guest count is validated against available capacity
- [ ] System automatically applies table joining logic when guest count requires it (e.g., combining two 4-person tables for 6+ guests)
- [ ] If no table or combination can accommodate the group, an error is shown and submission is blocked
- [ ] On successful submission, reservation is created with status *Pending*
- [ ] Guest sees a confirmation message that their request was received
- [ ] Acknowledgment email is sent to the guest immediately

**Expected Result:** Reservation request is created in *Pending* status and guest is notified.

---

### US-1.3: Receive Reservation Acknowledgement Email
**As a** Guest
**I want to** receive an email confirming my request was received
**So that** I know the restaurant has my booking details

**Acceptance Criteria:**
- [ ] Email sent immediately after form submission
- [ ] Email includes:
  - Guest name
  - Date, time, and number of guests
  - Reservation reference number
  - Note that confirmation is pending staff review
- [ ] Email is sent to the address provided in the form

**Expected Result:** Guest receives acknowledgement email with reservation details.

---

### US-1.4: Receive Reservation Confirmation Email
**As a** Guest
**I want to** receive a confirmation email once staff approve my reservation
**So that** I have a final record of my booking

**Acceptance Criteria:**
- [ ] Email sent when staff confirms the reservation
- [ ] Email includes:
  - Confirmed date, time, and number of guests
  - Reservation reference number
  - Link to cancel the reservation
- [ ] Cancellation link is unique per reservation and does not require login
- [ ] Admin receives a BCC or separate copy of the confirmation email for archival

**Expected Result:** Guest receives confirmation email with a cancellation link.

---

### US-1.5: Receive Reservation Rejection Email
**As a** Guest
**I want to** be notified if my reservation is rejected
**So that** I can make alternative arrangements

**Acceptance Criteria:**
- [ ] Email sent when staff rejects the reservation
- [ ] Email includes:
  - Rejection reason provided by staff
  - Original reservation details (date, time, guests)
- [ ] Reservation status is updated to *Rejected*

**Expected Result:** Guest is informed of the rejection and the reason provided.

---

### US-1.6: Cancel a Reservation
**As a** Guest or Customer
**I want to** cancel my confirmed reservation via the link in my confirmation email
**So that** I can free up the table if my plans change

**Acceptance Criteria:**
- [ ] Cancellation link in confirmation email is unique and valid per reservation
- [ ] Clicking the link immediately cancels the reservation in the system
- [ ] Cancellation generates a notification email to staff
- [ ] Guest receives a cancellation acknowledgement email
- [ ] Cancelled time slot becomes available for new bookings
- [ ] Reservation status changes to *Cancelled*

**Expected Result:** Reservation is cancelled, staff is notified, and the slot is reopened.

---

## 2. Customer Accounts (Optional)

### US-2.1: Register an Account
**As a** Guest
**I want to** optionally create an account
**So that** I can view my reservation history in one place

**Acceptance Criteria:**
- [ ] Registration is optional — guests can book without an account
- [ ] Registration form collects: name, email, password, password confirmation
- [ ] Email must be unique in the system
- [ ] Password must meet minimum security requirements (8+ characters)
- [ ] After registration, user is redirected to their dashboard

**Expected Result:** Customer account is created and user can access their booking history.

---

### US-2.2: Log In to Account
**As a** Customer
**I want to** log in to my account
**So that** I can view my reservations

**Acceptance Criteria:**
- [ ] Login form accepts email and password
- [ ] Invalid credentials show an appropriate error message
- [ ] Successful login redirects to customer dashboard

**Expected Result:** Customer is authenticated and redirected to their dashboard.

---

### US-2.3: Reset Password
**As a** Customer
**I want to** reset my password if I forget it
**So that** I can regain access to my account

**Acceptance Criteria:**
- [ ] "Forgot password" link available on login page
- [ ] User enters email address to receive a reset link
- [ ] Password reset link is valid for 60 minutes
- [ ] User sets a new password via the reset link
- [ ] Confirmation message is shown after successful reset

**Expected Result:** Customer receives reset email and can set a new password.

---

### US-2.4: View Reservation History
**As a** Customer
**I want to** view all my past and upcoming reservations
**So that** I have a record of my bookings

**Acceptance Criteria:**
- [ ] Dashboard lists all reservations associated with the account's email
- [ ] Each entry shows: date, time, guest count, status (Pending, Confirmed, Rejected, Cancelled)
- [ ] Upcoming confirmed reservations show the cancellation link
- [ ] Reservations are sorted by date (newest first)

**Expected Result:** Customer can see their complete reservation history.

---

## 3. Staff Operations

### US-3.1: Receive New Reservation Notification
**As a** Staff member
**I want to** receive an email when a new reservation request is submitted
**So that** I can promptly review and act on it

**Acceptance Criteria:**
- [ ] Email is sent to staff immediately upon form submission
- [ ] Email includes:
  - Guest name, email, and phone number
  - Date, time, and number of guests
  - Reservation reference number
- [ ] Email contains two action deep-links:
  - **Confirm** — directly confirms the reservation
  - **Reject** — redirects to a form for entering a rejection reason
- [ ] Deep-links are authenticated and cannot be used by unauthorized parties

**Expected Result:** Staff receives an actionable notification for each new reservation.

---

### US-3.2: Confirm a Reservation
**As a** Staff member
**I want to** confirm a pending reservation
**So that** the table is blocked and the guest is notified

**Acceptance Criteria:**
- [ ] Staff can confirm via the deep-link in the notification email
- [ ] Staff can also confirm from the admin reservation list
- [ ] On confirmation:
  - Reservation status changes to *Confirmed*
  - Time slot is blocked in the system for the reserved table(s)
  - Confirmation email sent to the guest (with cancellation link)
  - BCC/copy email sent to admin
- [ ] Already-confirmed reservations cannot be confirmed again

**Expected Result:** Reservation is confirmed, slot is blocked, and all parties are notified.

---

### US-3.3: Reject a Reservation
**As a** Staff member
**I want to** reject a pending reservation with a reason
**So that** the guest is informed and the slot remains available

**Acceptance Criteria:**
- [ ] Staff can reject via the deep-link in the notification email
- [ ] Rejection link opens a form/modal to enter a rejection reason
- [ ] Rejection reason is required before submitting
- [ ] On rejection:
  - Reservation status changes to *Rejected*
  - Rejection email with reason sent to the guest
  - Time slot remains available for new bookings
- [ ] Staff can also reject from the admin reservation list

**Expected Result:** Reservation is rejected, guest is notified with the reason.

---

### US-3.4: View Pending Reservations
**As a** Staff member
**I want to** see a list of reservations awaiting confirmation
**So that** I can process them in a timely manner

**Acceptance Criteria:**
- [ ] Dedicated "Pending" list in the admin panel
- [ ] Each row shows: guest name, date, time, guest count, submission time
- [ ] Rows are sorted by submission time (oldest first)
- [ ] Confirm and Reject actions available inline or via detail view
- [ ] Count of pending requests visible in dashboard overview

**Expected Result:** Staff can quickly identify and act on unprocessed reservation requests.

---

### US-3.5: View Today's and Tomorrow's Reservations
**As a** Staff member
**I want to** see a quick overview of today's and tomorrow's confirmed bookings
**So that** I can prepare the restaurant floor accordingly

**Acceptance Criteria:**
- [ ] Admin dashboard shows two sections: "Today's Reservations" and "Tomorrow's Reservations"
- [ ] Each entry shows: guest name, time, guest count, table(s) assigned
- [ ] Only *Confirmed* reservations are listed
- [ ] Counts are displayed as a summary (e.g., "8 reservations today")

**Expected Result:** Staff can plan daily operations from a single dashboard view.

---

## 4. Reservation Management (Admin Panel)

### US-4.1: View All Reservations
**As a** Staff member or Manager
**I want to** view a full list of all reservations
**So that** I can monitor and manage booking activity

**Acceptance Criteria:**
- [ ] Data table lists all reservations across all dates
- [ ] Columns: reference number, guest name, date, time, guest count, status, submitted at
- [ ] Filterable by status (Pending, Confirmed, Rejected, Cancelled)
- [ ] Filterable by date range
- [ ] Searchable by guest name, email, or reference number
- [ ] Status displayed as a color-coded Badge (e.g., yellow = Pending, green = Confirmed)
- [ ] Pagination for large datasets

**Expected Result:** Full reservation history is accessible and filterable.

---

### US-4.2: Create a Reservation (Admin)
**As a** Staff member or Manager
**I want to** manually create a reservation from the admin panel
**So that** I can handle phone or walk-in bookings

**Acceptance Criteria:**
- [ ] Form mirrors the public booking form fields
- [ ] Staff can set reservation status directly (Pending or Confirmed)
- [ ] Table availability is validated the same way as the public form
- [ ] Confirmation email sent to the guest if status is set to Confirmed

**Expected Result:** Staff can add reservations on behalf of guests without using the public form.

---

### US-4.3: Edit a Reservation
**As a** Staff member or Manager
**I want to** edit an existing reservation
**So that** I can correct mistakes or accommodate guest requests

**Acceptance Criteria:**
- [ ] Editable fields: guest name, email, phone, date, time, guest count
- [ ] Availability is re-validated on save if date/time/guest count changes
- [ ] Status can be changed (e.g., Pending → Confirmed)
- [ ] Changes are saved and reflected immediately
- [ ] If date/time changes, previously blocked slot is released and new slot is blocked

**Expected Result:** Reservation details can be updated while keeping the system consistent.

---

### US-4.4: Delete a Reservation
**As a** Manager
**I want to** delete a reservation record
**So that** I can remove erroneous or test entries

**Acceptance Criteria:**
- [ ] Delete action available in the reservation detail view
- [ ] Confirmation dialog shown before deletion
- [ ] Deleted reservation's time slot is released
- [ ] Only Managers can delete reservations (Staff cannot)

**Expected Result:** Reservation is permanently removed and the slot is freed.

---

## 5. Floor & Table Management

### US-5.1: Define Tables
**As a** Manager
**I want to** define the restaurant's tables and their base capacities
**So that** the booking system knows what is available

**Acceptance Criteria:**
- [ ] Manager can add tables with: table identifier (e.g., "Table 1", "T-A"), base seating capacity
- [ ] Manager can edit table capacity
- [ ] Manager can remove a table (only if it has no upcoming confirmed reservations)
- [ ] Changes are reflected in availability calculations immediately

**Expected Result:** The system has an accurate representation of the restaurant's seating.

---

### US-5.2: Configure Table Joining Rules
**As a** Manager
**I want to** specify which tables can be joined and the minimum guest count that triggers joining
**So that** larger groups can be accommodated automatically

**Acceptance Criteria:**
- [ ] Manager selects two or more tables to form a joinable group
- [ ] Manager sets the minimum guest count that activates the joining rule (e.g., 6+ guests to join two 4-person tables)
- [ ] There is option for manager to select days and hours when specific joining is unavaliable for guests to book (eg. joining table for 15 people on friday, start at 7pm. ). 
- [ ] Multiple joining groups can be configured
- [ ] A table can belong to only one joining group at a time
- [ ] Joining rules are used automatically during availability checks

**Expected Result:** The system can correctly suggest joined table combinations for large groups. 

---

### US-5.3: Availability Calculation with Joining Logic
**As the** System
**I want to** correctly calculate table availability including joining rules
**So that** guests are only shown slots where they can actually be seated

**Acceptance Criteria:**
- [ ] First, check individual tables with sufficient capacity
- [ ] If no single table fits, check configured joining groups where:
  - All tables in the group are free for the requested slot
  - Guest count meets or exceeds the joining threshold
  - Combined capacity is sufficient for the guest count
- [ ] A slot is shown as unavailable if neither individual nor joined tables can fit the group
- [ ] Joined tables are booked as a unit — no partial joining

**Expected Result:** Availability shown to guests is always accurate and respects joining rules.

---

## 6. Email Notifications Summary

### US-6.1: Staff New Request Email
**As** Staff
**I want to** receive a notification email for each new reservation request with confirm/reject links
*(See US-3.1 for full acceptance criteria)*

---

### US-6.2: Guest Acknowledgement Email
**As a** Guest
**I want to** receive an acknowledgement that my request was received
*(See US-1.3 for full acceptance criteria)*

---

### US-6.3: Guest Confirmation Email
**As a** Guest
**I want to** receive a confirmation email with a cancellation link
*(See US-1.4 for full acceptance criteria)*

---

### US-6.4: Guest Rejection Email
**As a** Guest
**I want to** receive a rejection email with the staff's reason
*(See US-1.5 for full acceptance criteria)*

---

### US-6.5: Guest Cancellation Notification
**As a** Guest
**I want to** receive a cancellation acknowledgement after I cancel
*(See US-1.6 for full acceptance criteria)*

---

## 7. Manager Administration

### US-7.1: Manage Staff Accounts
**As a** Manager
**I want to** create and manage Staff user accounts
**So that** I can control who has access to the admin panel

**Acceptance Criteria:**
- [ ] Manager can create Staff accounts (name, email, password)
- [ ] Manager can deactivate Staff accounts
- [ ] Staff accounts have limited permissions (cannot delete reservations, cannot manage tables/staff)
- [ ] Manager account is the only role with full access

**Expected Result:** Manager controls who can access and operate the admin panel.

---

### US-7.2: Configure Operating Hours
**As a** Manager
**I want to** set the restaurant's operating hours
**So that** guests can only book within valid time windows

**Acceptance Criteria:**
- [ ] Manager sets opening and closing time per day of week
- [ ] Manager can mark specific days as closed
- [ ] Public booking calendar enforces operating hours (times outside range are not selectable)
- [ ] Changes take effect immediately

**Expected Result:** Guests can only select booking times within the configured operating hours.

---

## Appendix: User Story Status

| ID | Story | Priority | Status |
|----|-------|----------|--------|
| US-1.1 | Browse Availability | High | Pending |
| US-1.2 | Submit Reservation Request | High | Pending |
| US-1.3 | Acknowledgement Email | High | Pending |
| US-1.4 | Confirmation Email | High | Pending |
| US-1.5 | Rejection Email | High | Pending |
| US-1.6 | Cancel Reservation (Guest) | High | Pending |
| US-2.1 | Register an Account | Low | Pending |
| US-2.2 | Log In to Account | Low | Pending |
| US-2.3 | Reset Password | Low | Pending |
| US-2.4 | View Reservation History | Low | Pending |
| US-3.1 | Staff New Request Notification | High | Pending |
| US-3.2 | Confirm a Reservation | High | Pending |
| US-3.3 | Reject a Reservation | High | Pending |
| US-3.4 | View Pending Reservations | High | Pending |
| US-3.5 | Today's & Tomorrow's Overview | Medium | Pending |
| US-4.1 | View All Reservations | Medium | Pending |
| US-4.2 | Create Reservation (Admin) | Medium | Pending |
| US-4.3 | Edit a Reservation | Medium | Pending |
| US-4.4 | Delete a Reservation | Low | Pending |
| US-5.1 | Define Tables | High | Pending |
| US-5.2 | Configure Joining Rules | High | Pending |
| US-5.3 | Availability with Joining Logic | High | Pending |
| US-6.1 | Staff New Request Email | High | Pending |
| US-6.2 | Guest Acknowledgement Email | High | Pending |
| US-6.3 | Guest Confirmation Email | High | Pending |
| US-6.4 | Guest Rejection Email | High | Pending |
| US-6.5 | Guest Cancellation Notification | Medium | Pending |
| US-7.1 | Manage Staff Accounts | Medium | Pending |
| US-7.2 | Configure Operating Hours | Medium | Pending |
