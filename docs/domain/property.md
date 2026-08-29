# Property Domain

The `Property` is the core listing entity — an apartment/room rental with photos, amenities, pricing, location, and policies. Grounded in [`app/Models/Property.php`](../../app/Models/Property.php), [`app/Http/Controllers/PropertyController.php`](../../app/Http/Controllers/PropertyController.php), and the property migrations.

## Model at a Glance

- `Property` uses `SoftDeletes`.
- Mass assignment via `$fillable` (never `$guarded`).
- Several columns are JSON, cast to arrays in the model.

### JSON Columns

| Column | Meaning |
|--------|---------|
| `unit_types` | The room/unit types offered (e.g. Studio, 1BR). This **replaces** the removed `Unit` model — see [`ADR-003`](../decisions/ADR-003-units-refactored-to-property-types.md). |
| `prices` | Per-unit-type price map keyed by booking type. See [`pricing.md`](pricing.md). |
| `weekend_days` | Property-level weekend day override; falls back to global settings. |
| `photo_categories` | Gallery category labels used to group `PropertyPhoto` records. |
| `required_documents` | Documents required at booking/check-in. |
| `nearby_places` | Manually-entered nearby points of interest. **Not** an API integration — see [`ADR-002`](../decisions/ADR-002-nearby-places-manual-json-not-geoapify.md). |

### Scalar Fields of Note

`name`, `slug` (unique), `description`, `address`/`city`/`province`/`postal_code`, `latitude`/`longitude` (decimal), `featured_image_id` (FK → `media`), `status` (default `draft`), `is_featured`, `order`, `max_days`, `checkin_time`, `checkout_time`, `checkin_method`, `max_guests`.

## Lifecycle

A property typically moves through these stages in the admin:

1. **Create** — name, slug, description, location. Status starts as `draft`.
2. **Photos** — upload/attach media as `PropertyPhoto` records, grouped by `photo_categories`; pick a featured image (`featured_image_id`).
3. **Amenities** — attach via the `amenity_property` pivot (`belongsToMany`).
4. **Pricing** — edit the `prices` JSON per unit type / booking type. See [`pricing.md`](pricing.md).
5. **Detail fields** — check-in/out times, `checkin_method`, `required_documents`, `max_days`, `max_guests`, `nearby_places`.
6. **Publish** — set `status` to published/active so it appears on the public site.

## Relationships

| Relationship | Definition |
|--------------|------------|
| Featured image | `belongsTo(Media, 'featured_image_id')` |
| Amenities | `belongsToMany(Amenity, 'amenity_property')` |
| Photos | `hasMany(PropertyPhoto)` |
| Promo rates | `hasMany(PromoRate)` |
| Bookings | `hasMany(Booking)` |

## Unit Types (NOT a `Unit` model)

There is **no** `Unit` / `Room` model. The `units` table and `amenity_unit` pivot were dropped by [`2026_08_12_000000_refactor_units_to_property_types.php`](../../database/migrations/2026_08_12_000000_refactor_units_to_property_types.php). Unit types now live in the `unit_types` JSON column. Prices are keyed by unit type inside the `prices` JSON.

Helper methods:

- [`Property::hasType(string $type)`](../../app/Models/Property.php:151) — whether a given unit type exists on the property.
- [`Property::priceFor(string $type, string $key)`](../../app/Models/Property.php:175) — the raw price for a unit type + price key.
- [`Property::hasBookingType(string $key)`](../../app/Models/Property.php:226) — whether the property offers a booking type (daily/transit/weekly/monthly), based on which price keys are populated.
- [`Property::maxBookingDays()`](../../app/Models/Property.php:268) — the max bookable days (from `max_days`).

## Photos & Featured Image

- [`PropertyPhoto`](../../app/Models/PropertyPhoto.php) links a `property_id` and `media_id` with a `category` and `sort_order` (indexed by `property_id`, `category`).
- The featured/primary image is `properties.featured_image_id` → `media`. Before reordering or deleting media, check which record is the featured image so the thumbnail is not broken.
- See the media skill ([`.agents/skills/media/SKILL.md`](../../.agents/skills/media/SKILL.md)) for upload/storage conventions (public disk, `upload_file()` helper).

## Amenities

Amenities are a shared taxonomy attached many-to-many via `amenity_property`. Manage them through the amenity admin; attach/detach on the property edit screen.

## Nearby Places

`nearby_places` is a manually-entered JSON array on the property (name, distance, category, etc.), rendered on the property detail page. The Geoapify integration document is a **design spec only** and is not implemented — do not add external API calls to the request path. See [`ADR-002`](../decisions/ADR-002-nearby-places-manual-json-not-geoapify.md) and the nearby-places skill.

## Slug

`properties.slug` is unique and used for route-model binding (`{property:slug}`) with a configurable base slug (`slug('slug_apartments','apartments')`). The slug pattern is validated in [`PropertyRequest`](../../app/Http/Requests/PropertyRequest.php) as `^[a-z0-9]+(?:-[a-z0-9]+)*$`. Preserve slug stability; regenerate only on explicit request.

## View Surfaces

**Admin** ([`resources/views/admin/properties/`](../../resources/views/admin/properties)):

- `index.blade.php` — listing.
- `create.blade.php` / `edit.blade.php` — reuse the partials below.
- `_photos.blade.php` — gallery/photo editor.
- `_pricing.blade.php` — the `prices` JSON editor.
- `_policy.blade.php` — check-in/out, documents, policy fields.

**Frontend**:

- [`properties/index.blade.php`](../../resources/views/properties/index.blade.php) — public listing.
- [`properties/show.blade.php`](../../resources/views/properties/show.blade.php) — detail page (hero gallery, info, booking form, pricing table, amenities, nearby places + map, policies, FAQ, nearby properties). Honors the `displayMode` setting (`both`, `pricing_only`, `form_only`).
- [`properties/_card.blade.php`](../../resources/views/properties/_card.blade.php), [`properties/_pricing-table.blade.php`](../../resources/views/properties/_pricing-table.blade.php), [`properties/_booking-form.blade.php`](../../resources/views/properties/_booking-form.blade.php) — supporting partials.

`PropertyController::publicShow()` eager-loads `featuredImage`, `amenities`, `photos.media`, and active `promoRates` to avoid N+1 queries.

## Related Documentation

- [`docs/domain/pricing.md`](pricing.md) — price keys and the canonical calculator.
- [`docs/domain/booking.md`](booking.md) — booking flow that consumes property pricing.
- Property skill: [`.agents/skills/property/SKILL.md`](../../.agents/skills/property/SKILL.md).
- [`AGENTS.md` §11](../../AGENTS.md) — pricing rules.
