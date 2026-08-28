---
name: property
description: >-
  Use when working on properties (listings): creating/editing a property, photos
  and galleries, amenities, location/nearby places, unit types, max guests, or
  the property detail/listing pages. Trigger phrases: "add a property field",
  "property photos", "amenities", "nearby places", "property gallery", "property
  detail page", "unit types", "publish a property". Grounds work in the real
  Property model and its JSON columns.
---

# Purpose
Properties are the core domain entity. The `Property` model is large and
JSON-heavy (prices, unit types, nearby places, photo categories all stored as JSON
arrays). This skill captures the real relationships, fields, and admin/frontend
surfaces so agents edit the right place without inventing a `Room`/`Unit` model.

# When to Use
- Adding/editing property fields, unit types, amenities, or photos.
- Working on nearby places or the property location.
- Editing the property admin form or the public listing/detail views.

# Rules
- There is NO `Unit`/`Room` model. Room types are the `unit_types` JSON array on
  `properties` (keys like `studio`, `1br`, `2br`; see `Property::UNIT_TYPES`).
- `Property` casts these to `array`: `prices`, `unit_types`, `weekend_days`,
  `photo_categories`, `required_documents`, `nearby_places`. Keep them JSON; do not
  flatten to new columns without a migration + user confirmation.
- Relationships (real): `belongsTo(Media,'featured_image_id')`,
  `belongsToMany(Amenity,'amenity_property')`, `hasMany(PropertyPhoto)`,
  `hasMany(PromoRate)`, `hasMany(Booking)`. Preserve these.
- Photos: `PropertyPhoto` links `property_id` + `media_id` + `category` +
  `sort_order`. Gallery categories come from the property's `photo_categories`
  JSON. Do not break the featured-image (`featured_image_id`) relationship.
- Nearby places have TWO coexisting paths:
  1. MANUAL JSON on `properties.nearby_places` (`{name, category, distance_km,
     lat?, lng?}`), validated by [`PropertyRequest`](app/Http/Requests/PropertyRequest.php).
  2. PERSISTENT Geoapify POIs in the `places` + `property_places` tables
     ([`Place`](app/Models/Place.php), [`PropertyPlace`](app/Models/PropertyPlace.php)),
     reached via `Property::propertyPlaces()` (hasMany) and `Property::places()`
     (hasManyThrough).
  On the property page the persistent POIs (`$persistentPlaces`, `source='geoapify'`,
  ordered by `distance_m`) take PRECEDENCE; the manual `nearby_places` grouping is
  the fallback when there are none. There is no `NearbyPlace` model and no
  `NearbyPlacesService` — the service is [`GeoapifyService`](app/Services/GeoapifyService.php).
- Geoapify is fetched ONLY by [`FetchNearbyPlacesJob`](app/Jobs/FetchNearbyPlacesJob.php),
  dispatched ONLY by the admin resync action
  (`PropertyController::resyncNearbyPlaces()`, `POST admin/properties/{property}/resync-nearby-places`;
  the POI table renders via `nearbyPlaces()` at
  `GET admin/properties/{property}/nearby-places`, partial
  [`_nearby.blade.php`](resources/views/admin/properties/_nearby.blade.php)). There is
  NO automatic fetch on property create/update and no artisan/scheduled refresh.
  Never add an external API call to a request/render path.
- Distance display: persistent POIs use `PropertyPlace::$distance_formatted`
  (`"850m"` / `"1.2km"` / `null`) from the stored Haversine `distance_m`; manual JSON
  places use Haversine in `PropertyController::publicShow()` when a place carries
  `lat`/`lng`, else the stored `distance_km`. Don't duplicate either.
- Pricing lives in the `prices` JSON and is calculated ONLY by
  `BookingPricingService` — see the pricing skill. Never compute totals in a
  property view.
- Validate all property input via [`PropertyRequest`](app/Http/Requests/PropertyRequest.php)
  (slug regex, money-input thousands stripping, nearby_places category enum).

# Workflow
1. Read [`app/Models/Property.php`](app/Models/Property.php) to confirm the field is
   a column or a JSON key and how it is cast.
2. For admin edits, update the matching partial (`_photos`, `_pricing`, `_policy`,
   `_nearby`) and [`PropertyRequest`](app/Http/Requests/PropertyRequest.php) validation.
3. For new fields: add a migration, add to `$fillable` + `$casts`, then the form.
4. For frontend, edit the `resources/views/properties/*` view; keep pricing display
   reading `$property->prices` for preview only.
5. Run property/booking feature tests.

# Common Mistakes
- Creating a `Unit`/`Room` model or `units` table (removed — use `unit_types` JSON).
- Inventing a `NearbyPlace` model or a `NearbyPlacesService` — the real classes are
  [`Place`](app/Models/Place.php), [`PropertyPlace`](app/Models/PropertyPlace.php),
  and [`GeoapifyService`](app/Services/GeoapifyService.php).
- Treating manual `nearby_places` JSON as the only source (or deleting it) — both
  paths coexist and the JSON is the fallback.
- Adding a live external API call on the property detail page render.
- Duplicating pricing math in a property Blade view instead of using the service.
- Breaking the `featured_image_id` or `amenity_property` relationships.

# Validation
- `php artisan test --filter=CrudTest` and property-related tests.
- Re-read [`app/Models/Property.php`](app/Models/Property.php) casts/relationships.
- Confirm admin form partials and [`PropertyRequest`](app/Http/Requests/PropertyRequest.php) stay in sync.

# Related Files
- [`app/Models/Property.php`](app/Models/Property.php), [`app/Models/PropertyPhoto.php`](app/Models/PropertyPhoto.php), [`app/Models/Amenity.php`](app/Models/Amenity.php)
- [`app/Models/Place.php`](app/Models/Place.php), [`app/Models/PropertyPlace.php`](app/Models/PropertyPlace.php), [`app/Services/GeoapifyService.php`](app/Services/GeoapifyService.php), [`app/Jobs/FetchNearbyPlacesJob.php`](app/Jobs/FetchNearbyPlacesJob.php)
- [`app/Http/Controllers/PropertyController.php`](app/Http/Controllers/PropertyController.php), [`app/Http/Requests/PropertyRequest.php`](app/Http/Requests/PropertyRequest.php)
- [`resources/views/admin/properties/_photos.blade.php`](resources/views/admin/properties/_photos.blade.php), [`resources/views/admin/properties/_pricing.blade.php`](resources/views/admin/properties/_pricing.blade.php), [`resources/views/admin/properties/_policy.blade.php`](resources/views/admin/properties/_policy.blade.php), [`resources/views/admin/properties/_nearby.blade.php`](resources/views/admin/properties/_nearby.blade.php)
- [`resources/views/properties/index.blade.php`](resources/views/properties/index.blade.php), [`resources/views/properties/show.blade.php`](resources/views/properties/show.blade.php), [`resources/views/properties/_card.blade.php`](resources/views/properties/_card.blade.php), [`resources/views/properties/_booking-form.blade.php`](resources/views/properties/_booking-form.blade.php)
