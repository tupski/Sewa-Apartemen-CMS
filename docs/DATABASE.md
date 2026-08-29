# Model Database & Domain

_Database & Domain Model_

Deskripsi **model, relasi, kolom JSON, dan tabel pivot** yang benar-benar ada di **Artivo CMS**. Semua migrasi ada di [`database/migrations/`](../database/migrations) — perubahan skema **wajib lewat migrasi**, jangan edit skema langsung.

_Description of the **models, relationships, JSON columns, and pivot tables** that actually exist in **Artivo CMS**. All migrations live in [`database/migrations/`](../database/migrations) — schema changes **must go through migrations**, never edit the schema directly._

---

## 23 Model Terkonfirmasi / _23 Confirmed Models_

Semua model ada di [`app/Models/`](../app/Models):

| Model | Deskripsi singkat |
|-------|-------------------|
| [`Property`](../app/Models/Property.php) | Properti/apartemen (listing) |
| [`PropertyPhoto`](../app/Models/PropertyPhoto.php) | Foto properti (FK `property_id`) |
| [`Amenity`](../app/Models/Amenity.php) | Fasilitas (WiFi, AC, Parkir, dll.) |
| [`Booking`](../app/Models/Booking.php) | Pemesanan |
| [`Voucher`](../app/Models/Voucher.php) | Kupon diskon |
| [`PromoRate`](../app/Models/PromoRate.php) | Harga promo |
| [`Media`](../app/Models/Media.php) | Pustaka media (disk `public`) |
| [`Page`](../app/Models/Page.php) | Halaman CMS |
| [`Block`](../app/Models/Block.php) | Blok konten |
| [`Navigation`](../app/Models/Navigation.php) | Menu navigasi |
| [`Post`](../app/Models/Post.php) | Artikel blog |
| [`Category`](../app/Models/Category.php) | Kategori blog |
| [`Tag`](../app/Models/Tag.php) | Tag blog |
| [`User`](../app/Models/User.php) | Pengguna |
| [`Role`](../app/Models/Role.php) | Role (pivot `model_has_roles`) |
| [`SeoMetadata`](../app/Models/SeoMetadata.php) | Metadata SEO (morph `seoable`) |
| [`Redirect`](../app/Models/Redirect.php) | Aturan redirect |
| [`Setting`](../app/Models/Setting.php) | Pengaturan situs |
| [`Language`](../app/Models/Language.php) | Bahasa |
| [`CurrencyRate`](../app/Models/CurrencyRate.php) | Kurs mata uang |
| [`ActivityLog`](../app/Models/ActivityLog.php) | Log aktivitas pengguna |
| [`Place`](../app/Models/Place.php) | POI Geoapify yang dipersistenkan |
| [`PropertyPlace`](../app/Models/PropertyPlace.php) | Pivot properti ↔ place |

## Kolom JSON Kunci / _Key JSON Columns_

### `Property->prices` — kolom JSON

Harga properti disimpan sebagai JSON di kolom `prices` pada tabel `properties`, di-cast `'array'` ([`app/Models/Property.php`](../app/Models/Property.php:63)). Struktur per tipe unit:

```json
{
  "1br": {
    "night_wd": 500000,
    "night_we": 600000,
    "weekly": 3000000,
    "monthly": 10000000
  },
  "2br": {
    "t3_wd": 150000, "t3_we": 175000,
    "t6_wd": 250000, "t6_we": 290000,
    "t9_wd": 350000, "t9_we": 400000,
    "t12_wd": 450000, "t12_we": 500000,
    "t24_wd": 600000, "t24_we": 700000
  }
}
```

Kunci harga per tipe booking (lihat [`docs/BOOKING.md`](BOOKING.md)):

| Booking type | Kunci harga |
|--------------|-------------|
| `daily` | `night_wd`, `night_we` |
| `transit` | `t3_wd`, `t3_we`, `t6_wd`, `t6_we`, `t9_wd`, `t9_we`, `t12_wd`, `t12_we`, `t24_wd`, `t24_we` |
| `weekly` | `weekly` |
| `monthly` | `monthly` |

### `Property->nearby_places` — kolom JSON (jalur manual)

Kolom JSON `nearby_places` menyimpan tempat sekitar yang dimasukkan **manual**. Ini tetap menjadi fallback di halaman properti. Untuk pipeline Geoapify yang persisten, lihat [`docs/NEARBY-PLACES.md`](NEARBY-PLACES.md).

Kolom JSON lain di `Property`: `unit_types`, `weekend_days`, `photo_categories`, `required_documents` (semuanya di-cast `array`).

## Relasi Kunci / _Key Relationships_

- `PropertyPhoto` → `property_id` FK ke `Property` (cascade).
- `Booking` → `property_id` FK; `voucher_id` FK (ditambahkan belakangan); `status`; `access_token` (lookup tamu); `notes`.
- `SeoMetadata` → **polimorfik** via relasi `seoable()` (`morphTo`) — [`app/Models/SeoMetadata.php`](../app/Models/SeoMetadata.php:40). Pages/posts/properties melampirkan metadata SEO melaluinya. **Jangan** ratakan atau rusak morph ini.
- `User` → `roles()` `belongsToMany` via pivot `model_has_roles` (gaya Spatie, `model_type` + `model_id` + `role_id`).
- `Property` → `propertyPlaces()` (hasMany) dan `places()` (hasManyThrough) — lihat [`docs/NEARBY-PLACES.md`](NEARBY-PLACES.md).
- `ActivityLog` → `user_activity_logs` tabel, `User` punya `activityLogs()` hasMany.

## Tabel POI / _POI Tables_

- **`places`** ([`2026_08_28_000001_create_places_table.php`](../database/migrations/2026_08_28_000001_create_places_table.php)): POI Geoapify yang dipersistenkan. Dedupe pada `geoapify_place_id` (nullable-unique). Field: `name`, `category` (label `Property::NEARBY_CATEGORIES`), `lat`/`lng` (cast float), `address`, `website`, `phone`, `raw_category`, `fetched_at`.
- **`property_places`** ([`2026_08_28_000002_create_property_places_table.php`](../database/migrations/2026_08_28_000002_create_property_places_table.php)): pivot `property_id` + `place_id` (FK cascade, unique composite), `source` enum `manual|geoapify` (default `geoapify`), `distance_m`, `sort_order`. `getDistanceFormattedAttribute()` merender `"850m"` / `"1.2km"` / `null`.

## Absensi Penting — JANGAN Asumsikan Ada / _Notable Absences — Do NOT Assume These Exist_

Ini **tidak ada** di kode. Jangan mendokumentasikan atau membangun di atasnya:

| Hal | Status |
|-----|--------|
| **Model `Room`/`Unit`** | ❌ Tidak ada. Unit direfaktor menjadi `unit_types` di [`2026_08_12_000000_refactor_units_to_property_types.php`](../database/migrations/2026_08_12_000000_refactor_units_to_property_types.php). Referensi mati `Unit`/`UnitFactory` boleh diabaikan. |
| **Tabel `Availability`** | ❌ Tidak ada tabel khusus. Namun **pengecekan konflik booking per tipe unit ADA** di [`BookingService::validateAvailability()`](../app/Services/BookingService.php:204) — lihat catatan di bawah. |
| **Model `Payment`** | ❌ Tidak ada sistem pembayaran. |
| **Direktori `Policies/`** | ❌ Tidak ada. Otorisasi via `authorize()` di controller + middleware `admin`. |
| **`NearbyPlace` model** | ❌ Tidak ada. Ada `Place` + `PropertyPlace` (pipeline Geoapify) dan JSON `nearby_places` (manual). |
| **CI/CD pipeline** | ❌ Tidak ada `.github/`. Deployment manual. |

### ⚠️ Catatan penting tentang `Availability` / _Important note about `Availability`_

`AGENTS.md` dan dokumen lama menyatakan "tidak ada server-side availability/conflict checking". **Itu sudah usang.** Kode saat ini memiliki:

- [`BookingService::validateAvailability()`](../app/Services/BookingService.php:204) — memblokir booking yang tumpang-tindih untuk properti + tipe unit yang sama (`status != 'cancelled'`), menggunakan `lockForUpdate()` (anti TOCTOU), dipanggil di dalam transaksi `BookingService::create()`.
- Error yang dilempar: `'Tipe kamar ini sudah dibooking pada tanggal tersebut.'`
- Dikunci oleh test `test_overlapping_booking_is_rejected` di [`tests/Feature/SecurityTest.php`](../tests/Feature/SecurityTest.php:177).

Jadi: **pengecekan konflik booking ada**, meskipun tidak ada tabel `Availability` terpisah. Ini divergensi dari `AGENTS.md` yang harus dilaporkan.

## Konvensi Migrasi / _Migration Conventions_

- Semua perubahan skema lewat **migrasi** di [`database/migrations/`](../database/migrations).
- **Foreign key eksplisit** dengan `->constrained()` / `->foreign()`.
- **Index disengaja** — tambahkan untuk kolom yang dipakai di `WHERE`/`JOIN`/`ORDER BY` (mis. `properties.slug`, `posts.slug`, `pages.slug`, lookup booking).
- **Hindari migrasi destruktif** — lebih suka perubahan aditif.
- Jangan pernah `migrate:fresh` / `db:wipe` pada data produksi tanpa persetujuan eksplisit.

## Lihat Juga / _See Also_

- [`docs/architecture/database.md`](architecture/database.md) — ERD & detail tabel (dokumen lama, verifikasi dengan kode)
- [`docs/domain/property.md`](domain/property.md), [`docs/domain/pricing.md`](domain/pricing.md), [`docs/domain/booking.md`](domain/booking.md) — dokumen domain
- [`docs/decisions/ADR-003-units-refactored-to-property-types.md`](decisions/ADR-003-units-refactored-to-property-types.md) — keputusan refactor unit
