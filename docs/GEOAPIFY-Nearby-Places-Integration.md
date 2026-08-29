# Implementasi Nearby Places / "Di Sekitar Properti" — Lya Rooms

---

# ⚑ IMPLEMENTATION STATUS — IMPLEMENTED 2026-08-28

**This document is the original design spec. The pipeline it describes is now implemented.** Everything below this section is the historical spec and is NOT authoritative. Where the spec and the code differ, **the code wins** (per [`AGENTS.md`](../AGENTS.md) §23).

## Shipped files

**Migrations**
- [`database/migrations/2026_08_28_000001_create_places_table.php`](../database/migrations/2026_08_28_000001_create_places_table.php) — `places`: `geoapify_place_id` (nullable, unique), `name`, `category`, `lat`/`lng` decimal(10,7), `address`, `website`, `phone`, `raw_category`, `fetched_at`. Indexes on `category` and composite `(lat, lng)`.
- [`database/migrations/2026_08_28_000002_create_property_places_table.php`](../database/migrations/2026_08_28_000002_create_property_places_table.php) — `property_places`: `property_id` + `place_id` (FK cascade), `source` enum `manual|geoapify` default `geoapify`, `distance_m` unsigned nullable, `sort_order`. Unique `(property_id, place_id)`; indexes on `property_id` and `source`.

**Models**
- [`app/Models/Place.php`](../app/Models/Place.php) — casts `lat`/`lng` → float, `fetched_at` → datetime; `propertyPlaces()` hasMany.
- [`app/Models/PropertyPlace.php`](../app/Models/PropertyPlace.php) — `property()` / `place()` belongsTo; `getDistanceFormattedAttribute()` → `"850m"` / `"1.2km"` / `null`.
- [`app/Models/Property.php`](../app/Models/Property.php) — gained `propertyPlaces()` (hasMany) and `places()` (hasManyThrough). Its `nearby_places` JSON column, `NEARBY_CATEGORIES` constant, `$fillable`, and `$casts` are unchanged.

**Service**
- [`app/Services/GeoapifyService.php`](../app/Services/GeoapifyService.php) — `fetchNearbyPlaces(float $lat, float $lng, ?int $radiusMetres, ?int $limit): array` against `https://api.geoapify.com/v2/places`. Normalizes each feature to `geoapify_place_id`, `name`, `raw_category`, `category`, `lat`, `lng`, `address`, `website`, `phone`; skips unnamed features; returns `[]` when `features` is absent. Up to 2 retries on timeout/5xx, `Retry-After` handling on 429, no retry on other 4xx, `\RuntimeException` on 401/403, persistent failure, invalid JSON, or a blank API key. Private `mapCategory()` translates provider categories to `Property::NEARBY_CATEGORIES` labels.

**Job**
- [`app/Jobs/FetchNearbyPlacesJob.php`](../app/Jobs/FetchNearbyPlacesJob.php) — `ShouldQueue`, `$tries = 3`, `$backoff = [30, 120, 300]`, `$timeout = 60`. Early-returns with `Log::warning` when coordinates or the API key are missing. Cache key `geoapify_places_{$property->id}`, 24h TTL; cache hit skips the API call; failures are never cached. Inside `DB::transaction()`: `Place::updateOrCreate()` on `geoapify_place_id`, `PropertyPlace::updateOrCreate()` on `(property_id, place_id)` with `source='geoapify'` and a Haversine `distance_m`, then deletes stale `source='geoapify'` pivot rows. Catches `\RuntimeException` and returns — nothing escapes `handle()`. **This is the only caller of `GeoapifyService`.**

**Config**
- [`config/services.php`](../config/services.php) `geoapify` block: `key` (`GEOAPIFY_API_KEY`), `map_key` (`GEOAPIFY_MAP_KEY`, falls back to `key`), `radius` (default 2000), `max_results` (default 20). The same four keys were added to `.env.example` with blank values.

**Admin surface** — methods on the existing root-namespace [`app/Http/Controllers/PropertyController.php`](../app/Http/Controllers/PropertyController.php), not a new `Admin\` controller:
- `nearbyPlaces(Property $property)` → `GET admin/properties/{property}/nearby-places`, route name `admin.properties.nearby-places` — POI table ordered by `distance_m` ASC.
- `resyncNearbyPlaces(Property $property)` → `POST admin/properties/{property}/resync-nearby-places`, route name `admin.properties.resync-nearby-places` — validates coordinates + API key, `Cache::forget()`s the key, dispatches the job, redirects back with a flash message.
- Both inside the existing `['auth','verified','admin']` group under the `slug('admin_prefix','admin')` prefix in [`routes/web.php`](../routes/web.php). `edit()` eager-loads `propertyPlaces.place`.
- [`resources/views/admin/properties/_nearby.blade.php`](../resources/views/admin/properties/_nearby.blade.php) — resync button, coordinate/API-key warnings, POI table with a `manual`/`geoapify` source badge.

**Frontend**
- `PropertyController::publicShow()` passes `$persistentPlaces` (`source='geoapify'`, `with('place')`, ordered by `distance_m`) alongside the untouched `$nearbyPlacesWithDistance`.
- [`resources/views/properties/show.blade.php`](../resources/views/properties/show.blade.php) — renders persistent POIs grouped by category when present, else falls back to the manual `nearby_places` JSON grouping. Emits `<div id="property-map">` plus a `<script type="application/json" id="map-data">` payload (hardened `json_encode` flags) with `center`, `mapKey`, and `markers`.
- [`resources/views/layouts/frontend.blade.php`](../resources/views/layouts/frontend.blade.php) — Leaflet 1.9.4 CSS/JS from CDN with SRI hashes; no npm dependency added.
- [`resources/js/app.js`](../resources/js/app.js) — `initPropertyMap()` bound to `turbo:load` + `DOMContentLoaded`; reads `#map-data`, builds the Geoapify raster tile URL from the payload's `mapKey`, falls back to OSM tiles when blank, `scrollWheelZoom: false`, `data-map-init` Turbo guard. No API key is hardcoded in JS.
- New UI strings added to [`lang/en.json`](../lang/en.json) and [`lang/id.json`](../lang/id.json).

**Tests**
- [`tests/Feature/GeoapifyNearbyPlacesTest.php`](../tests/Feature/GeoapifyNearbyPlacesTest.php) — 30 tests / 109 assertions, green. Uses `Http::preventStrayRequests()` + `Http::fake()`, and pins that a public property-page render issues **zero** outbound HTTP requests.
- [`tests/Feature/PropertyNearbyPlacesTest.php`](../tests/Feature/PropertyNearbyPlacesTest.php) — manual-JSON coverage, still passing unchanged.

## Deliberate divergences from this spec

| This spec says | What shipped |
|---|---|
| `NearbyPlacesService` | The service is **`GeoapifyService`**. `NearbyPlacesService` does not exist. |
| `SyncNearbyPlaces` job | The job is **`FetchNearbyPlacesJob`**. |
| Central `config/nearby-places.php` with category slugs (`food`, `cafe`, …) + labels + icons | No such config file. Provider categories map directly to existing **`Property::NEARBY_CATEGORIES` display labels** (e.g. `'Restaurant/Food'`, `'Mall/Shopping'`, `'Hospital/Health'`) inside `GeoapifyService::mapCategory()`, reusing the labels the manual JSON path already used. Unmapped provider categories return `null` and those POIs are excluded. |
| `places.external_id`, `subcategory`, `street`, `postcode`, `city`, `district`, `country`, `distance`, `metadata` JSON | `places.geoapify_place_id` (dedupe key), a single flat `address`, `website`, `phone`, `raw_category`, `fetched_at`. No `metadata` JSON column; distance lives on the pivot as `distance_m`. |
| `property_places` unique on `property_id + place_id + category`, with a `category` column | Pivot has **no `category` column** (category lives on `places`); unique is `(property_id, place_id)`. Pivot instead carries a **`source` enum (`manual`\|`geoapify`)** the spec did not anticipate. |
| `Place::properties()` / `Property::nearbyPlaces()` `belongsToMany` with pivot fields | `Property::propertyPlaces()` (hasMany to the pivot model) + `Property::places()` (hasManyThrough). The pivot is a first-class `PropertyPlace` model. `Property::nearbyPlaces()` was **not** added — that name would collide conceptually with the `nearby_places` JSON column. |
| `GEOAPIFY_LIMIT_PER_CATEGORY=5` | **`GEOAPIFY_MAX_RESULTS`** (default 20) — a single overall result cap, not per category. |
| Cache key `property:{id}:nearby-places`, TTL 7 days, configurable via `NEARBY_PLACES_CACHE_TTL` | Cache key **`geoapify_places_{id}`**, TTL fixed at **24 hours**, not env-configurable. |
| Migrate away from / replace the existing nearby-places display | The manual **`properties.nearby_places` JSON path is retained as a fallback**. Persistent `source='geoapify'` rows take precedence on the property page; the manual JSON grouping renders when there are none. `source='manual'` pivot rows are never deleted by the job. |
| Sync on property create, and re-sync when coordinates change | **No automatic fetch on create or update.** The admin **Resync POI** action is the sole trigger. |
| "Jika map belum ada, jangan memaksakan implementasi map" / possible MapLibre | A map **was** shipped: **Leaflet 1.9.4 via CDN** (SRI-pinned) with Geoapify raster tiles, falling back to OSM tiles when `map_key` is blank. No npm map package, no MapLibre. |

## Not implemented from this spec

Do not assume any of these exist:

- **`config/nearby-places.php`** — no category config file; no per-category icon registry.
- **Artisan command** `php artisan nearby-places:sync [property?]` — there is no POI artisan command.
- **Scheduled refresh** — [`routes/console.php`](../routes/console.php) schedules only `currency:fetch`. POI data never refreshes on its own; it goes stale until an admin resyncs.
- **Sync status lifecycle** (`Queued` / `Syncing` / `Synced` / `Failed`) and a "Last synced" timestamp in the admin UI — the admin partial shows the POI table plus flash messages only. (`places.fetched_at` is persisted, but no admin "last synced" indicator is rendered.)
- **Automatic dispatch on property save / coordinate change** — see the divergence table.
- **Per-category result limits** and per-category API calls — one call per resync, one overall result cap.

Any other UI refinement described in the spec's §21–§27 (category navigation, "Lihat Semua" expansion, dedicated loading state) should be verified directly against [`resources/views/properties/show.blade.php`](../resources/views/properties/show.blade.php) before being assumed present.

## Operational prerequisites

1. **`GEOAPIFY_API_KEY` must be set in `.env`.** It is currently **blank**, so the job early-returns with a log warning and the map falls back to OSM tiles. Nothing will populate until a key is set.
2. **A real queue driver plus a worker are required for async execution.** `.env` sets `QUEUE_CONNECTION=sync` while [`config/queue.php`](../config/queue.php) defaults to `database`. Under `sync` the job runs **inline during the admin resync request** — slower request, and `$tries`/`$backoff` never apply. Set e.g. `QUEUE_CONNECTION=database` and run `php artisan queue:work` for real background execution with retries.

Operator instructions: [`docs/geoapify-setup.md`](geoapify-setup.md). Agent rules: [`.agents/skills/nearby-places/SKILL.md`](../.agents/skills/nearby-places/SKILL.md).

---

## Context

Saya sedang mengembangkan **Lya Rooms**, website booking akomodasi berbasis Laravel.

Saya ingin menambahkan fitur:

> **"Di Sekitar Properti"**

Fitur ini harus otomatis mendeteksi berbagai POI (Point of Interest) di sekitar property berdasarkan latitude dan longitude property.

Provider utama:

**Geoapify Places API**

Reference:
https://www.geoapify.com/places-api/

Tujuan akhirnya:

Property memiliki koordinat:

```text
latitude
longitude
```

Kemudian sistem secara otomatis mencari tempat di sekitar property dan menyimpannya ke database.

Kategori contoh:

* 🍽️ Kuliner
* ☕ Cafe
* 🛍️ Belanja
* 🎡 Hiburan
* 🚉 Transportasi
* 🏥 Kesehatan
* 🏦 Keuangan
* 🏫 Pendidikan
* 🕌 Tempat Ibadah
* 💪 Lifestyle
* 📍 Lainnya

---

# IMPORTANT

Sebelum coding:

1. Inspect repository.
2. Identifikasi versi Laravel.
3. Identifikasi model Property yang sudah ada.
4. Identifikasi migration properties.
5. Identifikasi struktur service layer.
6. Identifikasi apakah project sudah menggunakan Laravel Queue.
7. Identifikasi cache driver.
8. Identifikasi naming convention project.
9. Identifikasi struktur Blade/component frontend.
10. Jangan membuat ulang struktur yang sebenarnya sudah tersedia.

**Jangan mengubah business logic booking.**

Jangan mengubah:

* booking
* availability
* pricing
* reservation
* payment
* authentication
* authorization

Fitur ini harus menjadi modul terisolasi.

---

# ARCHITECTURE

Implementasikan:

```text
Property
   │
   │ latitude + longitude
   ▼
SyncNearbyPlaces Job
   │
   ▼
NearbyPlacesService
   │
   ▼
Geoapify Places API
   │
   ▼
Normalize API Response
   │
   ▼
places
   │
   ▼
property_places
   │
   ▼
Cache
   │
   ▼
Property Detail UI
```

Jangan melakukan API request Geoapify langsung dari Blade/browser.

---

# 1. Database: places

Buat migration:

```text
create_places_table
```

Minimal fields:

```text
id
external_id
name
category
subcategory nullable
latitude
longitude
address nullable
street nullable
postcode nullable
city nullable
district nullable
country nullable
distance nullable
metadata json nullable
created_at
updated_at
```

Namun sebelum menentukan schema final, inspect existing database conventions.

## external_id

`external_id` menyimpan identifier POI dari provider.

Buat unique index jika aman berdasarkan response provider.

Jika identifier provider tidak selalu tersedia, gunakan kombinasi identifier yang aman.

---

# 2. Database: property_places

Buat migration:

```text
create_property_places_table
```

Fields:

```text
id
property_id
place_id
category
distance_meters
sort_order nullable
created_at
updated_at
```

Foreign key:

```text
property_id → properties.id
place_id → places.id
```

Gunakan cascading delete jika konsisten dengan database conventions existing project.

Tambahkan unique constraint:

```text
property_id + place_id + category
```

agar tidak terjadi duplicate relationship.

Tambahkan indexes untuk query:

```text
property_id
category
distance_meters
```

---

# 3. Model Place

Buat:

```text
app/Models/Place.php
```

Relationship:

```php
public function properties()
{
    return $this->belongsToMany(Property::class, 'property_places')
        ->withPivot([
            'category',
            'distance_meters',
            'sort_order',
        ]);
}
```

Tambahkan casts:

```text
metadata => array
latitude => decimal
longitude => decimal
```

Sesuaikan dengan conventions project.

---

# 4. Property Relationship

Tambahkan ke existing Property model:

```php
public function nearbyPlaces()
{
    return $this->belongsToMany(Place::class, 'property_places')
        ->withPivot([
            'category',
            'distance_meters',
            'sort_order',
        ]);
}
```

Jangan merusak relationship Property yang sudah ada.

---

# 5. Category Configuration

Jangan hardcode kategori di banyak tempat.

Buat satu central configuration.

Contoh:

```text
config/nearby-places.php
```

Struktur:

```php
return [

    'radius_meters' => 2000,

    'limit_per_category' => 5,

    'categories' => [

        'food' => [
            'label' => 'Kuliner',
            'icon' => 'utensils',
            'geoapify' => [
                // provider categories
            ],
        ],

        'cafe' => [
            'label' => 'Cafe',
            'icon' => 'coffee',
            'geoapify' => [
                // provider categories
            ],
        ],

        'shopping' => [
            'label' => 'Belanja',
            'icon' => 'shopping-bag',
            'geoapify' => [
                // provider categories
            ],
        ],

        'entertainment' => [
            'label' => 'Hiburan',
            'icon' => 'ticket',
            'geoapify' => [
                // provider categories
            ],
        ],

        'transportation' => [
            'label' => 'Transportasi',
            'icon' => 'bus',
            'geoapify' => [
                // provider categories
            ],
        ],

        'health' => [
            'label' => 'Kesehatan',
            'icon' => 'heart-pulse',
            'geoapify' => [
                // provider categories
            ],
        ],

        'finance' => [
            'label' => 'Keuangan',
            'icon' => 'landmark',
            'geoapify' => [
                // provider categories
            ],
        ],

        'education' => [
            'label' => 'Pendidikan',
            'icon' => 'graduation-cap',
            'geoapify' => [
                // provider categories
            ],
        ],

        'worship' => [
            'label' => 'Tempat Ibadah',
            'icon' => 'building',
            'geoapify' => [
                // provider categories
            ],
        ],

        'lifestyle' => [
            'label' => 'Lifestyle',
            'icon' => 'dumbbell',
            'geoapify' => [
                // provider categories
            ],
        ],

    ],

];
```

Gunakan kategori Geoapify yang benar berdasarkan dokumentasi API.

**Jangan mengarang category identifier.**

Jika perlu, inspect dokumentasi provider terlebih dahulu.

---

# 6. Environment Variables

Tambahkan:

```env
GEOAPIFY_API_KEY=
GEOAPIFY_RADIUS=2000
GEOAPIFY_LIMIT_PER_CATEGORY=5
```

Jangan hardcode API key.

Config:

```php
'api_key' => env('GEOAPIFY_API_KEY'),

'radius_meters' => env(
    'GEOAPIFY_RADIUS',
    2000
),

'limit_per_category' => env(
    'GEOAPIFY_LIMIT_PER_CATEGORY',
    5
),
```

Jangan expose API key ke frontend JavaScript.

---

# 7. NearbyPlacesService

Buat service:

```text
app/Services/NearbyPlacesService.php
```

Responsibilities:

1. Request Geoapify.
2. Validate coordinates.
3. Build categories.
4. Request POI.
5. Normalize response.
6. Calculate distance.
7. Deduplicate places.
8. Upsert places.
9. Attach places ke property.
10. Return normalized result.

Service harus menggunakan Laravel HTTP Client:

```php
Http::timeout(...)
    ->retry(...)
```

Jangan menggunakan raw curl jika tidak diperlukan.

---

# 8. API Request

Gunakan endpoint Places API yang sesuai dengan dokumentasi Geoapify terbaru.

Request berdasarkan:

```text
latitude
longitude
radius
categories
limit
```

Gunakan:

```text
GEOAPIFY_API_KEY
```

Jangan expose key ke browser.

---

# 9. Error Handling

Service harus menangani:

* HTTP 4xx
* HTTP 5xx
* timeout
* invalid JSON
* empty response
* invalid coordinates
* missing API key
* rate limit

Jangan membuat property detail gagal render hanya karena Geoapify sedang error.

Nearby places adalah **non-critical feature**.

Jika API gagal:

```text
log warning/error
return empty collection
```

Property page tetap harus berhasil.

---

# 10. Distance Calculation

Jangan percaya hanya pada distance yang dikirim provider jika koordinat POI tersedia.

Buat helper/service:

```text
calculateDistanceInMeters()
```

Gunakan **Haversine formula**.

Input:

```text
property_lat
property_lng
place_lat
place_lng
```

Output:

```text
distance_meters
```

Simpan hasil dalam database sebagai integer/decimal yang sesuai.

Contoh:

```text
250
450
1200
2100
```

---

# 11. Human-readable Distance

Buat formatter:

```text
formatDistance()
```

Rules:

```text
< 1000m

850 m
```

Jika >= 1000:

```text
1.2 km
```

Jangan tampilkan terlalu banyak decimal.

Contoh:

```text
250 m
850 m
1.2 km
2.5 km
```

Gunakan locale Bahasa Indonesia jika existing project memiliki localization layer.

---

# 12. Deduplication

Satu POI mungkin muncul dalam beberapa kategori Geoapify.

Contoh:

```text
Restaurant
Food
Fast Food
```

Jangan menyimpan POI yang sama berkali-kali sebagai `places`.

Gunakan:

```text
external_id
```

sebagai identifier utama jika tersedia.

Relationship category dapat disimpan pada:

```text
property_places.category
```

---

# 13. Upsert Strategy

Gunakan database upsert / updateOrCreate sesuai kebutuhan.

Jangan:

```php
Place::create(...)
```

setiap sync.

Karena job dapat dijalankan berkali-kali.

Goal:

```text
sync
sync
sync
```

tetap menghasilkan:

```text
1 place
```

bukan:

```text
3 duplicate places
```

---

# 14. Queue Job

Buat:

```text
app/Jobs/SyncNearbyPlaces.php
```

Job menerima:

```php
Property $property
```

atau property ID jika lebih sesuai dengan queue serialization conventions project.

Implement:

```php
ShouldQueue
```

Job harus:

1. cek property masih ada
2. cek latitude/longitude
3. panggil NearbyPlacesService
4. log hasil
5. handle exception
6. retry secara aman

Tambahkan:

```text
tries
backoff
timeout
```

sesuai conventions project.

Jangan membuat job gagal permanen hanya karena API temporary timeout.

---

# 15. Dispatch Job

Nearby places harus di-sync ketika:

### Property dibuat

Dispatch:

```text
SyncNearbyPlaces
```

### Property coordinates berubah

Dispatch ulang.

### Manual admin sync

Sediakan kemampuan untuk menjalankan sync ulang.

Contoh:

```text
php artisan nearby-places:sync {property?}
```

Jika artisan command cocok dengan arsitektur project.

---

# 16. Jangan Sync Setiap Page View

Ini sangat penting.

JANGAN:

```text
GET /property/foo
    ↓
Geoapify API
```

Gunakan:

```text
Property saved
    ↓
Queue
    ↓
Geoapify
    ↓
Database
```

Property page hanya membaca database/cache.

---

# 17. Cache

Tambahkan cache untuk nearby places.

Cache key:

```text
property:{property_id}:nearby-places
```

Atau gunakan naming convention project jika sudah ada.

TTL:

```text
7 days
```

atau configurable:

```env
NEARBY_PLACES_CACHE_TTL=604800
```

Cache result yang sudah dikelompokkan berdasarkan kategori.

Contoh:

```php
[
    'food' => [...],
    'cafe' => [...],
    'shopping' => [...],
]
```

---

# 18. Cache Invalidation

Setelah successful sync:

```text
Cache::forget(...)
```

Kemudian cache akan dibuat ulang saat property detail dibuka.

Jika coordinates berubah:

```text
invalidate nearby places cache
```

Jika places disync manual:

```text
invalidate cache
```

---

# 19. Query Optimization

Jangan melakukan N+1 query.

Property detail:

```text
1 property query
1 nearby places query
```

bukan:

```text
1 property
+
10 category queries
+
50 place queries
```

Gunakan eager loading / optimized query.

---

# 20. Data Freshness

Nearby places tidak perlu real-time per visitor.

Default:

```text
sync every 7-30 days
```

Namun jika property coordinates berubah:

```text
sync immediately
```

Tambahkan field jika diperlukan:

```text
nearby_places_synced_at
```

ke properties atau gunakan metadata/status table sesuai arsitektur existing.

Jangan menambah field hanya jika sebenarnya tidak diperlukan.

---

# 21. Mobile UI

Tambahkan section:

# Di Sekitar Properti

Layout mobile-first.

Contoh:

```text
Di sekitar properti

🍽️ Kuliner

┌────────────────────────────┐
│ Restoran XYZ               │
│ ⭐ 4.5                     │
│ 250 m                      │
└────────────────────────────┘

┌────────────────────────────┐
│ Cafe ABC                   │
│ ⭐ 4.7                     │
│ 450 m                      │
└────────────────────────────┘

        Lihat semua
```

Kemudian:

```text
🛍️ Belanja

Mall XYZ
850 m

Supermarket ABC
1.1 km

Lihat semua
```

---

# 22. Category Navigation

Mobile boleh menggunakan horizontal scrolling **HANYA untuk category tabs**, bukan untuk informasi POI.

Contoh:

```text
[ Semua ] [ Kuliner ] [ Belanja ] [ Hiburan ] [ Transportasi ]
```

Jika menggunakan horizontal scroll:

```css
overflow-x-auto
```

hanya pada category selector.

POI cards sendiri harus tetap vertical/full-width.

Pastikan category navigation tidak menyebabkan seluruh page mengalami horizontal overflow.

---

# 23. POI Card

Buat reusable component:

```text
resources/views/components/nearby-place-card.blade.php
```

atau mengikuti component structure existing.

Isi:

```text
Name
Category
Distance
Rating jika tersedia
Address jika tersedia
```

Optional:

```text
Open/closed status
```

Jangan tampilkan data jika provider tidak memberikannya.

Jangan membuat fake rating.

---

# 24. "Lihat Semua"

Default mobile:

Tampilkan maksimal:

```text
3 POI / category
```

Kemudian:

```text
Lihat semua
```

Bisa menggunakan:

* modal
* drawer
* accordion

Pilih component existing yang paling sesuai.

Desktop boleh menampilkan lebih banyak.

---

# 25. Icons

Gunakan icon library yang sudah ada di project.

Jangan menambahkan icon package baru jika tidak diperlukan.

Category icon berasal dari central config.

Contoh:

```text
food → utensils
cafe → coffee
shopping → shopping-bag
transportation → bus
health → heart-pulse
```

---

# 26. Empty State

Jika category tidak memiliki POI:

Jangan tampilkan:

```text
Kuliner
No data
```

yang memenuhi layar.

Lebih baik:

* hide category kosong

atau jika seluruh POI kosong:

```text
Belum ada informasi tempat di sekitar properti.
```

Jangan membuat halaman property terlihat rusak.

---

# 27. Loading State

Karena data berasal dari database/cache:

Normal property detail seharusnya tidak memerlukan loading state.

Jika implementasi menggunakan async enhancement, gunakan skeleton.

Tetapi:

**Jangan request Geoapify dari browser.**

---

# 28. Map Integration

Jika existing Lya Rooms sudah memiliki map:

Jangan mengganti map provider sekarang.

Nearby Places merupakan layer data terpisah.

UI dapat memiliki:

```text
Di sekitar properti

[Map]

Kuliner
...

Belanja
...
```

Jika map belum ada, jangan memaksakan implementasi map sebagai bagian pekerjaan ini kecuali memang sudah ada requirement existing.

Fokus:

**Nearby POI + list UI.**

---

# 29. Security

API key:

```text
GEOAPIFY_API_KEY
```

harus hanya server-side.

Jangan:

```text
VITE_GEOAPIFY_API_KEY
NEXT_PUBLIC_GEOAPIFY_API_KEY
```

Jangan mengirim API key ke frontend.

Validate property coordinates.

Jangan menerima arbitrary coordinates dari public request lalu meneruskannya langsung ke provider tanpa authorization/rate limiting.

---

# 30. Admin UX

Jika project memiliki admin property editor, tambahkan:

```text
Nearby Places

Last synced:
27 Aug 2026 10:30

[ Sync Nearby Places ]
```

Button melakukan dispatch queue job.

Tambahkan status:

```text
Queued
Syncing
Synced
Failed
```

hanya jika architecture existing mendukungnya.

Jangan membuat sistem admin yang kompleks hanya untuk fitur ini.

---

# 31. Artisan Command

Jika cocok dengan project:

```bash
php artisan nearby-places:sync
```

Behavior:

```text
php artisan nearby-places:sync
```

sync semua property yang memiliki valid coordinates.

Optional:

```bash
php artisan nearby-places:sync 123
```

sync property tertentu.

Gunakan queue jika jumlah property banyak.

Jangan menjalankan semua HTTP request secara synchronous jika jumlah property besar.

---

# 32. Scheduled Refresh

Jika scheduler project sudah digunakan, tambahkan scheduled refresh.

Misalnya:

```text
weekly
```

Hanya sync property yang:

* memiliki coordinates
* data nearby places sudah stale

Jangan sync semua property setiap hari tanpa alasan.

---

# 33. Tests

Tambahkan tests untuk:

### Distance

Test Haversine:

```text
known coordinate A
known coordinate B
expected approximate distance
```

### Service

Mock Geoapify response.

Test:

* successful response
* empty response
* invalid response
* timeout
* HTTP 500
* rate limit

### Database

Test:

```text
property
→ nearby places
```

relationship tersimpan dengan benar.

### Deduplication

Pastikan satu external_id tidak membuat duplicate place.

### Job

Pastikan job memanggil service.

### Cache

Pastikan cache dibuat setelah query.

### Property page

Pastikan nearby places dapat ditampilkan tanpa membuat error jika data kosong.

Jangan menggunakan real Geoapify API dalam automated tests.

---

# 34. Performance Acceptance Criteria

Target:

Property detail page tidak melakukan HTTP request eksternal ke Geoapify.

Database query nearby places harus teroptimasi.

Cache harus digunakan.

Tidak ada N+1.

Tidak ada API key di frontend.

Tidak ada horizontal overflow pada mobile.

---

# 35. UX Acceptance Criteria

Pada mobile:

```text
Property
 ↓
Gallery
 ↓
Property information
 ↓
Amenities
 ↓
About
 ↓
Tipe Kamar & Harga
 ↓
Di Sekitar Properti
 ↓
Reviews
 ↓
Policies
```

atau gunakan urutan existing page jika UX audit menunjukkan urutan lain lebih baik.

Nearby Places harus:

* mudah discan
* tidak terlalu padat
* tidak terlalu panjang
* kategori jelas
* jarak mudah dibaca
* mobile-friendly

---

# 36. Important UI Principle

Jangan membuat:

```text
Restaurant | Cafe | Mall | Transport | ...
```

sebagai tabel horizontal.

Jangan membuat:

```text
Place name | Address | Distance | Rating | Category
```

sebagai desktop table yang dipaksa ke mobile.

Mobile harus:

```text
Category
   ↓
Place Card
   ↓
Place Card
   ↓
Place Card
```

---

# 37. Existing Property Detail Integration

Cari file property detail existing.

Integrasikan:

```text
NearbyPlaces section
```

tanpa mengubah struktur keseluruhan halaman secara agresif.

Gunakan component reusable.

Contoh:

```blade
<x-nearby-places
    :property="$property"
    :places="$nearbyPlaces"
/>
```

Tetapi gunakan syntax yang sesuai dengan existing component architecture.

---

# 38. Controller / ViewModel

Jangan melakukan business logic berat di Blade.

Controller/service/ViewModel harus menyediakan:

```text
nearbyPlaces
nearbyPlacesByCategory
```

Blade hanya render.

Jika property detail menggunakan dedicated service/ViewModel, integrasikan di sana.

---

# 39. Recommended Data Flow

Expected final implementation:

```text
Admin creates Property
        │
        ▼
Property saved
        │
        ▼
SyncNearbyPlaces dispatched
        │
        ▼
Queue Worker
        │
        ▼
NearbyPlacesService
        │
        ▼
Geoapify
        │
        ▼
Normalize
        │
        ▼
Calculate Haversine distance
        │
        ▼
Upsert places
        │
        ▼
Sync property_places
        │
        ▼
Invalidate cache
        │
        ▼
Property Detail
        │
        ▼
Cache
        │
        ▼
"Di Sekitar Properti"
```

---

# 40. Deliverables

Implement:

```text
database/migrations/
    xxxx_create_places_table.php
    xxxx_create_property_places_table.php

app/Models/
    Place.php

app/Services/
    NearbyPlacesService.php

app/Jobs/
    SyncNearbyPlaces.php

config/
    nearby-places.php

resources/views/components/
    nearby-places.blade.php
    nearby-place-card.blade.php
```

Plus files tambahan jika memang diperlukan oleh existing architecture.

---

# 41. Final Audit

Setelah implementasi selesai:

Run:

```bash
php artisan migrate
php artisan test
php artisan config:clear
php artisan cache:clear
```

dan command terkait jika diperlukan.

Jika environment tidak memungkinkan menjalankan command tertentu, jangan mengarang hasilnya.

Lakukan code review terhadap:

* SQL indexes
* foreign keys
* queue serialization
* HTTP retry
* API error handling
* caching
* duplicate prevention
* distance calculation
* responsive UI
* accessibility
* security
* performance

---

# 42. Final Response

Setelah selesai, berikan summary:

### Architecture

Apa saja component yang dibuat.

### Database

Migration dan indexes.

### Geoapify

Endpoint dan category mapping yang digunakan.

### Queue

Bagaimana sync dijalankan.

### Cache

Cache key + TTL + invalidation.

### Distance

Metode perhitungan jarak.

### UI

Bagaimana section "Di Sekitar Properti" bekerja di mobile/desktop.

### Tests

Test yang berhasil dijalankan.

### Commands

Command migration/test/sync yang perlu digunakan.

### Environment

Environment variable yang harus ditambahkan.

### Potential Improvements

Berikan maksimal 5 improvement lanjutan.

---

## IMPORTANT FINAL RULE

**Jangan melakukan shortcut hanya supaya fitur cepat selesai.**

Jangan:

* API call dari Blade
* API key di frontend
* fake POI data
* fake rating
* duplicate places
* hardcoded coordinates
* horizontal table di mobile
* `overflow-x-hidden` sebagai solusi root cause
* N+1 query
* synchronous API calls pada setiap page request

Implementasikan sebagai fitur production-ready yang bisa menangani pertumbuhan jumlah property Lya Rooms.
