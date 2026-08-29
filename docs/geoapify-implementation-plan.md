# Geoapify Persistent-POI Pipeline — Implementation Plan

> **Status**: PLAN ONLY — no application code written.
> **Prepared for**: Downstream implementation phases (2–7).
> **Ground truth**: All claims below are verified against actual file reads.

---

## 1. Current State (Verified)

### 1.1 Coordinates

`properties` table has `latitude DECIMAL(10,8)` and `longitude DECIMAL(11,8)` (both nullable), added in [`database/migrations/2026_08_11_151813_create_properties_table.php`](database/migrations/2026_08_11_151813_create_properties_table.php) lines 23–24. Both are in `$fillable` ([`app/Models/Property.php`](app/Models/Property.php) lines 32–33) and cast as `decimal:8` (lines 55–56). Validated in [`app/Http/Requests/PropertyRequest.php`](app/Http/Requests/PropertyRequest.php) lines 66–67 (`nullable|numeric|between`).

### 1.2 Manual `nearby_places` JSON

Added in [`database/migrations/2026_08_18_000000_add_property_detail_fields.php`](database/migrations/2026_08_18_000000_add_property_detail_fields.php) line 39 as `json` nullable. Cast as `array` on the model (line 65). In `$fillable` (line 44).

**Shape** (inferred from admin form): each item is `{name, category, lat?, lng?, distance_km?}`.

**Admin entry**: [`resources/views/admin/properties/_policy.blade.php`](resources/views/admin/properties/_policy.blade.php) lines 7–20, included by both `create.blade.php` and `edit.blade.php` via `@include('admin.properties._policy')`. The categories defined there are:
```
Mall/Shopping, Restaurant/Food, Transport, Education, Hospital/Health,
Recreation, Hotel, Nearby Places, Transportation, Entertainment/Attraction, Others
```
Included via [`resources/views/admin/properties/edit.blade.php`](resources/views/admin/properties/edit.blade.php) line 300.

### 1.3 Current Display (show.blade.php)

[`resources/views/properties/show.blade.php`](resources/views/properties/show.blade.php) lines 27–41:
- `$nearbyPlacesWithDistance` injected by controller (Haversine `distance_formatted` + `distance_m`).
- Falls back to raw `property->nearby_places` if variable missing.
- Groups by `$place['category']` into `$nearbyGroups`.
- `$hasMap = $property->latitude && $property->longitude` (line 40).
- `$nearbyWithCoords` = places where `lat`/`lng` are present (line 41).

**Map already uses Leaflet 1.9.4** loaded via CDN: line 46 `<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">`. JS loads it via `window.loadScript(LEAFLET_SRC)` (line 915). Uses **OpenStreetMap** tiles (not Geoapify tiles currently).

### 1.4 PropertyController `publicShow()`

Injects `$nearbyPlacesWithDistance` with Haversine distance computation from the JSON array. No external API call. No `Place` or `PropertyPlace` model referenced.

### 1.5 Queue/Cache Reality

**Critical finding**: [`.env`](.env) line 50: `QUEUE_CONNECTION=sync`. [`.env`](.env) line 53: `CACHE_STORE=file`.

`config/queue.php` line 16 default is `database` but env overrides to `sync`. `config/cache.php` line 18 default is `database` but env overrides to `file`.

`0001_01_01_000002_create_jobs_table.php` exists — the `jobs` table migration is present, but the `sync` driver means any dispatched job runs **inline on the HTTP request** unless the driver is changed.

No `app/Jobs/` directory exists. No existing queued jobs, events, or listeners.

### 1.6 Config/Services

`config/services.php` has no Geoapify entry. No `GEOAPIFY_*` keys in `.env`.

### 1.7 Middleware / Admin Route Pattern

`admin` middleware alias → [`App\Http\Middleware\EnsureUserIsAdmin`](bootstrap/app.php:45). Admin routes grouped under `slug('admin_prefix', 'admin')` at [`routes/web.php`](routes/web.php) line 79. Named `admin.*`. Property routes: `Route::resource('properties', PropertyController::class)` at line 116. **Never hardcode `/admin`** — always use `route('admin.properties.*')`.

### 1.8 What Does NOT Exist

- No `places` table or migration
- No `property_places` table or migration
- No `Place` or `PropertyPlace` model
- No `GeoapifyService` or `NearbyPlacesService`
- No `SyncNearbyPlaces` job
- No `app/Jobs/` directory
- No Geoapify API key in config or env
- No Geoapify tiles in the map (currently OSM)
- No admin "resync" button for Geoapify

---

## 2. Spec Components: Exists vs. Must Build

| Component | Spec Says | Exists Today | Action |
|---|---|---|---|
| `latitude`/`longitude` on `properties` | Required | ✅ Yes (decimal columns, fillable, validated) | None needed |
| `nearby_places` JSON (manual) | Keep intact | ✅ Yes | Preserve as-is |
| `places` table | New | ❌ No | Create migration + model |
| `property_places` pivot | New | ❌ No | Create migration + model |
| `GeoapifyService` | New | ❌ No | Create service |
| `SyncNearbyPlaces` job | New | ❌ No | Create job + Jobs dir |
| Caching of fetch results | New | ❌ No | Add to service (file driver works) |
| Admin "resync" action | New | ❌ No | New route + controller method |
| Admin view of found places | New | ❌ No | Add to `edit.blade.php` or new partial |
| `source` distinction (manual vs geoapify) | New | ❌ No | Implement via `source` enum on pivot |
| Leaflet map (existing) | Reuse | ✅ Yes (Leaflet 1.9.4 CDN) | Extend to show POI markers from DB |
| Geoapify map tiles | Optional upgrade | ❌ Not used | Replace OSM tiles (Decision B) |
| Queue worker | Required for async | ❌ Not running (sync) | Must change driver (Decision A) |
| `config/services.php` Geoapify entry | New | ❌ No | Add Places API key + map tile key |

---

## 3. Target Architecture

```
Admin "Resync" action (POST /admin/properties/{id}/nearby-places/sync)
  └─> PropertyController::syncNearbyPlaces()
        └─> dispatches SyncNearbyPlacesJob (queued)

Property saved with coord change (optional trigger)
  └─> Property::booted() observer
        └─> dispatches SyncNearbyPlacesJob (queued)

SyncNearbyPlacesJob (app/Jobs/)
  ├─ Guards: property has lat+lng, not already running (cache lock)
  └─> GeoapifyService::fetchNearbyPlaces(lat, lng, radius, categories)
        ├─ HTTP GET Geoapify Places API (server-side only)
        ├─ Handles: timeout, rate-limit (429), API error, invalid response, retry
        ├─ Normalises response → Place DTOs
        └─> Persists to `places` (upsert by geoapify_place_id)
              └─> Persists to `property_places` (upsert, dedupe)
                    └─> Cache::put("property_places.{$propertyId}", ..., 24h)
                          └─> Cache::forget("property_places.{$propertyId}") on resync

Property detail page (publicShow)
  ├─ Load from cache/DB: property_places + places (no API call)
  ├─ Merge with manual nearby_places JSON (manual takes priority for same name+category)
  └─> Blade view renders:
        ├─ Existing Leaflet map (extended with POI markers)
        ├─ Grouped table/list by category
        └─ Badge indicating source (manual / geoapify)
```

---

## 4. Schema Proposals

### 4.1 `places` table

```sql
places
  id               BIGINT UNSIGNED PK AUTO_INCREMENT
  geoapify_place_id VARCHAR(191) UNIQUE NOT NULL   -- Geoapify feature ID for dedupe
  name             VARCHAR(255) NOT NULL
  category         VARCHAR(100) NOT NULL            -- normalised to internal category slug
  lat              DECIMAL(10,8) NOT NULL
  lng              DECIMAL(11,8) NOT NULL
  address          VARCHAR(500) NULL
  city             VARCHAR(100) NULL
  country          VARCHAR(100) NULL
  created_at       TIMESTAMP
  updated_at       TIMESTAMP

INDEXES:
  UNIQUE(geoapify_place_id)                        -- dedupe key
  INDEX(category)                                  -- filter by category
  INDEX(lat, lng)                                  -- possible geo-range filter
```

**Dedupe key**: `geoapify_place_id` (Geoapify's own stable feature identifier). Used in `upsert()` to avoid duplicate rows when the same POI appears near multiple properties.

### 4.2 `property_places` pivot table

```sql
property_places
  id               BIGINT UNSIGNED PK AUTO_INCREMENT
  property_id      BIGINT UNSIGNED NOT NULL FK → properties.id (CASCADE DELETE)
  place_id         BIGINT UNSIGNED NOT NULL FK → places.id (CASCADE DELETE)
  source           ENUM('geoapify','manual') NOT NULL DEFAULT 'geoapify'
  distance_m       INT UNSIGNED NULL               -- computed Haversine distance in metres
  fetched_at       TIMESTAMP NULL                  -- when this link was last fetched
  created_at       TIMESTAMP
  updated_at       TIMESTAMP

INDEXES:
  UNIQUE(property_id, place_id)                    -- no duplicate links
  INDEX(property_id)                               -- primary access pattern
  INDEX(source)                                    -- filter manual vs geoapify
```

**Notes**:
- `distance_m` is stored here (property→POI varies per property; the `places` row is shared).
- `source = 'manual'` rows will be added when (optionally) backfilling existing `nearby_places` JSON entries that have `lat`+`lng`; for now manual JSON remains the canonical manual source.
- The `UNIQUE(property_id, place_id)` constraint makes upserts idempotent.

---

## 5. Decision Resolutions (A–E)

### Decision A — Queue Driver

**Problem**: [`.env`](.env) `QUEUE_CONNECTION=sync` means any `SyncNearbyPlacesJob::dispatch()` runs **synchronously on the HTTP request**, blocking the admin and violating requirement 4 (no heavy Geoapify call on admin HTTP request).

**Resolution**: **Require `QUEUE_CONNECTION=database` for this feature to function asynchronously.**

- The `jobs` table migration already exists (`0001_01_01_000002_create_jobs_table.php`).
- The `database` queue connection is already configured in `config/queue.php` lines 39–45.
- Implementation steps:
  1. Document in `.env.example` that `QUEUE_CONNECTION=database` is required for async POI fetching.
  2. In `SyncNearbyPlacesJob`, check for the queue driver at dispatch time; if `sync`, log a warning but still run (graceful degradation in dev).
  3. The production deployment guide must include `php artisan queue:work --daemon` or a supervisor config.
- **Fallback for sync driver**: if `QUEUE_CONNECTION=sync`, the job runs inline. This is acceptable for development and single-property deploys with small radius/few categories. The admin "resync" button must show a spinner and display any error.
- **No Redis/Beanstalkd requirement** — `database` driver is sufficient for this workload (low volume, no sub-second latency needed).

**Recommended default**: `QUEUE_CONNECTION=database` in production; the `sync` fallback is self-documenting for dev.

### Decision B — Map Rendering Library

**Finding**: The property page **already uses Leaflet 1.9.4** loaded from CDN (`unpkg.com/leaflet@1.9.4`) via [`show.blade.php`](resources/views/properties/show.blade.php) line 46. The JS `initDetailMap()` function already places a property marker and POI markers from `$nearbyWithCoords`.

**Resolution**: **Reuse existing Leaflet 1.9.4. Do NOT introduce MapLibre GL or any new npm map library.**

Rationale:
- Leaflet is already working, Turbo-compatible (loaded on-demand via `loadScript`), and responsive.
- MapLibre GL adds ~600 KB JS + WebGL requirement; not warranted for this use case.
- The only change needed is: (a) extend the existing `initDetailMap()` to also read POI markers from a server-rendered JS data variable containing the persisted `property_places` data, and (b) optionally swap OSM tiles for Geoapify raster tiles (see Decision C).

**No new npm dependency required for map rendering.**

If Geoapify raster tiles are used (optional), the `leaflet` package can be **optionally** npm-installed for TypeScript types only — but since the project already loads Leaflet from CDN, this is unnecessary. Keep using the CDN load pattern.

### Decision C — API Key Exposure

**Two distinct keys are needed**:

1. **Places API key** (server-side only): used by `GeoapifyService` for the `/v2/places` endpoint. **Must never reach the browser.** Stored as `GEOAPIFY_PLACES_KEY` in `.env`, accessed via `config('services.geoapify.places_key')`.

2. **Map tile key** (browser-visible): Geoapify map tiles require a key in the tile URL (`https://maps.geoapify.com/v1/tile/{style}/{z}/{x}/{y}.png?apiKey=KEY`). This key **must** reach the browser to render tiles. It is exposed in the rendered Blade HTML (a standard, accepted practice for map tile providers — the key is rate-limited by domain in Geoapify's dashboard).

**Recommended approach**:
- Add to `config/services.php`:
  ```php
  'geoapify' => [
      'places_key' => env('GEOAPIFY_PLACES_KEY'),          // server-side only
      'map_tile_key' => env('GEOAPIFY_MAP_TILE_KEY'),       // browser-safe
      'places_radius' => env('GEOAPIFY_PLACES_RADIUS', 1000), // metres
  ],
  ```
- The `places_key` is used exclusively in `GeoapifyService` (PHP). Never echoed to Blade.
- The `map_tile_key` is passed to the Blade view as a scoped PHP variable (`$mapTileKey`) via the controller's `publicShow()` method, rendered into an inline JS constant (escaped): `const GEOAPIFY_TILE_KEY = '{{ $mapTileKey }}';`. The tile URL is assembled in JS.
- **One key or two**: recommend **two separate keys** with different domain restrictions in the Geoapify dashboard. The map tile key can be domain-restricted to the production domain; the places key should have no browser domain restriction (server-side). This is a security best practice.
- If the operator wants only one key (simpler setup), a single `GEOAPIFY_KEY` can serve both, but document the tradeoff.
- `.env.example` should add:
  ```
  GEOAPIFY_PLACES_KEY=
  GEOAPIFY_MAP_TILE_KEY=
  ```

**If `GEOAPIFY_MAP_TILE_KEY` is empty**, the map falls back to OpenStreetMap tiles (current behavior). This ensures backward compatibility.

### Decision D — Source of Truth: Manual JSON + Geoapify DB

**The two sources coexist**:

| Source | Location | Authoritative for |
|---|---|---|
| Manual | `properties.nearby_places` JSON | Admin-curated, high-confidence, always shown |
| Geoapify | `property_places` + `places` tables | Auto-discovered, lower confidence, shown if no manual match |

**Merge strategy for `publicShow()`**:

1. Load `property_places` (eager) with `place` relationship, ordered by `distance_m`.
2. Convert both sources to a unified DTO shape: `{name, category, lat, lng, distance_m, distance_formatted, source, address}`.
3. Build a dedupe key per entry: `strtolower(trim($name)) . '|' . $category`.
4. Manual entries take priority: iterate manual JSON first, add all to merged set, track dedupe keys.
5. Append Geoapify entries where the dedupe key is not already present.
6. Group by category for display.
7. Mark each entry with `source` (`manual` or `geoapify`) so the view can show a badge if desired.

**The `properties.nearby_places` JSON column is never modified or cleared by the Geoapify pipeline.** The JSON is the manual admin's domain.

**If both sources are empty** (no manual, no geoapify), the "What's Around" section is hidden (current behavior preserved).

### Decision E — Fetch Trigger Policy

**Two trigger modes**:

1. **Explicit admin "Resync" button** (primary, recommended): Admin clicks "Resync from Geoapify" on the property edit page. This dispatches `SyncNearbyPlacesJob`. Requires coordinates to be set.

2. **Optional: dispatch on coordinate change** (secondary, opt-in): In `PropertyController::update()`, detect if `latitude`/`longitude` changed and dispatch the job if they did. This should be **off by default** and documented, because:
   - Coordinates might be edited multiple times before publishing.
   - Unexpected API spend on every coordinate tweak.
   - The admin might prefer to control when a fetch happens.

**Recommended default**: Explicit admin resync only. Coordinate-change dispatch is added as a commented-out block with documentation.

**Idempotency**: `SyncNearbyPlacesJob` is idempotent — it upserts by `geoapify_place_id`; re-running it for the same property produces the same result. A cache lock (`Cache::lock("sync_nearby_places.{$propertyId}")`) prevents concurrent duplicate jobs.

**Cache window**: After a successful fetch, cache the `property_places` result for 24 hours (configurable via `GEOAPIFY_CACHE_TTL` env). The resync action clears the cache before dispatching.

---

## 6. Full File List: Create vs. Modify

### Phase 2 — Database + Models

**CREATE**:
- `database/migrations/YYYY_MM_DD_000000_create_places_table.php` — `places` table schema
- `database/migrations/YYYY_MM_DD_000001_create_property_places_table.php` — pivot table + FKs + indexes
- `app/Models/Place.php` — Eloquent model; `$fillable`, `$casts`, `propertyPlaces()` relationship
- `app/Models/PropertyPlace.php` — pivot model; `$fillable`, `$casts`, `place()` + `property()` relationships

**MODIFY**:
- `app/Models/Property.php` — add `places()` hasManyThrough or `propertyPlaces()` hasMany relationship; add `booted()` observer hook (commented dispatch, Decision E)

### Phase 3 — Service + Config

**CREATE**:
- `app/Services/GeoapifyService.php` — ALL HTTP comms with Geoapify Places API; fetch, normalise, handle errors; never called from controller/view directly
- `app/Http/Requests/PropertySyncNearbyPlacesRequest.php` — FormRequest for admin resync (authorize admin, validate property_id)

**MODIFY**:
- `config/services.php` — add `geoapify` array (places_key, map_tile_key, places_radius)
- `.env.example` — add `GEOAPIFY_PLACES_KEY=`, `GEOAPIFY_MAP_TILE_KEY=`, `GEOAPIFY_PLACES_RADIUS=1000`, `GEOAPIFY_CACHE_TTL=86400`

### Phase 4 — Queue Job

**CREATE**:
- `app/Jobs/SyncNearbyPlacesJob.php` — queued job; reads config, calls `GeoapifyService`, persists to `places`+`property_places`, manages cache; idempotent; retry/backoff

**MODIFY**:
- (nothing else — job is self-contained)

### Phase 5 — Admin

**CREATE**:
- `resources/views/admin/properties/_nearby_places_geoapify.blade.php` — partial: shows found Geoapify POIs in a table, "Resync" button with Alpine spinner, sync status/timestamp, last-fetched indicator

**MODIFY**:
- `app/Http/Controllers/PropertyController.php` — add `syncNearbyPlaces(Property $property)` method (POST, dispatches job, returns JSON response for Alpine); pass `$mapTileKey` to `publicShow()` view data
- `routes/web.php` — add `POST admin/properties/{property}/nearby-places/sync` route named `admin.properties.nearby-places.sync` inside the existing admin group
- `resources/views/admin/properties/edit.blade.php` — include `_nearby_places_geoapify` partial (tab or section after `_policy`)
- `resources/views/admin/properties/_policy.blade.php` — add visual distinction / label "Manual Entries" to the existing nearby_places section (no functional change)

### Phase 6 — Frontend

**MODIFY**:
- `app/Http/Controllers/PropertyController.php` — in `publicShow()`: load `property_places` with `place` eager; merge with manual JSON (Decision D merge strategy); pass `$nearbyPlacesWithDistance` (unified DTO), `$mapTileKey` to view
- `resources/views/properties/show.blade.php` — extend `initDetailMap()` to optionally use Geoapify tile URL when `GEOAPIFY_TILE_KEY` is set; add source badge (`manual`/`geoapify`) to each place row; ensure mobile-safe layout (no overflow); no horizontal tables on mobile
- `resources/js/app.js` — no changes needed unless `loadScript` needs updating for Geoapify tile CDN

### Phase 7 — Tests

**CREATE**:
- `tests/Feature/GeoapifyServiceTest.php` — unit-style feature test: mock HTTP client, test normalisation, error handling (429, timeout, invalid JSON), retry logic
- `tests/Feature/SyncNearbyPlacesJobTest.php` — test job dispatch, idempotency, cache lock, upsert behaviour
- `tests/Feature/PropertyNearbyPlacesMergeTest.php` — test the merge/dedupe logic (manual + geoapify sources)
- `tests/Feature/AdminPropertySyncTest.php` — test admin resync route (auth, dispatch, response JSON)

**MODIFY**:
- Existing `tests/Feature/CrudTest.php` — verify no regression on property create/edit/show

### Lang Keys

**MODIFY**:
- `lang/en.json` — add keys: `"Nearby Places (Geoapify)"`, `"Sync from Geoapify"`, `"Syncing..."`, `"Last synced"`, `"Never synced"`, `"Sync failed"`, `"Source: Manual"`, `"Source: Geoapify"`, `"Sync queued"`, `"Coordinates required to sync"`, `"Queue driver must be set to database for async sync"`
- `lang/id.json` — add Indonesian equivalents

---

## 7. Phased Implementation Sequence

```
Phase 2 (DB + Models)
  ├─ Migration: create_places_table
  ├─ Migration: create_property_places_table
  ├─ Model: Place (fillable, casts, relationships)
  ├─ Model: PropertyPlace (fillable, casts, relationships)
  └─ Modify: Property model (add relationships, booted hook placeholder)

Phase 3 (Service + Config)
  ├─ config/services.php: add geoapify block
  ├─ .env.example: add Geoapify keys
  ├─ GeoapifyService: fetch, normalise, error handling, retry
  └─ PropertySyncNearbyPlacesRequest: FormRequest

Phase 4 (Queue Job)
  └─ SyncNearbyPlacesJob: reads service, persists, cache, idempotent

Phase 5 (Admin)
  ├─ PropertyController: syncNearbyPlaces() method
  ├─ routes/web.php: add sync route
  ├─ admin/properties/_nearby_places_geoapify.blade.php: partial
  └─ admin/properties/edit.blade.php: include partial

Phase 6 (Frontend)
  ├─ PropertyController::publicShow(): merge logic + tile key
  ├─ show.blade.php: extend map JS, source badges, mobile layout
  └─ lang/en.json + lang/id.json: new keys

Phase 7 (Tests)
  ├─ GeoapifyServiceTest
  ├─ SyncNearbyPlacesJobTest
  ├─ PropertyNearbyPlacesMergeTest
  └─ AdminPropertySyncTest
```

---

## 8. Change Impact Analysis (per AGENTS.md §22)

### Files Affected
- **New**: 2 migrations, 2 models, 1 service, 1 job, 1 FormRequest, 1 admin partial, 4 test files
- **Modified**: `PropertyController` (2 methods + route), `routes/web.php` (1 route), `config/services.php`, `.env.example`, `edit.blade.php`, `_policy.blade.php`, `show.blade.php`, `lang/en.json`, `lang/id.json`, `app/Models/Property.php` (relationship + observer hook)

### Database Affected
- **2 new tables**: `places` and `property_places`
- **No existing table modifications** — `properties.nearby_places` JSON column is untouched
- **No data migration of existing JSON** — existing manual entries stay in `nearby_places`; no backfill is needed or planned

### API Affected
- New admin POST endpoint: `admin/properties/{property}/nearby-places/sync`
- No change to public property page URL or any existing endpoint contract
- `publicShow()` view data gains new variables (`$mapTileKey`); existing variables unchanged

### Booking Affected
- **None** — this pipeline is isolated. No pricing, voucher, status, or booking flow touched.

### SEO Affected
- **None** — no slug, canonical, sitemap, or heading changes.
- The `"What's Around"` section content may change (more POIs shown), but this is content enrichment, not structural SEO change.

### Performance Affected
- **publicShow()**: adds one eager-loaded query (`property_places` with `place`) per page load. Mitigated by 24h cache.
- **No blocking API calls on page render** — Geoapify is called only in the background job.
- **N+1 risk**: use `with('places')` or `with('propertyPlaces.place')` — must be explicit in the controller.
- **Cache**: `file` driver is sufficient for this workload (low write frequency, property-keyed).

### Security Affected
- **`GEOAPIFY_PLACES_KEY`**: server-side only, never echoed. Standard env-key hygiene.
- **`GEOAPIFY_MAP_TILE_KEY`**: browser-visible by design (Geoapify's model). Should be domain-restricted in Geoapify dashboard.
- **New admin POST route**: protected by `['auth', 'verified', 'admin']` middleware via the existing group.
- **Response from Geoapify** is untrusted external data — all fields must be sanitized/escaped before storage and before echoing in Blade (`{{ }}` auto-escapes strings).
- **No new upload paths, no new SSRF surface** (outbound HTTP from server to Geoapify is outbound-only, no redirect following needed).
- **Queue job**: runs as web server process; no elevated permissions needed.

### Tests Required
- 4 new test files (Phase 7 above)
- Regression coverage: existing `CrudTest` property create/edit must still pass
- SQLite in-memory: `places` and `property_places` migrations must be SQLite-compatible (DECIMAL, ENUM, standard FKs — all supported)

---

## 9. New Dependencies

### Composer
None required. `GeoapifyService` uses Laravel's built-in `Http` facade (`Illuminate\Support\Facades\Http`) which is already available.

### NPM
None required. Leaflet is already loaded from CDN (existing pattern in `show.blade.php`). No new npm packages needed.

**Total new external dependencies: 0.**

---

## 10. Discrepancies Found

### D-1: AGENTS.md §4 "Notable Absences" is partly stale
AGENTS.md states "No `NearbyPlace` / `Place` model" — this is currently true but will become false after Phase 2. The skill file will need updating after implementation.

### D-2: Admin categories in `_policy.blade.php` don't match spec categories
The spec (`docs/GEOAPIFY-Nearby-Places-Integration.md`) lists Geoapify categories: Kuliner, Cafe, Belanja, Hiburan, Transportasi, Kesehatan, Keuangan, Pendidikan, Tempat Ibadah, Lifestyle, Lainnya.

The admin form (`_policy.blade.php` lines 8–20) uses different English category slugs: `Mall/Shopping`, `Restaurant/Food`, `Transport`, `Education`, `Hospital/Health`, `Recreation`, `Hotel`, `Nearby Places`, `Transportation`, `Entertainment/Attraction`, `Others`.

**Impact**: When merging manual and Geoapify sources (Decision D), the `category` values from Geoapify's API (raw strings like `"catering.restaurant"`) need to be normalised to the project's English category set. The `GeoapifyService` normaliser must map Geoapify category slugs → internal categories. The spec's Indonesian labels are display labels only; the internal key set should match the existing admin form's English values for consistency.

**Recommendation**: Normalise Geoapify categories to the existing English set (`Mall/Shopping`, `Restaurant/Food`, etc.) plus add any missing ones (`Cafe`, `Bank/ATM`, `Place of Worship`, `Lifestyle`). Do NOT change the existing category strings in `_policy.blade.php`; only add new ones if needed.

### D-3: `show.blade.php` uses CDN Leaflet, not npm Leaflet
The property map loads Leaflet from `unpkg.com` CDN, not via `npm install leaflet`. This is the existing pattern and should be maintained. Any plan that suggests `npm install leaflet` is inconsistent with the existing code.

### D-4: `QUEUE_CONNECTION=sync` but `config/queue.php` default is `database`
AGENTS.md §14 correctly says "queue config defaults to `database` but `.env` overrides to `sync`". The implementation must handle both states and document the requirement explicitly. This is not a discrepancy in AGENTS.md, just a runtime state agents must not silently assume.

### D-5: `CACHE_STORE=file` but `config/cache.php` default is `database`
Same pattern as D-4 for cache. `file` cache is fine for this feature's needs. No discrepancy to report, just noting it works as-is.

### D-6: `PropertyNearbyPlacesTest.php` file listed in open tabs but not in migrations list
Open tabs show `tests/Feature/PropertyNearbyPlacesTest.php` — this file appears to have been created (possibly in a prior interrupted session). Implementation phase should check its content before writing new tests to avoid duplication.

---

## 11. Environment Variables Required

Add to `.env.example` (and configure in production `.env`):

```ini
# Geoapify Integration
# Places API key — server-side only, never expose to browser
GEOAPIFY_PLACES_KEY=
# Map tile key — browser-visible, restrict by domain in Geoapify dashboard
GEOAPIFY_MAP_TILE_KEY=
# Search radius in metres (default 1000 = 1km)
GEOAPIFY_PLACES_RADIUS=1000
# Cache TTL for fetched places in seconds (default 86400 = 24h)
GEOAPIFY_CACHE_TTL=86400
# Queue driver MUST be 'database' (not 'sync') for async POI fetching
QUEUE_CONNECTION=database
```

---

## 12. Geoapify Category Normalisation Map (for GeoapifyService)

Geoapify returns category strings like `catering.restaurant`, `commercial.shopping_mall`, etc. These must be mapped to the project's internal category set:

| Geoapify category prefix | Internal category |
|---|---|
| `catering.restaurant`, `catering.fast_food` | `Restaurant/Food` |
| `catering.cafe` | `Restaurant/Food` (or add `Cafe`) |
| `commercial.shopping_mall`, `commercial.supermarket` | `Mall/Shopping` |
| `leisure.park`, `tourism.attraction` | `Recreation` |
| `entertainment.*` | `Entertainment/Attraction` |
| `public_transport.*`, `transport.*` | `Transport` |
| `healthcare.*` | `Hospital/Health` |
| `education.*` | `Education` |
| `accommodation.hotel` | `Hotel` |
| `amenity.bank`, `amenity.atm` | `Nearby Places` |
| `religion.*` | `Nearby Places` |
| `sport.*`, `leisure.fitness` | `Nearby Places` |
| everything else | `Others` |

This map lives inside `GeoapifyService::normaliseCategory()` and is configurable.

---

## 13. Potential Improvements (Post-MVP)

1. **Artisan command** `geoapify:sync-all` to bulk-sync all published properties with coordinates — useful for initial population.
2. **Admin bulk-sync** button on `properties/index.blade.php` for syncing multiple properties at once.
3. **Staleness indicator** on the admin list: show last-synced timestamp per property with a "stale" warning after configurable days.
4. **Geoapify category fetch per category group** in parallel using `Http::pool()` to reduce API round-trips.
5. **Backfill manual JSON**: optional one-time command to convert existing `nearby_places` items that have `lat`/`lng` into `property_places` rows with `source = 'manual'` for unified rendering.
