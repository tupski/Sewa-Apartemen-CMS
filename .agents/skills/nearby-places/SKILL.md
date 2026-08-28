---
name: nearby-places
description: >-
  Use when working on "nearby places"/"what's around" data for properties, or
  when someone references the Geoapify integration. Trigger phrases: "nearby
  places", "what's around", "points of interest", "POI", "Geoapify", "places
  API", "distance to landmarks". CRITICAL: TWO paths coexist — manually-entered
  `nearby_places` JSON AND the persistent Geoapify pipeline (`places` +
  `property_places`). Geoapify is called ONLY from the queued job.
---

# Purpose
Keep agents grounded in the real, two-path nearby-places architecture.

Since 2026-08-28 the Geoapify pipeline described in
[`docs/GEOAPIFY-Nearby-Places-Integration.md`](docs/GEOAPIFY-Nearby-Places-Integration.md)
IS implemented (with deliberate divergences — see that file's Implementation
Status section). The older manually-entered `properties.nearby_places` JSON path
was NOT removed; it remains the fallback. Both must keep working.

# When to Use
- Editing how nearby places are stored, entered, fetched, or displayed.
- Any request mentioning Geoapify, a places API, POIs, or the property map.
- Reviewing distance/"what's around" behavior on the property page.
- Touching the admin POI table or the resync action.

# Rules

## The two paths and how they coexist
- **Persistent path (Geoapify)**: `places` table (POI records, deduped on
  `geoapify_place_id`) + `property_places` pivot (`property_id`, `place_id`,
  `source` enum `manual|geoapify` default `geoapify`, `distance_m`,
  `sort_order`; unique on `(property_id, place_id)`).
  Migrations: [`2026_08_28_000001_create_places_table.php`](database/migrations/2026_08_28_000001_create_places_table.php),
  [`2026_08_28_000002_create_property_places_table.php`](database/migrations/2026_08_28_000002_create_property_places_table.php).
- **Manual path (unchanged)**: `properties.nearby_places` JSON, entered in the
  property admin. `Property::NEARBY_CATEGORIES`, `$fillable`, and `$casts` are
  UNCHANGED — do not "clean these up".
- **Precedence on the public property page**: `PropertyController::publicShow()`
  passes both `$persistentPlaces` (pivot `source='geoapify'`, eager-loaded
  `place`, ordered by `distance_m`) and the untouched
  `$nearbyPlacesWithDistance`. [`resources/views/properties/show.blade.php`](resources/views/properties/show.blade.php)
  renders persistent POIs grouped by category **when present**, and falls back
  to the manual JSON grouping otherwise. Keep both branches intact.
- **`source='manual'` pivot rows are never deleted by the job.** The stale-row
  cleanup in the job is scoped to `source='geoapify'` only.

## Real class names (do not invent others)
- [`app/Services/GeoapifyService.php`](app/Services/GeoapifyService.php) — the HTTP client. **`NearbyPlacesService` still does NOT exist**; the design doc's name was not used.
- [`app/Jobs/FetchNearbyPlacesJob.php`](app/Jobs/FetchNearbyPlacesJob.php) — the ONLY caller of the service.
- [`app/Models/Place.php`](app/Models/Place.php), [`app/Models/PropertyPlace.php`](app/Models/PropertyPlace.php).
- `Property::propertyPlaces()` (hasMany) and `Property::places()` (hasManyThrough) in [`app/Models/Property.php`](app/Models/Property.php).
- There is **no artisan sync command** and **no scheduled refresh** for POIs.

## Non-negotiables
- **Never call Geoapify from a controller, view, or any render path.** Only
  `FetchNearbyPlacesJob::handle()` may call `GeoapifyService`. A feature test
  pins that a public property page render issues **zero** outbound HTTP requests.
- **Never add a live Places/geocoding call to a request path** — not for search,
  not for autocomplete, not "just once on first view".
- **API key comes from config only** — `config('services.geoapify.key')` /
  `config('services.geoapify.map_key')`, backed by `GEOAPIFY_API_KEY` /
  `GEOAPIFY_MAP_KEY` in [`config/services.php`](config/services.php). Never
  hardcode a key in PHP, Blade, or JS. The browser-side map key is read from the
  `#map-data` JSON payload rendered by the view, not embedded in
  [`resources/js/app.js`](resources/js/app.js).
- **The job must stay idempotent.** It uses `Place::updateOrCreate()` keyed on
  `geoapify_place_id` and `PropertyPlace::updateOrCreate()` keyed on
  `(property_id, place_id)`, all inside a `DB::transaction()`. Re-running it must
  not duplicate rows.
- **Dedupe on `geoapify_place_id`** (nullable, unique). Do not add a second
  identity strategy.
- **Respect the 24h cache** — key `geoapify_places_{$property->id}`. A cache hit
  skips the API call; failures are never cached. Only the admin resync action
  clears it (`Cache::forget()`), so resync always re-fetches.
- **The job swallows its own failures.** It catches `\RuntimeException` from the
  service, logs, and returns; nothing escapes `handle()`. It also early-returns
  with a `Log::warning` when `latitude`/`longitude` is null or the API key is
  blank. Keep that behavior — a POI fetch must never break an admin request.
- **Do not duplicate the Haversine distance math.** `distance_m` is computed once
  in the job; `PropertyPlace::getDistanceFormattedAttribute()` handles display
  (`"850m"` / `"1.2km"` / `null`).
- **Unmapped categories are intentionally excluded.** `GeoapifyService::mapCategory()`
  (private) maps Geoapify category strings to `Property::NEARBY_CATEGORIES`
  display labels and returns null for anything unmapped; those POIs are dropped.
  To support a new POI type, extend that map — do not bypass it.

## The only fetch trigger
A Geoapify fetch happens **only** via the admin resync action. There is **no**
automatic fetch on property create or update.

- `PropertyController::nearbyPlaces(Property $property)` → `GET admin/properties/{property}/nearby-places`, route name `admin.properties.nearby-places` — renders the POI table partial ordered by `distance_m` ASC.
- `PropertyController::resyncNearbyPlaces(Property $property)` → `POST admin/properties/{property}/resync-nearby-places`, route name `admin.properties.resync-nearby-places` — validates coordinates and the API key, `Cache::forget()`s the key, dispatches `FetchNearbyPlacesJob`, redirects back with a flash message (error flash on validation failure).
- Both routes live inside the existing `['auth','verified','admin']` group under the `slug('admin_prefix','admin')` prefix in [`routes/web.php`](routes/web.php) — never hardcode `/admin`.
- `PropertyController::edit()` eager-loads `propertyPlaces.place`; keep it to avoid N+1.

## Operational caveats to state honestly
- **The queue is `sync`.** `.env` sets `QUEUE_CONNECTION=sync` while
  [`config/queue.php`](config/queue.php) defaults to `database`. Under `sync`
  the job runs **inline during the admin resync request**, so the job's
  `$tries = 3` / `$backoff = [30, 120, 300]` / `$timeout = 60` only take effect
  once a real driver plus `php artisan queue:work` are in place. This is a
  deployment consideration, not a code defect — do not "fix" it in code.
- **`GEOAPIFY_API_KEY` is currently blank.** Until an operator sets it, the job
  early-returns and the map falls back to OSM tiles. See
  [`docs/geoapify-setup.md`](docs/geoapify-setup.md).
- `Property` uses `SoftDeletes`, so the `property_places` FK cascade only fires
  on `forceDelete()`. Soft-deleted properties keep their pivot rows.

# Workflow
1. Determine which path the requirement touches: persistent Geoapify rows, the
   manual `nearby_places` JSON, or the map. Read the relevant file before editing.
2. Display/grouping changes → [`resources/views/properties/show.blade.php`](resources/views/properties/show.blade.php)
   (keep BOTH the persistent branch and the manual fallback) or
   [`resources/views/admin/properties/_nearby.blade.php`](resources/views/admin/properties/_nearby.blade.php)
   for the admin table.
3. Fetch/normalization changes → [`app/Services/GeoapifyService.php`](app/Services/GeoapifyService.php)
   (request shape, retries, `mapCategory()`); persistence/dedupe/cleanup changes
   → [`app/Jobs/FetchNearbyPlacesJob.php`](app/Jobs/FetchNearbyPlacesJob.php).
   Never move fetch logic toward the controller.
4. Map changes → the `#map-data` payload in `show.blade.php` plus
   `initPropertyMap()` in [`resources/js/app.js`](resources/js/app.js). Leaflet
   1.9.4 loads from CDN with SRI in
   [`resources/views/layouts/frontend.blade.php`](resources/views/layouts/frontend.blade.php)
   — do not add an npm map dependency or swap the provider.
5. Schema changes → new additive migration. Adding an artisan sync command, a
   scheduled refresh, or an automatic fetch on save is an infrastructure/cost
   change — get user approval first (per AGENTS.md §20).
6. New UI strings → [`lang/en.json`](lang/en.json) and [`lang/id.json`](lang/id.json).

# Common Mistakes
- Calling `GeoapifyService` (or any HTTP client) from `PropertyController`, a
  Blade view, or a middleware — the job is the only permitted caller.
- Deleting `source='manual'` pivot rows during the job's stale-row cleanup.
- Removing the manual `nearby_places` JSON fallback branch from
  `show.blade.php`, or "tidying" `Property::NEARBY_CATEGORIES` / `$casts`.
- Forgetting the 24h cache: adding a fetch path that ignores
  `geoapify_places_{id}`, or caching a failed response.
- Assuming the queue is async — under `sync` the job runs inline and retries
  never happen.
- Re-implementing the Haversine distance calculation in a controller, view, or
  accessor instead of reading `distance_m` / `distance_formatted`.
- Hardcoding the map key in [`resources/js/app.js`](resources/js/app.js) or a
  Blade template instead of passing it through the `#map-data` payload.
- Inventing `NearbyPlacesService`, an artisan `nearby-places:sync` command, a
  scheduled refresh, or an `Admin\` namespace POI controller — none exist.
- Breaking idempotency by switching `updateOrCreate()` to `create()`, or
  changing the dedupe key away from `geoapify_place_id`.

# Validation
- Confirm no external API call was added to a request path — the property-page
  render must still issue zero outbound HTTP requests.
- Confirm the persistent branch AND the manual JSON fallback both still render on
  the property page, grouped by category with distances.
- Confirm the job is still transactional, idempotent, and leaves `source='manual'`
  rows untouched.
- Confirm no API key appears in JS, Blade, or committed config.
- Run `php artisan test --filter=GeoapifyNearbyPlaces` and
  `php artisan test --filter=PropertyNearbyPlaces`, then the full suite
  (`php artisan test`). Note: `SecurityTest` has one pre-existing, date-dependent
  failure unrelated to POIs.

# Related Files
- [`docs/GEOAPIFY-Nearby-Places-Integration.md`](docs/GEOAPIFY-Nearby-Places-Integration.md) (design spec + Implementation Status / divergences)
- [`docs/geoapify-setup.md`](docs/geoapify-setup.md) (operator setup, troubleshooting)
- [`app/Services/GeoapifyService.php`](app/Services/GeoapifyService.php)
- [`app/Jobs/FetchNearbyPlacesJob.php`](app/Jobs/FetchNearbyPlacesJob.php)
- [`app/Models/Place.php`](app/Models/Place.php), [`app/Models/PropertyPlace.php`](app/Models/PropertyPlace.php)
- [`app/Models/Property.php`](app/Models/Property.php) (`nearby_places` JSON cast, `NEARBY_CATEGORIES`, `propertyPlaces()`, `places()`)
- [`app/Http/Controllers/PropertyController.php`](app/Http/Controllers/PropertyController.php) (`nearbyPlaces()`, `resyncNearbyPlaces()`, `publicShow()`, `edit()`)
- [`routes/web.php`](routes/web.php) (admin POI routes)
- [`config/services.php`](config/services.php) (`geoapify` block), `.env.example`
- [`database/migrations/2026_08_28_000001_create_places_table.php`](database/migrations/2026_08_28_000001_create_places_table.php), [`database/migrations/2026_08_28_000002_create_property_places_table.php`](database/migrations/2026_08_28_000002_create_property_places_table.php)
- [`resources/views/admin/properties/_nearby.blade.php`](resources/views/admin/properties/_nearby.blade.php)
- [`resources/views/properties/show.blade.php`](resources/views/properties/show.blade.php), [`resources/views/layouts/frontend.blade.php`](resources/views/layouts/frontend.blade.php), [`resources/js/app.js`](resources/js/app.js)
- [`tests/Feature/GeoapifyNearbyPlacesTest.php`](tests/Feature/GeoapifyNearbyPlacesTest.php), [`tests/Feature/PropertyNearbyPlacesTest.php`](tests/Feature/PropertyNearbyPlacesTest.php)
