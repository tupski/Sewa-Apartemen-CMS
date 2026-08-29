# ADR-003: Units Refactored to Property `unit_types`

- **Status**: Accepted
- **Date**: 2026-08-27

## Context

The original schema modeled a rentable unit as its own `Unit` entity: a `units` table (created by [`2026_08_11_151815_create_units_table.php`](../../database/migrations/2026_08_11_151815_create_units_table.php)) with a related `amenity_unit` pivot, and a `Unit` Eloquent model.

In practice, a "unit" in this product is a lightweight variant of a property (e.g. Studio vs 1BR) that mainly differs by price. A full relational model added complexity — separate records, joins, and pivots — without a matching benefit, and pricing was already moving toward a per-unit-type JSON price map on the property.

## Decision

The `Unit` model and its tables were refactored away. Migration [`2026_08_12_000000_refactor_units_to_property_types.php`](../../database/migrations/2026_08_12_000000_refactor_units_to_property_types.php) **drops** the `units` table and the `amenity_unit` pivot. Unit types now live in the `unit_types` JSON column on `properties`, and prices are keyed by unit type inside the `prices` JSON.

Property-side helpers operate on this JSON:

- [`Property::hasType()`](../../app/Models/Property.php:151) — whether a unit type exists.
- [`Property::priceFor()`](../../app/Models/Property.php:175) — a price for a unit type + key.

Bookings reference the chosen unit type via the `unit_type` string column, not a `unit_id` FK (the old `unit_id` was dropped from `bookings`).

## Consequences

- **Positive**: simpler schema; unit variants and their prices are edited together on the property.
- **Positive**: fewer joins on the hot property-detail and booking paths.
- **Cleanup debt**: some legacy artifacts may linger and are effectively **dead**:
  - The [`create_units_table`](../../database/migrations/2026_08_11_151815_create_units_table.php) migration remains in history (migrations are not deleted) but the table is dropped by the later refactor.
  - A stale [`UnitFactory`](../../database/factories/UnitFactory.php) and any `App\Models\Unit` reference (e.g. in `SchemaService`) point at a model that no longer exists. Ignore them; do not treat them as evidence a `Unit` model exists.
- **Constraint**: do **not** reintroduce a `Unit`/`Room` model or `units` table without an explicit, audited decision. Model unit variants as `unit_types` JSON on the property, consistent with the current pricing/booking code.

## References

- [`docs/domain/property.md`](../domain/property.md), [`docs/architecture/database.md`](../architecture/database.md)
- [`AGENTS.md` §4](../../AGENTS.md)
- Skills: [`.agents/skills/property/SKILL.md`](../../.agents/skills/property/SKILL.md), [`.agents/skills/database/SKILL.md`](../../.agents/skills/database/SKILL.md)
