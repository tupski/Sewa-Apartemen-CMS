---
name: booking
description: >-
  Use when working on the booking flow: creating bookings, booking statuses,
  guest status lookup, applying vouchers at booking time, the booking form, or
  the admin bookings screens. Trigger phrases: "create a booking", "booking
  status", "confirm/cancel booking", "booking form", "guest lookup", "apply
  voucher to booking", "booking total". STRICTEST skill — booking code is
  business-critical and transactional.
---

# Purpose
Booking creation is the most sensitive flow in the app: it is transactional,
snapshots pricing, reserves voucher codes under a lock, and generates a unique
booking code. This skill exists to stop agents from duplicating that logic or
adding features (like availability checks) that do not exist.

# When to Use
- Any change touching booking creation, status, vouchers-at-booking, or lookup.
- Editing the booking form or the admin bookings index/show views.
- Adding validation to booking input.

# Rules
- `BookingService::create()` ([`app/Services/BookingService.php`](app/Services/BookingService.php))
  is the SOLE booking creator. It runs inside `DB::transaction`, snapshots the
  price breakdown, resolves the voucher by CODE with `lockForUpdate`, increments
  `used_count`, and generates the code. Route ALL booking creation through it.
- Booking totals come from `BookingPricingService::calculate()` ONLY. Never compute
  a total in the controller, the view, or JS. Client-side price display is a
  preview; the server is authoritative.
- Statuses are exactly `pending`, `confirmed`, `cancelled`, `completed` (default
  `pending`) — the `bookings.status` enum. Do not add or rename statuses without a
  migration + user confirmation. Use `BookingService::confirm()/cancel()/complete()`
  for transitions (they fire notifications).
- Booking codes are `BK-YYYYMMDD-XXXX`, generated transactionally with
  `lockForUpdate` via `BookingService::generateCode()`. Do not generate codes
  elsewhere.
- Guest status lookup uses the unique `bookings.access_token` column. Do not expose
  bookings by sequential `id` (IDOR risk).
- There is NO server-side availability/conflict-checking system. Do NOT add one —
  it is a business-rule change requiring an audit and explicit user confirmation.
- Validate booking input via [`BookingRequest`](app/Http/Requests/BookingRequest.php).
- There is NO Payment model/gateway; `total_price` and `deposit_amount` are stored
  on the booking and "payment status" equals booking status.

# Workflow
1. Read [`app/Services/BookingService.php`](app/Services/BookingService.php) fully
   before any change to understand the transaction and voucher lock.
2. For a new field, add a migration, update `Booking` `$fillable`/`$casts` and
   [`BookingRequest`](app/Http/Requests/BookingRequest.php).
3. Keep pricing delegated to `BookingPricingService`; keep voucher discount in
   `Voucher::calculateDiscount()`.
4. For status changes, call the service transition methods (not raw `update()`).
5. Write/extend a regression test in `tests/Feature/BookingFlowTest.php` for any
   business-critical change.

# Common Mistakes
- Recomputing the booking total in the controller/view instead of using the service.
- Generating booking codes or applying vouchers outside `BookingService`.
- Adding an availability/double-booking check (none exists — needs approval).
- Looking up guest bookings by `id` instead of `access_token`.
- Adding a payment integration or new status value casually.

# Validation
- `php artisan test --filter=BookingFlowTest` (SQLite in-memory) passes.
- Re-read [`app/Services/BookingService.php`](app/Services/BookingService.php) to
  confirm the transaction, `lockForUpdate`, and code generation are intact.
- Confirm no total/discount math leaked into a controller or Blade view.

# Related Files
- [`app/Services/BookingService.php`](app/Services/BookingService.php), [`app/Services/BookingPricingService.php`](app/Services/BookingPricingService.php)
- [`app/Models/Booking.php`](app/Models/Booking.php), [`app/Models/Voucher.php`](app/Models/Voucher.php)
- [`app/Http/Controllers/BookingController.php`](app/Http/Controllers/BookingController.php), [`app/Http/Requests/BookingRequest.php`](app/Http/Requests/BookingRequest.php)
- [`resources/views/properties/_booking-form.blade.php`](resources/views/properties/_booking-form.blade.php), [`resources/views/bookings/success.blade.php`](resources/views/bookings/success.blade.php), [`resources/views/bookings/status.blade.php`](resources/views/bookings/status.blade.php)
- [`database/migrations/2026_08_11_162521_create_bookings_table.php`](database/migrations/2026_08_11_162521_create_bookings_table.php), [`tests/Feature/BookingFlowTest.php`](tests/Feature/BookingFlowTest.php)
