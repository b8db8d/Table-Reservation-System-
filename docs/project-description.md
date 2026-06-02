# Functional and technical requirements for the **Table Reservation System** project.

---

## 1. Technical Stack
* **Backend:** Laravel 13 (Starter Kit).
* **Frontend:** Vue.js (Inertia.js), Tailwind CSS 4.
* **Database:** MySQL.
* **UI Components:** shadcn (vue).
* **Packages:** spatie/laravel-permission, propaganistas/laravel-phone, spatie/laravel-honeypot, spatie/laravel-activitylog, spatie/laravel-query-builder, laravel/reverb.

---

## 2. System Architecture and Views

### A. Customer View (Public)
The primary goal is to provide an intuitive reservation process.
* **Interactive Calendar:** For selecting date and time.
* **Availability Status:** Dynamic display of available tables and their capacities (e.g., 5 tables for 2 people).
* **Reservation Form:**
    * First and Last Name.
    * Email address and Phone number.
    * Number of guests (with capacity validation).
* **Table Joining Logic:** Automatic availability recalculation if the number of guests requires joining predefined units (e.g., combining two 4-person tables for a reservation of 6+ people).

### B. Admin Panel
The central hub for managing restaurant operations.
* **Dashboard (Main Page):**
    * Quick overview: "Today’s Reservations" and "Tomorrow’s Reservations".
* **Reservation Management:**
    * Full CRUD (Create, Read, Update, Delete) for reservations.
    * List of pending requests awaiting confirmation.
* **Floor Management:**
    * Defining the number of tables and their base capacity.
    * **Connection Configuration:** Manually specifying which tables can be joined and the minimum guest threshold required to trigger this rule.

---

## 3. Business Logic and Workflow

### Confirmation Process
The system follows a two-step verification process:
1.  **Request Submission:** Client fills out the form. Status: *Pending*.
2.  **Staff Notification:** An email is sent to the staff containing the details and two action links:
    * **Confirm:** Changes status to *Confirmed*, blocks the time slot in the database, and sends confirmation emails to both the client and the admin.
    * **Reject:** Redirects the admin to a form for providing a reason. After submission, the system sends a rejection email to the client.

### Automatic Table Joining Rules
The mechanism must verify:
* Are there individual tables available with the required capacity?
* If not, are the predefined "table pairs/groups" available for that time slot?
* Does the guest count exceed the threshold that activates the joining rule (e.g., min. 6 people to join two 4-person tables)?

---

## 4. Communication Requirements (Email)
* **To Staff:** New request notification (including Deep Links for Confirm/Reject actions).
* **To Client:**
    * Acknowledgment of request receipt.
    * Final confirmation (after admin action) plus link to cancel reservation (link should generate email to staff and cancel the reservation in system).
    * Rejection notice including the provided reason.
* **To Admin:** A BCC or separate copy of the final confirmation for archival purposes.

---

## 5. Key UI Components (shadcn)
* `Calendar` – Date selection.
* `Form` & `Input` – Contact data entry.
* `Dialog` / `Modal` – Rejection reason form.
* `DataTable` – Admin reservation lists.
* `Badge` – Status indicators (Pending, Confirmed, Rejected).

---
**Sources:** Project requirements provided by the user.