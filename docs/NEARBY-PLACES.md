# Tempat Sekitar (Nearby Places)

_Nearby Places_

Dua jalur tempat sekitar di properti: **JSON manual** dan **pipeline Geoapify persisten**. Keduanya hidup berdampingan.

_Two coexisting paths for nearby places on properties: **manual JSON** and the **persistent Geoapify pipeline**. Both coexist._

---

## 1. Jalur Manual / _Manual Path_

- Kolom JSON `properties.nearby_places` — di-cast `array` di [`Property`](../app/Models/Property.php:66).
- Dimasukkan manual oleh admin di form properti.
- Tetap menjadi **fallback** di halaman properti publik.
- Test: [`tests/Feature/PropertyNearbyPlacesTest.php`](../tests/Feature/PropertyNearbyPlacesTest.php).

## 2. Jalur Geoapify Persisten / _Persistent Geoapify Path_

Pipeline end-to-end:

1. Admin menekan tombol **Resync POI** di form properti → rute `admin.properties.resync-nearby-places`.
2. [`FetchNearbyPlacesJob`](../app/Jobs/FetchNearbyPlacesJob.php) di-dispatch (satu-satunya job custom; di `.env` queue `sync`, jadi berjalan **inline** saat request admin).
3. Job memanggil [`GeoapifyService`](../app/Services/GeoapifyService.php) → mengambil POI dari Geoapify Places API → menormalkan kategori (`Property::NEARBY_CATEGORIES`) → menyimpan ke tabel `places` (dedupe via `geoapify_place_id` nullable-unique) → membuat pivot `property_places` (unique composite `property_id` + `place_id`).
4. Hasil dish ke cache selama 24 jam (`geoapify_places_{id}`) sehingga request berikutnya tidak memanggil Geoapify lagi.
5. **Geoapify TIDAK PERNAH dipanggil saat render halaman.** Public property page membaca dari `places`/`property_places` yang sudah dipersistenkan. Test mengkonfirmasi: [`GeoapifyNearbyPlacesTest`](../tests/Feature/GeoapifyNearbyPlacesTest.php) mem-pin bahwa halaman properti publik mengeluarkan **0 permintaan HTTP keluar**.

### Model

- [`Place`](../app/Models/Place.php) — tabel `places`: `name`, `category` (label display dari `Property::NEARBY_CATEGORIES`), `lat`/`lng` (float), `address`, `website`, `phone`, `raw_category`, `fetched_at`. Dedupe pada `geoapify_place_id` (nullable-unique).
- [`PropertyPlace`](../app/Models/PropertyPlace.php) — pivot `property_places` dengan `source` enum `manual|geoapify` (default `geoapify`), `distance_m`, `sort_order`. `getDistanceFormattedAttribute()` merender `"850m"` / `"1.2km"` / `null`.

### Relasi di Property

- `$property->propertyPlaces()` — hasMany
- `$property->places()` — hasManyThrough

Karena `Property` menggunakan `SoftDeletes`, cascade FK di `property_places` hanya aktif pada `forceDelete()`.

### Admin UI

- Partial [`_nearby.blade.php`](../resources/views/admin/properties/_nearby.blade.php) — tabel POI, tombol Resync, peringatan jika koordinat/kunci tidak ada.
- Peringatan jika `GEOAPIFY_MAP_KEY` sama dengan `GEOAPIFY_API_KEY` (kunci Places terkirim ke browser — SEC-003).

## Konfigurasi / _Configuration_

```env
GEOAPIFY_API_KEY=your-server-key-here
GEOAPIFY_MAP_KEY=your-browser-key-here    # Optional, fallback ke GEOAPIFY_API_KEY
GEOAPIFY_RADIUS=2000                       # Meter, default 2000
GEOAPIFY_MAX_RESULTS=20                    # Default 20
```

## Catatan Penting / _Important Notes_

- **Dokumen desain asli** ada di [`docs/GEOAPIFY-Nearby-Places-Integration.md`](GEOAPIFY-Nearby-Places-Integration.md) — **sudah diimplementasi**. Lihat bagian Implementation Status di dokumen tersebut untuk daftar file yang dikirim dan divergensi yang disengaja. Di mana spesifikasi dan kode berbeda, **kode adalah otoritatif**.
- **Setup API key**: [`docs/geoapify-setup.md`](geoapify-setup.md).
- **Jangan** panggil `GeoapifyService` dari controller, view, atau jalur render lainnya — hanya dari `FetchNearbyPlacesJob`.
- Test: [`tests/Feature/GeoapifyNearbyPlacesTest.php`](../tests/Feature/GeoapifyNearbyPlacesTest.php), [`tests/Feature/PropertyNearbyPlacesTest.php`](../tests/Feature/PropertyNearbyPlacesTest.php).

## Lihat Juga / _See Also_

- [`docs/geoapify-setup.md`](geoapify-setup.md) — setup API key
- [`docs/GEOAPIFY-Nearby-Places-Integration.md`](GEOAPIFY-Nearby-Places-Integration.md) — spesifikasi desain (implementasi selesai, bedakan dengan kode)
- [`GEOAPIFY Integration Security Audit Report.md`](../GEOAPIFY%20Integration%20Security%20Audit%20Report.md) (root) — laporan audit keamanan Geoapify
