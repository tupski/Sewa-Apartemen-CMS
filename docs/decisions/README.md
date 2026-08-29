# Architecture Decision Records (ADRs)

This directory records significant architecture and business decisions for the Sewa Apartemen CMS.

## What Is an ADR?

An Architecture Decision Record captures a single, significant decision: the **context** that forced a choice, the **decision** itself, and the **consequences** that follow. ADRs are immutable history — once accepted, an ADR is not rewritten. If a decision changes, a new ADR is added that supersedes the old one (and the old one is marked as superseded).

## When to Write One

Write an ADR when a decision has lasting architectural or business impact, such as:

- Centralizing or relocating business logic (e.g. canonical services).
- Choosing not to build a feature that a design doc describes.
- Refactoring a data model in a way future work must respect.
- Adopting or rejecting an external provider, queue driver, cache driver, or storage disk.
- Changing auth, roles, or the booking/pricing lifecycle.

Do **not** write an ADR for routine work — CSS tweaks, copy changes, bug fixes with no design impact, or dependency bumps.

## Numbering & Format

- Files are named `ADR-NNN-short-slug.md`, numbered sequentially from `001`.
- Each ADR uses the standard sections: **Status**, **Context**, **Decision**, **Consequences**.
- Status is one of: `Proposed`, `Accepted`, `Superseded by ADR-NNN`, `Deprecated`.

## Index

| ADR | Title | Status | Date |
|-----|-------|--------|------|
| [ADR-001](ADR-001-canonical-pricing-and-booking-services.md) | Canonical pricing and booking services | Accepted | 2026-08-27 |
| [ADR-002](ADR-002-nearby-places-manual-json-not-geoapify.md) | Nearby places are manual JSON, not Geoapify | Accepted | 2026-08-27 |
| [ADR-003](ADR-003-units-refactored-to-property-types.md) | Units refactored to property `unit_types` | Accepted | 2026-08-27 |

## Related Documentation

- [`AGENTS.md`](../../AGENTS.md) — master rule file (the ADRs formalize several of its rules).
- [`docs/architecture/overview.md`](../architecture/overview.md) — architecture overview.
- [`docs/domain/`](../domain) — domain guides (property, booking, pricing).
