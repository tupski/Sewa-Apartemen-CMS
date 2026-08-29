# Booking & Harga

_Booking & Pricing_

Alur booking, status, layanan canonical, kunci harga JSON, promo & voucher — **sesuai kode**. Ini adalah area bisnis-kritis; semua perhitungan harga **harus** lewat layanan canonical.

_Booking flow, statuses, canonical services, JSON price keys, promos & vouchers — **as implemented**. This is a business-critical area; all pricing calculations **must** go through the canonical services._

---

## Alur Booking / _Booking Flow_

```
Halaman properti (publik)
  → Form booking (resources/views/properties/_booking-form.blade.php)
  → POST /bookings (BookingController::store, throttle:10,1)
  → BookingService::create()  [transaksional + canonical]
  → Halaman sukses /status/{token} (bookings/success.blade.php, bookings/status.blade.php)
```

Rute terkait ([`routes/web.php`](../routes/web.php:78)):

| Route | Middleware | Fungsi |
|-------|-----------|--------|
| `POST /{slug_booking}` | `throttle:10,1` | Buat booking (`bookings.store`) |
| `GET /{slug_booking_success}/{token}/success` | — | Halaman sukses |
| `GET /{slug_booking_status}/{token}` | `throttle:30,1` | Status booking tamu |
| `POST /{slug_booking_status}/validate-voucher` | `throttle:20,1` | Validasi kupon di form |

> Semua slug dapat diubah via **Settings → Slug & Path**. Halaman booking publik dikunci oleh **`access_token` acak** (bukan id numerik/kode sekuensial) — lihat komentar FIND-001 di [`routes/web.php`](../routes/web.php:79).

## Status Booking / _Booking Statuses_

Enum `bookings.status` dari [`2026_08_11_162521_create_bookings_table.php`](../database/migrations/2026_08_11_162521_create_bookings_table.php):

| Status | Default | Arti |
|--------|---------|------|
| `pending` | ✅ | Menunggu konfirmasi |
| `confirmed` | — | Dikonfirmasi |
| `cancelled` | — | Dibatalkan |
| `completed` | — | Selesai (masa tinggal berakhir) |

Alur transisi: `pending → confirmed → completed`, atau `pending → cancelled`. Transisi dilakukan oleh:

- [`BookingService::confirm()`](../app/Services/BookingService.php:237)
- [`BookingService::cancel()`](../app/Services/BookingService.php:248)
- [`BookingService::complete()`](../app/Services/BookingService.php:259)

Setiap transisi mengirim notifikasi via [`BookingNotificationService`](../app/Services/BookingNotificationService.php).

**Lookup tamu**: halaman status publik diakses lewat `bookings.access_token` (random, unguessable). Tambahan migrasi: [`2026_08_24_000000_add_access_token_to_bookings_table.php`](../database/migrations/2026_08_24_000000_add_access_token_to_bookings_table.php).

## Layanan Canonical / _Canonical Services_

> Aturan emas: **JANGAN duplikasi** logika pricing/booking/voucher di controller, view, atau JS. Tampilan harga di klien hanyalah preview; server adalah sumber kebenaran.

### 1. [`BookingPricingService::calculate()`](../app/Services/BookingPricingService.php:30)

Satu-satunya kalkulator harga. Parameter: `Property`, `unitType`, `bookingType`, `checkIn`, `checkOut`, `hours`, `promoRateId`, `voucherId`.

- **Transit**: harga per jam, ditentukan hari check-in (`t{3|6|9|12|24}_{wd|we}`).
- **Daily**: `night_wd`/`night_we` — keputusan weekend **per malam**.
- **Weekly / Monthly**: flat (`weekly`, `monthly`).
- Weekend ditentukan oleh `weekend_days` properti (override) atau konfigurasi global Settings → Pricing.
- Dapat menerapkan **PromoRate** dan/atau **Voucher**.
- Return: `total`, `nights`, `hours`, `is_weekend`, `days`, `rate`, `promo`, `voucher`, `discount`.

### 2. [`BookingService::create()`](../app/Services/BookingService.php:51)

Satu-satunya pembuat booking. **Transaksional** (`DB::transaction`).

- Kode booking unik `BK-YYYYMMDD-XXXX` di-generate di dalam transaksi dengan `lockForUpdate()` (anti duplikat saat konkurensi) — [`generateCode()`](../app/Services/BookingService.php:22).
- **Voucher hanya diterima via kode** (`voucher_code`), bukan `voucher_id` numerik (FIND-003).
- `used_count` voucher di-increment **hanya setelah** baris booking tersimpan (gagal di atas → rollback, voucher tetap bisa dipakai).
- Notifikasi keluar dikirim **setelah commit** via `DB::afterCommit` — tidak pernah mem-rollback booking.

### 3. [`Voucher::calculateDiscount()`](../app/Models/Voucher.php:77)

Satu-satunya kalkulator diskon kupon:

- `percent`: `round(total × discount_value/100)`, di-cap ke `max_discount_amount`.
- `fixed`: `discount_value`.
- Tidak pernah melebihi total booking.

## Pengecekan Konflik / _Conflict Checking_

> ⚠️ **PENTING**: `AGENTS.md` versi lama menyatakan "tidak ada server-side availability check". **Itu salah/kedaluwarsa.**

Kode saat ini **memiliki** pengecekan konflik booking:

- [`BookingService::validateAvailability()`](../app/Services/BookingService.php:204) dipanggil di dalam `create()`, memblokir booking yang **tumpang-tindih** untuk properti + tipe unit yang sama dengan status selain `cancelled`.
- Menggunakan `lockForUpdate()` (mencegah TOCTOU double-booking).
- Error: `'Tipe kamar ini sudah dibooking pada tanggal tersebut.'`
- Dikunci test: [`SecurityTest::test_overlapping_booking_is_rejected`](../tests/Feature/SecurityTest.php:177).

Tidak ada **tabel `Availability`** terpisah — pengecekan berbasis kueri overlap langsung di tabel `bookings`.

## Kunci Harga JSON / _JSON Price Keys_

Disimpan di `properties.prices` (JSON). Per tipe unit:

| Booking type | Kunci |
|--------------|-------|
| `daily` | `night_wd`, `night_we` |
| `transit` | `t3_wd`, `t3_we`, `t6_wd`, `t6_we`, `t9_wd`, `t9_we`, `t12_wd`, `t12_we`, `t24_wd`, `t24_we` |
| `weekly` | `weekly` |
| `monthly` | `monthly` |

Bucket transit: `[3, 6, 9, 12, 24]` jam — [`BookingPricingService::TRANSIT_BUCKETS`](../app/Services/BookingPricingService.php:13). Bulanan = 30 malam, mingguan = 7 malam.

Harga yang dikosongkan **tidak** muncul sebagai opsi di frontend. "Harga mulai dari" (card/detail) menggunakan `lowestPriceToday()` ([`app/Models/Property.php`](../app/Models/Property.php:236)) yang beralih ke tarif weekend pada hari weekend.

## Promo Rate / _Promo Rates_

- Model: [`PromoRate`](../app/Models/PromoRate.php)
- Diterapkan di `BookingPricingService::calculate()` untuk tipe transit (dan lainnya) — promo di-cap ke `property_id` spesifik dan harus `is_active`.
- Admin: **Menu → Booking → Promo Rates**.

## Voucher / _Vouchers_

- Model: [`Voucher`](../app/Models/Voucher.php) — kode selalu UPPERCASE (`setCodeAttribute`).
- Validitas: `is_active`, rentang `valid_from`/`valid_until`, dan `usage_limit` vs `used_count` — [`isValid()`](../app/Models/Voucher.php:50).
- Diterapkan hanya di dalam `BookingService::create()` via kode.
- Admin: **Menu → Booking → Vouchers**.

## Data Booking Tersimpan / _Stored Booking Data_

Model [`Booking`](../app/Models/Booking.php) menyimpan: `code`, `access_token`, `status`, `total_price`, `deposit_amount`, `price_breakdown` (JSON), `voucher_id`, `voucher_discount`, `metadata` (JSON: `unit_type`, `nights`, `hours`, `check_in_time`, dst.), `whatsapp_status`/`whatsapp_sent_at`, `notes`.

## Lihat Juga / _See Also_

- [`docs/DATABASE.md`](DATABASE.md) — kolom JSON `prices`, relasi
- [`docs/domain/pricing.md`](domain/pricing.md), [`docs/domain/booking.md`](domain/booking.md) — dokumen domain lama
- [`docs/decisions/ADR-001-canonical-pricing-and-booking-services.md`](decisions/ADR-001-canonical-pricing-and-booking-services.md)
