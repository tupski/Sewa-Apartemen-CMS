# Booking Domain

The booking flow lets a guest reserve a property unit type for a date/time. Grounded in [`app/Services/BookingService.php`](../../app/Services/BookingService.php), [`app/Models/Booking.php`](../../app/Models/Booking.php), [`app/Http/Controllers/BookingController.php`](../../app/Http/Controllers/BookingController.php), and the booking migrations.

## Flow

```
Property detail page
  → Booking form (resources/views/properties/_booking-form.blade.php)
  → BookingController@store  (validates via BookingRequest)
  → BookingService::create()  (transactional)
        ├─ resolve + lock voucher (by code)
        ├─ BookingPricingService::calculate()  (authoritative total)
        ├─ generate code (BK-YYYYMMDD-XXXX)
        ├─ persist booking (+ price_breakdown snapshot)
        └─ BookingNotificationService (booking.created)
  → Success page (resources/views/bookings/success.blade.php)
  → Guest status lookup (resources/views/bookings/status.blade.php) via access_token
```

## Statuses

The `bookings.status` enum (migration [`2026_08_11_162521`](../../database/migrations/2026_08_11_162521_create_bookings_table.php)):

| Status | Meaning |
|--------|---------|
| `pending` | Default on creation; awaiting confirmation. |
| `confirmed` | Approved by an admin. |
| `cancelled` | Cancelled. |
| `completed` | Stay finished. |

`Booking` provides query scopes (`pending`/`confirmed`/`cancelled`/`completed`) and helpers like `isActive()` and `isPastDue()`.

## `BookingService::create()`

The **sole canonical booking creator**. It runs inside a `DB::transaction`:

1. **Voucher resolution + lock** — a voucher is resolved by its **code** (`strtoupper`+trim), locked with `lockForUpdate()`, and validated via `Voucher::isValid()`. A numeric `voucher_id` alone is rejected. This prevents double-redemption races (FIND-003).
2. **Unit-type check** — `Property::hasType()` must pass, else the booking is rejected.
3. **Booking-method normalization** — the newer `unit` (`jam`/day) + `duration` inputs are normalized to the legacy `booking_type` / `duration_hours` / `check_out` fields; `maxBookingDays()` is enforced.
4. **Pricing** — the total flows through [`BookingPricingService::calculate()`](../../app/Services/BookingPricingService.php). The result is snapshotted into `price_breakdown`. See [`pricing.md`](pricing.md).
5. **Code generation** — [`BookingService::generateCode()`](../../app/Services/BookingService.php:22) produces `BK-YYYYMMDD-XXXX`, reading the last code with `lockForUpdate()` in the same transaction to avoid duplicate codes on concurrent requests (BUG-006).
6. **Persist + notify** — the booking is saved (with `access_token`), the voucher `used_count` is incremented in the same transaction, and `BookingNotificationService` fires the `booking.created` event.

## Booking Code Format

`BK-YYYYMMDD-XXXX` where `XXXX` is a zero-padded 4-digit daily sequence (e.g. `BK-20260827-0007`). Generation is transactional and lock-guarded.

## Guest Status Lookup (`access_token`)

Guests check a booking without logging in via `bookings.access_token` (unique, `varchar(64)`, added by [`2026_08_24_000000`](../../database/migrations/2026_08_24_000000_add_access_token_to_bookings_table.php)). The status page ([`bookings/status.blade.php`](../../resources/views/bookings/status.blade.php)) looks up the booking by this token — never by raw incrementing `id`.

## Voucher Application

- Vouchers are applied **inside** `BookingService::create()` only, resolved by code with `lockForUpdate`.
- The discount amount comes from [`Voucher::calculateDiscount()`](../../app/Models/Voucher.php) (percent vs fixed, capped by `max_discount_amount` and by the booking amount).
- `bookings.voucher_id` and `voucher_discount` record the applied voucher (added by [`2026_08_20_000002`](../../database/migrations/2026_08_20_000002_add_voucher_to_bookings_table.php)).
- Do not compute discounts anywhere else. See [`ADR-001`](../decisions/ADR-001-canonical-pricing-and-booking-services.md).

## Notifications

[`BookingNotificationService`](../../app/Services/BookingNotificationService.php) sends fire-and-forget webhook notifications (settings `notification_webhook` / `notification_webhook_secret`) for the `booking.created`, `booking.confirmed`, `booking.cancelled`, and `booking.completed` events. It always logs. There is no queue involved — the call is made inline.

## Admin Actions

Admins manage bookings via [`BookingController`](../../app/Http/Controllers/BookingController.php) and the admin views ([`resources/views/admin/bookings/index.blade.php`](../../resources/views/admin/bookings/index.blade.php), [`show.blade.php`](../../resources/views/admin/bookings/show.blade.php)):

- **Confirm** → `BookingService::confirm()` (status `confirmed` + notify).
- **Cancel** → `BookingService::cancel()` (status `cancelled` + notify).
- **Complete** → `BookingService::complete()` (status `completed` + notify).
- **Notes** — the `notes` field (added by [`2026_08_11_173000`](../../database/migrations/2026_08_11_173000_add_notes_to_bookings_table.php)).
- **Export** — admin booking export from the index screen.

## Explicit Non-Features

- **No `Availability` table** — availability is **not** a stored table/model. However, `BookingService::create()` **does** run a server-side conflict check via `BookingService::validateAvailability()` ([`BookingService.php:204`](../../app/Services/BookingService.php:204)): it rejects a booking whose window overlaps an existing booking for the same `property_id` + `unit_type` with `status != 'cancelled'` (i.e. `pending`, `confirmed`, or `completed`), using `lockForUpdate()` inside the create transaction to prevent TOCTOU double-booking. Pinned by `SecurityTest::test_overlapping_booking_is_rejected` (see [`SecurityTest.php:188`](../../tests/Feature/SecurityTest.php:188)). Frontend date pickers are advisory only; the server check is authoritative.
- **No payment system.** There is no `Payment` model or payment gateway. Only `total_price` and `deposit_amount` are stored; payment status effectively mirrors booking status.

## Related Documentation

- [`docs/domain/pricing.md`](pricing.md) — how totals are computed.
- [`docs/domain/property.md`](property.md) — the property/unit-type source of prices.
- Booking skill: [`.agents/skills/booking/SKILL.md`](../../.agents/skills/booking/SKILL.md) (the strictest skill — booking code is transactional and business-critical).
- [`AGENTS.md` §10](../../AGENTS.md) — booking rules.
