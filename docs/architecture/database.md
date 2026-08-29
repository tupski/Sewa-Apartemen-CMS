# Database Overview

The real database schema, grounded in the migrations under [`database/migrations/`](../../database/migrations) and the audit ([`docs/_agent-audit/AUDIT-FINDINGS.md`](../_agent-audit/AUDIT-FINDINGS.md)).

## Conventions

- **Migration-first**: all schema changes go through migrations. Never edit schema directly or alter tables outside a migration.
- **Explicit foreign keys**: FK constraints are declared with `->constrained()` / `->foreign()`. Do not rely on implicit naming.
- **Intentional indexes**: indexes are added for `WHERE`/`JOIN`/`ORDER BY` columns (slugs, booking lookups), not blindly.
- **Naming**: plural snake_case tables, PK `id`, FKs `{singular}_id`; pivots `amenity_property`, `post_tag`, `model_has_roles`.
- **JSON columns** hold flexible domain data (prices, unit types, nearby places, etc.), cast in the model.
- **Soft deletes** (`deleted_at`) on `properties`, `bookings`, and `vouchers`.
- **Additive migrations preferred** — avoid destructive changes; `migrate:fresh` / `db:wipe` require explicit approval.

## Tables

| Table | Migration | Notes |
|-------|-----------|-------|
| `users` | [`0001_01_01_000000`](../../database/migrations/0001_01_01_000000_create_users_table.php) | `email` unique; `phone`, `avatar` added later |
| `cache` / `cache_locks` | [`0001_01_01_000001`](../../database/migrations/0001_01_01_000001_create_cache_table.php) | framework cache |
| `jobs` | [`0001_01_01_000002`](../../database/migrations/0001_01_01_000002_create_jobs_table.php) | framework queue table (present, but no custom queued jobs use it) |
| `settings` | [`2026_08_11_142519`](../../database/migrations/2026_08_11_142519_create_settings_table.php) | `key` unique, `value`, `type`, `group` (added later) |
| `roles` | [`2026_08_11_143717`](../../database/migrations/2026_08_11_143717_create_roles_table.php) | `slug` unique |
| `model_has_roles` | [`2026_08_11_143718`](../../database/migrations/2026_08_11_143718_create_model_has_roles_table.php) | polymorphic pivot (`model_type`, `model_id`, `role_id`) |
| `media` | [`2026_08_11_144256`](../../database/migrations/2026_08_11_144256_create_media_table.php) | `user_id` FK (null on delete); disk/directory/filename; `metadata` JSON |
| `pages` | [`2026_08_11_144632`](../../database/migrations/2026_08_11_144632_create_pages_table.php) | `slug`; `is_homepage`; `blocks` JSON |
| `blocks` | [`2026_08_11_144745`](../../database/migrations/2026_08_11_144745_create_blocks_table.php) | reusable content block |
| `navigations` | [`2026_08_11_150120`](../../database/migrations/2026_08_11_150120_create_navigations_table.php) | self-FK `parent_id`; `menu_location`, `order` |
| `properties` | [`2026_08_11_151813`](../../database/migrations/2026_08_11_151813_create_properties_table.php) (+ refactor/detail/max_guests migrations) | `slug` unique; `featured_image_id` FK; **JSON**: `unit_types`, `weekend_days`, `prices`, `photo_categories`, `required_documents`, `nearby_places`; softDeletes |
| ~~`units`~~ | [`2026_08_11_151815`](../../database/migrations/2026_08_11_151815_create_units_table.php) | **DROPPED** by the refactor migration (see below) |
| `amenities` | [`2026_08_11_151816`](../../database/migrations/2026_08_11_151816_create_amenities_table.php) | pivot `amenity_property` |
| `bookings` | [`2026_08_11_162521`](../../database/migrations/2026_08_11_162521_create_bookings_table.php) (+ notes/voucher/access_token migrations) | `property_id` FK (cascade); `code` unique; `access_token` unique; `status` enum; `voucher_id` FK; `price_breakdown` JSON; softDeletes |
| `seo_metadata` | [`2026_08_11_170000`](../../database/migrations/2026_08_11_170000_create_seo_metadata_table.php) | polymorphic `seoable`; `open_graph`/`twitter` JSON |
| `redirects` | [`2026_08_11_170001`](../../database/migrations/2026_08_11_170001_create_redirects_table.php) | `from_url` unique, `to_url`, `status_code` |
| `categories` | [`2026_08_11_171000`](../../database/migrations/2026_08_11_171000_create_categories_table.php) | blog categories |
| `tags` | [`2026_08_11_171001`](../../database/migrations/2026_08_11_171001_create_tags_table.php) | blog tags |
| `posts` | [`2026_08_11_171002`](../../database/migrations/2026_08_11_171002_create_posts_table.php) | `slug`; `category_id`, `user_id`; morphOne SEO |
| `post_tag` | [`2026_08_11_171003`](../../database/migrations/2026_08_11_171003_create_post_tag_table.php) | pivot `post_id`/`tag_id` |
| `user_activity_logs` | [`2026_08_11_173100`](../../database/migrations/2026_08_11_173100_create_user_activity_logs_table.php) | backs the `log_activity()` helper / `ActivityLog` model |
| `property_photos` | [`2026_08_12_010000`](../../database/migrations/2026_08_12_010000_create_property_photos_table.php) | `property_id` + `media_id` (cascade); `category`, `sort_order`; index (`property_id`, `category`) |
| `promo_rates` | [`2026_08_20_000000`](../../database/migrations/2026_08_20_000000_create_promo_rates_table.php) | `property_id` FK; `applies_to`, `active_days` JSON, `price`, `booking_type` |
| `vouchers` | [`2026_08_20_000001`](../../database/migrations/2026_08_20_000001_create_vouchers_table.php) | `code` unique; `discount_type` (percent/fixed); `usage_limit`, `used_count`; softDeletes |
| `languages` | [`2026_08_26_000001`](../../database/migrations/2026_08_26_000001_create_languages_table.php) | `code`, `native_name`, `is_active`, `is_default` |
| `currency_rates` | [`2026_08_26_000002`](../../database/migrations/2026_08_26_000002_create_currency_rates_table.php) | `from_currency`/`to_currency`; `rate`; unique(`from`, `to`) |

## Key Relationships

- `Property` → `belongsTo(Media, 'featured_image_id')`, `belongsToMany(Amenity, 'amenity_property')`, `hasMany(PropertyPhoto)`, `hasMany(PromoRate)`, `hasMany(Booking)`.
- `PropertyPhoto` → `belongsTo(Property)`, `belongsTo(Media)`.
- `Booking` → `belongsTo(Property)`, `belongsTo(Voucher)`.
- `Voucher` → `hasMany(Booking)`; `calculateDiscount()` on the model.
- `PromoRate` → `belongsTo(Property)`.
- `User` → `belongsToMany(Role, 'model_has_roles', 'model_id', 'role_id')` scoped by `model_type`; `isAdmin()` checks `super-admin`/`admin`.
- `Post` → `belongsTo(User)` (author), `belongsTo(Category)`, `belongsToMany(Tag, 'post_tag')`, `morphOne(SeoMetadata)`.
- `SeoMetadata` → polymorphic `seoable` (pages, posts, and other entities attach via the morph).
- `Redirect` → flushes the `redirects` cache on save.

## JSON Columns

| Model.Column | Purpose |
|--------------|---------|
| `Property.unit_types` | Room/unit types (replaces the removed `Unit` model — see ADR-003) |
| `Property.prices` | Per-unit-type price map, keyed by booking type (see [`pricing.md`](../domain/pricing.md)) |
| `Property.weekend_days` | Property-level weekend-day override (falls back to global settings) |
| `Property.photo_categories` | Gallery category labels for property photos |
| `Property.required_documents` | Documents required at booking/check-in |
| `Property.nearby_places` | Manually-entered nearby POIs (see ADR-002 — NOT Geoapify) |
| `Booking.price_breakdown` | Snapshot of the pricing calculation at creation time |
| `Booking.metadata` | Misc booking metadata |
| `Page.blocks` | Ordered content blocks for a CMS page |
| `SeoMetadata.open_graph` / `.twitter` | OG / Twitter card metadata |
| `PromoRate.active_days` | Days a promo rate applies |
| `Media.metadata` | File metadata |

## Notable Pivots & Special Tables

- **`model_has_roles`** — polymorphic role pivot. `User::roles()` filters by `model_type` = `User::class`.
- **`post_tag`** — blog post ↔ tag many-to-many.
- **`amenity_property`** — property ↔ amenity many-to-many (the old `amenity_unit` pivot was dropped with `units`).
- **`seo_metadata`** — polymorphic morph (`seoable_id`/`seoable_type`); do not flatten it.
- **`redirects`** — `from_url` → `to_url` with `status_code`; drives [`RedirectMiddleware`](../../app/Http/Middleware/RedirectMiddleware.php).
- **`user_activity_logs`** — audit trail for admin actions.
- **`settings`** — key/value/type/**group**; read through the cached [`SettingsService`](../../app/Services/SettingsService.php).
- **`languages`** / **`currency_rates`** — i18n and FX support.

## Booking-Specific Columns

- **`bookings.access_token`** (unique, added by [`2026_08_24_000000`](../../database/migrations/2026_08_24_000000_add_access_token_to_bookings_table.php)) — powers the guest status lookup without authentication.
- **`bookings.voucher_id`** (nullable FK, added by [`2026_08_20_000002`](../../database/migrations/2026_08_20_000002_add_voucher_to_bookings_table.php)) plus `voucher_discount` — applied inside `BookingService::create()`.
- **`bookings.code`** (unique) — `BK-YYYYMMDD-XXXX` format.
- **`bookings.status`** — enum(`pending`, `confirmed`, `cancelled`, `completed`), default `pending`.

## Unique / Indexed Columns of Note

`properties.slug`, `bookings.code`, `bookings.access_token`, `users.email`, `roles.slug`, `redirects.from_url`, `settings.key` are unique. `currency_rates` has unique(`from_currency`, `to_currency`). `property_photos` has a composite index (`property_id`, `category`).

## Legacy: the `units` table

The original [`2026_08_11_151815_create_units_table.php`](../../database/migrations/2026_08_11_151815_create_units_table.php) created a `units` table (and an `amenity_unit` pivot). Both were **dropped** by [`2026_08_12_000000_refactor_units_to_property_types.php`](../../database/migrations/2026_08_12_000000_refactor_units_to_property_types.php), which moved that concept into the `unit_types` JSON column on `properties`. A stale [`UnitFactory`](../../database/factories/UnitFactory.php) may linger and any `App\Models\Unit` reference is dead. Do not reintroduce a `Unit` model without an explicit decision — see [`docs/decisions/ADR-003`](../decisions/ADR-003-units-refactored-to-property-types.md).

## Related Documentation

- [`docs/architecture/overview.md`](overview.md) — architecture and services.
- [`docs/domain/property.md`](../domain/property.md), [`docs/domain/booking.md`](../domain/booking.md), [`docs/domain/pricing.md`](../domain/pricing.md).
- Database skill: [`.agents/skills/database/SKILL.md`](../../.agents/skills/database/SKILL.md).
- [`AGENTS.md` §6](../../AGENTS.md) — database rules.
