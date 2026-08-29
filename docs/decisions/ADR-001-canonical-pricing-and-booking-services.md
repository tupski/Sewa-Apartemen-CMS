# ADR-001: Canonical Pricing and Booking Services

- **Status**: Accepted
- **Date**: 2026-08-27

## Context

Booking totals, promo application, and voucher discounts are the most business-critical calculations in the app. Getting them wrong means charging guests the wrong amount, applying invalid discounts, or allowing voucher double-redemption.

The pricing model is non-trivial: prices are stored as a per-unit-type JSON map with different keys per booking type (`daily`, `transit`, `weekly`, `monthly`), weekday/weekend variants, transit hour buckets, promo rates, and voucher discounts. The public property pages also need to **display** and **preview** prices client-side.

If any of this logic were copied into controllers, Blade views, or JavaScript, the copies would drift from the authoritative rules and produce inconsistent totals — a silent, high-impact class of bug.

## Decision

All pricing, booking-creation, and voucher logic is centralized in three canonical locations, and must never be duplicated elsewhere:

1. **[`BookingPricingService::calculate()`](../../app/Services/BookingPricingService.php)** — the sole calculator for booking totals across all booking types, including promo rates and voucher discounts. It owns the constants `TRANSIT_BUCKETS = [3,6,9,12,24]`, `WEEKLY_NIGHTS = 7`, and `MONTHLY_NIGHTS = 30`, and the weekday/weekend resolution.
2. **[`BookingService::create()`](../../app/Services/BookingService.php)** — the sole booking creator. It is transactional, resolves and locks vouchers by code (`lockForUpdate`), snapshots the pricing result into `price_breakdown`, generates the `BK-YYYYMMDD-XXXX` code under a lock, and increments voucher usage in the same transaction.
3. **[`Voucher::calculateDiscount()`](../../app/Models/Voucher.php)** — the sole voucher discount calculator (percent vs fixed, capped by `max_discount_amount` and the booking amount).

Client-side pricing (the booking form and pricing table reading `$property->prices`) is for **preview/display only**. The server is authoritative.

## Consequences

- **Positive**: one place to reason about, test, and fix pricing/booking/voucher behavior. Concurrency hazards (duplicate codes, voucher double-redemption) are handled once, correctly, via DB transactions and row locks.
- **Positive**: business-critical behavior is pinned by tests (e.g. `BookingFlowTest`), so regressions surface early.
- **Constraint**: contributors must route every price/total/discount through these services — never inline the math in a controller, view, or JS. A JS preview may mirror the display but is never trusted for the recorded total.
- **Constraint**: changes to any of these three units are business-critical and require covering tests (see [`AGENTS.md` §16](../../AGENTS.md)).
- **Trade-off**: the client-side preview and the server calculator both read the `prices` JSON, so their **display** logic can drift; this is acceptable because only the server value is persisted, but preview parity should be kept in mind when editing price keys.

## References

- [`docs/domain/pricing.md`](../domain/pricing.md), [`docs/domain/booking.md`](../domain/booking.md)
- [`AGENTS.md` §7, §10, §11](../../AGENTS.md)
- Skills: [`.agents/skills/pricing/SKILL.md`](../../.agents/skills/pricing/SKILL.md), [`.agents/skills/booking/SKILL.md`](../../.agents/skills/booking/SKILL.md)
