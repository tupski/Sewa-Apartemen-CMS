# Pricing Domain

Pricing is the most business-critical logic in the app. There is exactly **one** calculator, [`BookingPricingService::calculate()`](../../app/Services/BookingPricingService.php), and it must never be duplicated. Grounded in that service, [`app/Models/Property.php`](../../app/Models/Property.php), [`app/Models/Voucher.php`](../../app/Models/Voucher.php), and [`app/Models/PromoRate.php`](../../app/Models/PromoRate.php).

## Where Prices Live

Prices are stored in the `prices` JSON column on `properties`, keyed **per unit type** and then **per price key**:

```
prices = {
  "<unit_type>": {
    "night_wd": 350000, "night_we": 400000,
    "t3_wd": 90000,  "t3_we": 110000,
    ...
    "weekly": 2000000,
    "monthly": 7000000
  }
}
```

## Price Keys by Booking Type

| Booking type | Price keys |
|--------------|------------|
| `daily` | `night_wd` (weekday), `night_we` (weekend) |
| `transit` | `t3_wd`/`t3_we`, `t6_wd`/`t6_we`, `t9_wd`/`t9_we`, `t12_wd`/`t12_we`, `t24_wd`/`t24_we` |
| `weekly` | `weekly` (flat) |
| `monthly` | `monthly` (flat) |

The `_wd`/`_we` suffix distinguishes weekday vs weekend. Transit keys are prefixed by the hour bucket (`t3`, `t6`, `t9`, `t12`, `t24`).

[`Property::hasBookingType()`](../../app/Models/Property.php:226) determines which booking types a property offers based on which keys are populated; [`Property::priceFor()`](../../app/Models/Property.php:175) fetches a raw key value.

## The Canonical Calculator

[`BookingPricingService::calculate()`](../../app/Services/BookingPricingService.php:30) is the **sole authority** for all booking totals. Signature:

```php
calculate(
    Property $property,
    string $unitType,
    string $bookingType,
    Carbon $checkIn,
    ?Carbon $checkOut = null,
    ?int $hours = null,
    ?int $promoRateId = null,
    ?int $voucherId = null
): array
```

It returns an array including `total`, `nights`, `hours`, `is_weekend`, `days`, `rate`, `promo`, `voucher`, and `discount`.

### Constants

| Constant | Value | Meaning |
|----------|-------|---------|
| `TRANSIT_BUCKETS` | `[3, 6, 9, 12, 24]` | Allowed transit hour buckets |
| `WEEKLY_NIGHTS` | `7` | Nights represented by a weekly rate |
| `MONTHLY_NIGHTS` | `30` | Nights represented by a monthly rate |

### How Each Type Is Calculated

- **Transit** — hourly. `normalizeTransitHours()` rounds the requested hours up to the nearest bucket (and throws `InvalidArgumentException` on null/0, per BUG-008). Weekday vs weekend is decided by the **check-in day**. The rate is `prices[unitType]["t{hours}_wd|we"]`.
- **Daily** — per-night. Each night's weekday/weekend status is resolved individually, using `night_wd` / `night_we`.
- **Weekly** — flat `weekly` price, counted as `WEEKLY_NIGHTS` (7) nights.
- **Monthly** — flat `monthly` price, counted as `MONTHLY_NIGHTS` (30) nights.

### Weekend Resolution

Weekend days come from the property's `weekend_days` JSON when set; otherwise from global settings (`resolveWeekendDays()`).

## Promo Rates

[`PromoRate`](../../app/Models/PromoRate.php) records per-property promotional prices. Inside `calculate()`, a promo is applied only if it belongs to the property (`property_id` match) and is `is_active`. `PromoRate::matchesCheckin()` matches by day-of-week, time, booking type, and duration. Manage them via [`PromoRateController`](../../app/Http/Controllers/PromoRateController.php) and the admin promo-rate screens.

## Vouchers

[`Voucher::calculateDiscount()`](../../app/Models/Voucher.php) is the **sole canonical voucher calculator** — percent vs fixed, capped by `max_discount_amount` and by the booking amount. Validity is checked by `Voucher::isValid()` (active, within `valid_from`/`valid_until`, under `usage_limit`). Vouchers are resolved by **code** with `lockForUpdate` inside [`BookingService::create()`](../../app/Services/BookingService.php) — never elsewhere.

## Global Pricing Settings

Deposit policy, default weekend days, and related knobs live in the `settings` table and are edited via [`SettingController`/`SettingsController`](../../app/Http/Controllers/SettingsController.php) with the partial [`resources/views/admin/settings/partials/_pricing.blade.php`](../../resources/views/admin/settings/partials/_pricing.blade.php). They are read through the cached [`SettingsService`](../../app/Services/SettingsService.php).

## Server-Authoritative Rule

The server is the only authority on price. The public booking form ([`_booking-form.blade.php`](../../resources/views/properties/_booking-form.blade.php)) and pricing table ([`_pricing-table.blade.php`](../../resources/views/properties/_pricing-table.blade.php)) read `$property->prices` to **display** and **preview** prices, but that client-side view is not authoritative. Money inputs use the [`money-input`](../../resources/views/components/money-input.blade.php) component; [`PropertyRequest`](../../app/Http/Requests/PropertyRequest.php) strips thousands separators before validation.

**Never implement a second pricing calculation.** If a price path looks wrong, trace it back to `BookingPricingService::calculate()` before changing anything. See [`ADR-001`](../decisions/ADR-001-canonical-pricing-and-booking-services.md).

## Related Documentation

- [`docs/domain/booking.md`](booking.md) — how pricing is consumed at booking time.
- [`docs/domain/property.md`](property.md) — where the `prices` JSON is edited.
- Pricing skill: [`.agents/skills/pricing/SKILL.md`](../../.agents/skills/pricing/SKILL.md).
- [`AGENTS.md` §11](../../AGENTS.md) — pricing rules.
