---
name: pricing
description: >-
  Use when working on prices, rates, promos, vouchers, or price display. Trigger
  phrases: "change pricing", "price keys", "promo rate", "voucher discount",
  "transit rate", "weekend price", "pricing table", "money input", "how is the
  total calculated". Sensitive — there is ONE canonical pricing calculator and it
  must not be duplicated.
---

# Purpose
Pricing is business-critical and stored as a JSON `prices` map on `properties`,
with per-booking-type keys. All total/discount calculation flows through one
service. This skill prevents agents from writing a second pricing calculation that
silently diverges from the canonical one.

# When to Use
- Editing prices, promo rates, or voucher discounts.
- Changing how a total is calculated or displayed.
- Touching the admin pricing editors or the frontend pricing table.

# Rules
- `BookingPricingService::calculate()` ([`app/Services/BookingPricingService.php`](app/Services/BookingPricingService.php))
  is the SOLE canonical calculator for transit/daily/weekly/monthly totals, promo
  rates, and voucher discounts. NEVER implement a second pricing calculation.
- Prices live in `properties.prices` JSON, keyed by unit type then rate key:
  - `daily` → `night_wd`, `night_we`
  - `transit` → `t3_wd`,`t3_we`,`t6_wd`,`t6_we`,`t9_wd`,`t9_we`,`t12_wd`,`t12_we`,`t24_wd`,`t24_we`
  - `weekly` → `weekly`
  - `monthly` → `monthly`
- Transit buckets are `[3,6,9,12,24]` hours (`TRANSIT_BUCKETS`); hours are rounded up
  and null/0 hours throw. Weekly = 7 nights, monthly = 30 nights (flat rates).
- Weekend vs weekday is resolved per night (daily) or per check-in day (transit)
  from the property's `weekend_days`, falling back to global settings.
- Promo rates: [`app/Models/PromoRate.php`](app/Models/PromoRate.php); a promo only
  applies if it belongs to the property and `is_active`. Managed via
  [`app/Http/Controllers/PromoRateController.php`](app/Http/Controllers/PromoRateController.php).
- Voucher discount is `Voucher::calculateDiscount()` ([`app/Models/Voucher.php`](app/Models/Voucher.php))
  — percent or fixed, capped by `max_discount_amount` and the booking amount. Do not
  reimplement discount math.
- Client-side pricing display (booking form, pricing table) is PREVIEW ONLY. The
  server value from the service is authoritative.
- Money: use the [`money-input`](resources/views/components/money-input.blade.php)
  component; store as decimal (prices as JSON numeric values). Avoid floats for
  money where the storage convention is decimal. FormRequests strip thousands
  separators before validation.
- Global pricing settings (deposit policy, weekend days) live in the `settings`
  table via [`SettingsController`](app/Http/Controllers/SettingsController.php) +
  [`resources/views/admin/settings/partials/_pricing.blade.php`](resources/views/admin/settings/partials/_pricing.blade.php).

# Workflow
1. If a price seems wrong, trace it to `BookingPricingService::calculate()` BEFORE
   changing anything. Read the method end-to-end.
2. For a new rate key, update the `prices` JSON structure, the admin pricing partial,
   `Property::hasBookingType()`/`priceFor()`, and the service in lockstep.
3. Keep display partials reading `$property->prices` for preview only.
4. Add/extend pricing tests before changing the service.

# Common Mistakes
- Writing a parallel total/discount calculation in a controller, view, or JS.
- Adding a rate key to the JSON without updating the service and `hasBookingType()`.
- Using floats for money when the convention is decimal storage.
- Assuming a promo/voucher applies without the property + `is_active` checks.

# Validation
- `php artisan test` covering pricing/booking (SQLite in-memory) passes.
- Re-read [`app/Services/BookingPricingService.php`](app/Services/BookingPricingService.php)
  to confirm no logic was duplicated elsewhere.
- Confirm rate keys match [`app/Models/Property.php`](app/Models/Property.php)
  (`hasBookingType()` / `priceFor()`) and the admin pricing editor.

# Related Files
- [`app/Services/BookingPricingService.php`](app/Services/BookingPricingService.php)
- [`app/Models/PromoRate.php`](app/Models/PromoRate.php), [`app/Models/Voucher.php`](app/Models/Voucher.php), [`app/Models/Property.php`](app/Models/Property.php)
- [`app/Http/Controllers/PromoRateController.php`](app/Http/Controllers/PromoRateController.php), [`app/Http/Controllers/VoucherController.php`](app/Http/Controllers/VoucherController.php), [`app/Http/Controllers/SettingsController.php`](app/Http/Controllers/SettingsController.php)
- [`resources/views/admin/properties/_pricing.blade.php`](resources/views/admin/properties/_pricing.blade.php), [`resources/views/admin/settings/partials/_pricing.blade.php`](resources/views/admin/settings/partials/_pricing.blade.php)
- [`resources/views/properties/_pricing-table.blade.php`](resources/views/properties/_pricing-table.blade.php), [`resources/views/components/money-input.blade.php`](resources/views/components/money-input.blade.php)
