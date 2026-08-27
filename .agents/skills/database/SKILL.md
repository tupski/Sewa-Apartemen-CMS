---
name: database
description: >-
  Use when changing the schema, adding a migration, writing a query, or touching
  a model's fillable/casts/relationships. Trigger phrases: "add a column", "new
  migration", "add an index", "create a table", "eager load", "fix N+1", "add a
  relationship", "seed data", "cast a JSON column". Encodes this repo's
  migration-first, explicit-FK, JSON-column conventions and the real table list.
---

# Purpose
Schema and data-access conventions for the CMS. The database is MySQL in
production and SQLite in-memory for tests, so migrations and queries must avoid
MySQL-only behavior. This skill lists the tables that actually exist and the
conventions every migration/model must follow.

# When to Use
- Creating or editing a migration in `database/migrations/`.
- Adding a relationship, `$fillable` entry, or cast to a model.
- Writing queries and needing to avoid N+1.
- Adding dev/test data via a seeder.

# Rules
- Migration-first: all schema changes go through a new migration. Never edit an
  existing shipped migration to change live schema; add an additive migration.
- Declare foreign keys explicitly with `->constrained()` / `->foreign()`. Add
  indexes intentionally for `WHERE`/`JOIN`/`ORDER BY` columns.
- Models use `$fillable` (never `$guarded`). Cast JSON columns to `array` in the
  model — e.g. `Property` casts `prices`, `nearby_places`, `unit_types`,
  `weekend_days`, `photo_categories`, `required_documents` all to `array`.
- Naming: plural snake_case tables, PK `id`, FKs `{singular}_id`, pivots like
  `amenity_property`, `post_tag`, `model_has_roles`. Soft deletes via `deleted_at`
  where used (`Property`, `Booking`, `Voucher`).
- Eager load common relations to avoid N+1: `property.photos`, `property.amenities`,
  `booking.property`, `post.category`, `post.tags`.
- Wrap critical multi-row operations in `DB::transaction`. Use `lockForUpdate` when
  reserving a voucher code or generating a booking code (see the booking skill).
- Tests run on SQLite in-memory — do not rely on MySQL-specific SQL, JSON functions,
  or enum quirks in queries.
- Never run destructive migrations (`migrate:fresh`, `db:wipe`, drop/rewrite table)
  against real data without explicit approval. Use seeders for dev/test data.

# Real Tables (from migrations)
`users`, `cache`/`cache_locks`, `jobs`, `settings`, `roles`, `model_has_roles`,
`media`, `pages`, `blocks`, `navigations`, `properties`, `amenities`,
`amenity_property` (pivot), `bookings`, `seo_metadata`, `redirects`, `categories`,
`tags`, `posts`, `post_tag` (pivot), `user_activity_logs`, `property_photos`,
`promo_rates`, `vouchers`, `languages`, `currency_rates`.

Notable unique/indexes: `properties.slug`, `bookings.code`, `bookings.access_token`,
`users.email`, `roles.slug`, `redirects.from_url`, `settings.key`,
`currency_rates.unique(from_currency,to_currency)`, `property_photos.index(property_id,category)`.

# Do NOT invent these
- No `units` table (dropped by [`2026_08_12_000000_refactor_units_to_property_types.php`](database/migrations/2026_08_12_000000_refactor_units_to_property_types.php); room types live as `unit_types` JSON on `properties`).
- No `availability`, `places`/`property_places`, `payments`, `reviews` tables.
- Nearby places are JSON `nearby_places` on `properties`, not a table.

# Workflow
1. Read the existing migration(s) for the table you are changing.
2. Write an additive migration with explicit FKs and intentional indexes.
3. Update the model's `$fillable` and `$casts` (JSON → `array`).
4. If querying, add `with()`/`load()` for related models.
5. Run `php artisan test` against SQLite to confirm no MySQL-only assumptions.

# Common Mistakes
- Editing a shipped migration instead of adding a new one.
- Casting a JSON column as `string`/`json` object instead of `array`.
- Forgetting the FK constraint or a needed index.
- Introducing MySQL-only SQL that breaks SQLite tests.
- Referencing the removed `Unit` model / `units` table.

# Validation
- `php artisan migrate` on a dev DB (never `migrate:fresh` on real data).
- `php artisan test` (SQLite in-memory) passes.
- Re-read the model to confirm `$fillable` + `$casts` match the new columns.

# Related Files
- [`database/migrations/`](database/migrations)
- [`database/seeders/`](database/seeders)
- [`app/Models/Property.php`](app/Models/Property.php), [`app/Models/Booking.php`](app/Models/Booking.php), [`app/Models/Voucher.php`](app/Models/Voucher.php)
- [`phpunit.xml`](phpunit.xml) (SQLite in-memory test DB)
