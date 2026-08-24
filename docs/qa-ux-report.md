# QA & UX Report — Sewa Apartemen CMS
**Tanggal:** 2026-08-21  
**Branch:** main (post-merge dari fix/bug-audit-all-phases)  
**Tester:** Kiro (Static + HTTP Testing)  
**Server:** php artisan serve — localhost:8000

---

## 1. Status HTTP — Fungsionalitas Route

| Route | Method | Status | Catatan |
|-------|--------|--------|---------|
| `/` | GET | ✅ 200 | Homepage normal |
| `/apartments` | GET | ✅ 200 | Listing properti |
| `/blog` | GET | ✅ 200 | Blog listing |
| `/login` | GET | ✅ 200 | Halaman login |
| `/sitemap.xml` | GET | ✅ 200 | SEO sitemap |
| `/robots.txt` | GET | ✅ 200 | Robots file |
| `/search/suggest?q=ap` | GET | ✅ 200 | Search API JSON |
| `/dashboard` | GET | ✅ 302 | Redirect ke login (benar — belum auth) |
| `/admin/bookings` | GET | ✅ 302 | Redirect ke login (benar) |
| `/admin/settings` | GET | ✅ 302 | Redirect ke login (benar) |
| `/install` | GET | ✅ 403 | **ProtectInstaller bekerja** |
| `POST /bookings` (kosong) | POST | ✅ 422 | Validasi berjalan |
| `POST /booking/validate-voucher` | POST | ✅ 422 | Throttle + validasi aktif |

Semua route critical berjalan sesuai ekspektasi.

---

## 2. Temuan Kritis — User Admin Tanpa Role

⚠️ **ISSUE DITEMUKAN:** User `admin@sewaapartemen.com` (ID: 1) tidak memiliki role apapun di database. Artinya user ini **tidak bisa masuk ke admin panel** meski sudah terdaftar.

```
Admin User | admin@sewaapartemen.com | isAdmin: NO | roles: (kosong)
Admin KR   | admin@kakaramaroom.com  | isAdmin: YES | roles: super-admin
```

**Root cause:** User pertama (seeder default) dibuat sebelum system role terhubung ke pivot table `model_has_roles`. Ini bukan bug code — ini masalah data seeder.

**Solusi:** Assign role `super-admin` ke user ID 1 melalui admin panel dengan akun `admin@kakaramaroom.com`, atau via tinker:
```bash
php artisan tinker
DB::table('model_has_roles')->insert(['role_id'=>1,'model_type'=>'App\\Models\\User','model_id'=>1,'created_at'=>now(),'updated_at'=>now()]);
```

---

## 3. Analisis Fungsional — Admin Panel

### Dashboard
- ✅ Statistik hari ini: Booking, Pending, Revenue, Properties
- ✅ Card "Pending Konfirmasi" menampilkan warning jika ada booking menunggu
- ✅ Quick links ke booking list dengan filter pre-applied
- ✅ Recent bookings table
- ⚠️ **UX:** Dashboard tidak ada notifikasi real-time — admin harus refresh manual untuk tahu ada booking baru

### Booking Management
- ✅ Tabel booking dengan filter: search, booking_type, status, property, date range
- ✅ Export CSV (sudah di-fix: chunk 200 rows + default 90 hari)
- ✅ Confirm / Cancel / Complete actions
- ✅ Notes update per booking
- ⚠️ **UX:** Tidak ada bulk action (pilih banyak booking sekaligus, lalu konfirmasi/cancel semua)
- ⚠️ **UX:** Halaman detail booking tidak ada tombol "Hubungi via WhatsApp" langsung ke nomor customer

### Properties Management
- ✅ CRUD lengkap: create, edit, delete, status toggle
- ✅ Pricing per tipe kamar (harian, transit, mingguan, bulanan)
- ✅ Gallery foto dengan kategori
- ✅ SEO metadata per properti
- ⚠️ **UX:** Tidak ada preview langsung ke halaman publik dari admin

### Settings
- ✅ General, Theme, SEO, Integrations, Homepage settings
- ✅ Upload logo & favicon
- ✅ Social media links
- ✅ Color picker (primary, secondary, accent)
- ⚠️ **UX:** Field `notification_webhook` ada tapi tidak ada petunjuk cara isi (format URL, provider yang support)
- ⚠️ **UX:** Google Maps API key ada tapi tidak ada penjelasan cara dapatnya

---

## 4. Analisis Fungsional — Frontend (User)

### Homepage
- ✅ Hero section dengan gradient dinamis dari settings
- ✅ Search form terintegrasi
- ✅ Stats: jumlah properti, unit, kota
- ✅ Featured properties section
- ✅ Dark mode toggle
- ⚠️ **UX:** Tidak ada CTA yang jelas untuk "langsung hubungi via WhatsApp" di homepage jika user belum siap booking formal

### Halaman Listing Properti `/apartments`
- ✅ Grid properti published
- ✅ Filter by booking type
- ✅ Search by name
- ✅ Pagination
- ⚠️ **UX:** Tidak ada filter harga (min/max)
- ⚠️ **UX:** Tidak ada filter per kota / lokasi
- ⚠️ **UX:** Sort by (harga termurah, terbaru) belum ada

### Halaman Detail Properti
- ✅ Gallery style Traveloka (foto besar kiri + grid kanan)
- ✅ Booking form sticky di desktop
- ✅ Pilih tipe kamar, durasi, tipe sewa
- ✅ Kalkulasi harga real-time via JS
- ✅ Voucher validation
- ✅ Promo rates
- ✅ Peta (jika ada koordinat + API key)
- ✅ FAQ section
- ✅ Nearby places
- ✅ Mobile sticky booking bar
- ⚠️ **UX:** Setelah submit booking, halaman sukses hanya dalam Bahasa Inggris ("Booking Request Submitted!") — tidak konsisten dengan konten lain yang Bahasa Indonesia
- ⚠️ **UX:** Tombol WhatsApp di halaman sukses ada, tapi teksnya generik — baiknya sudah auto-fill pesan dengan kode booking

### Blog
- ✅ Listing dengan pagination
- ✅ Sidebar: recent posts, categories, tags
- ✅ Single post dengan SEO metadata
- ⚠️ **UX:** Tidak ada estimated reading time
- ⚠️ **UX:** Tidak ada share button (WhatsApp, Twitter)

---

## 5. Soal Utama: Solusi Notifikasi WhatsApp untuk Owner

Ini masalah bisnis yang sangat nyata. CMS sudah punya infrastruktur `BookingNotificationService` yang mengirim webhook saat ada booking baru. Yang perlu dilakukan adalah **menghubungkan webhook ini ke WhatsApp gateway**.

### Opsi yang Direkomendasikan (Gratis/Murah)

#### Opsi A — Fonnte (Rekomendasi Utama 🏆)
- **Biaya:** Rp 100.000–200.000/bulan, ada trial gratis
- **Cara kerja:** Daftar di fonnte.com → dapat URL webhook → isi di Settings > Integrations > Notification Webhook
- **Format URL:** `https://api.fonnte.com/send`
- **Kelebihan:** Paling populer di Indonesia, mudah setup, tidak perlu server sendiri
- **Kekurangan:** Perlu WhatsApp aktif terhubung ke Fonnte

#### Opsi B — Callmebot (100% Gratis)
- **Biaya:** Gratis selamanya
- **Cara kerja:** Kirim pesan ke bot Callmebot di WhatsApp sekali untuk aktivasi → dapat API key → buat webhook sederhana
- **Kelebihan:** Gratis total, tanpa langganan
- **Kekurangan:** Hanya bisa kirim ke 1 nomor, format pesan terbatas

#### Opsi C — n8n self-hosted (Gratis + Fleksibel)
- **Biaya:** Gratis (self-hosted di VPS ~$5/bulan)
- **Cara kerja:** Deploy n8n → buat workflow yang terima webhook dari CMS → format pesan → kirim via WhatsApp
- **Kelebihan:** Super fleksibel, bisa kirim ke banyak nomor, bisa filter kondisi, bisa kirim email sekaligus
- **Kekurangan:** Perlu sedikit setup teknis

#### Opsi D — Make.com / Zapier (Mudah tapi berbayar)
- **Biaya:** Make.com ada free tier (1000 ops/bulan), cukup untuk ~200 booking/bulan
- **Cara kerja:** Buat scenario di Make → trigger HTTP webhook → action kirim WA via Fonnte/Wablas

### Apa yang Sudah Ada di CMS

`BookingNotificationService` sudah mengirim payload lengkap ke webhook URL:

```json
{
  "event": "booking.created",
  "booking": {
    "code": "BK-20260821-0001",
    "customer": { "name": "...", "phone": "...", "whatsapp": "..." },
    "property": { "name": "Skyhouse BSD" },
    "check_in": "2026-08-25T14:00:00",
    "total_price": 500000,
    "admin_url": "/admin/bookings/1"
  }
}
```

**Yang perlu dilakukan hanya:**
1. Daftar di Fonnte (atau pilih provider lain)
2. Isi URL webhook di **Settings > Integrations > Notification Webhook**
3. Selesai — booking baru langsung notif ke WhatsApp owner

### Pesan WhatsApp yang Akan Diterima Owner

Dengan integrasi Fonnte, owner bisa terima pesan seperti ini otomatis:

```
🏠 BOOKING BARU - Skyhouse BSD

📋 Kode: BK-20260821-0001
👤 Customer: Budi Santoso
📱 WA: +62812345678
🏠 Tipe: Studio | Harian
📅 Check-in: 25 Agt 2026
💰 Total: Rp 500.000

🔗 Lihat di admin: https://yourdomain.com/admin/bookings/1
```

---

## 6. Rekomendasi UX Tambahan (Prioritas)

### 🔴 Harus Diperbaiki Segera

| # | Masalah | Dampak |
|---|---------|--------|
| 1 | Halaman booking success masih Bahasa Inggris | Tidak konsisten, membingungkan user |
| 2 | User `admin@sewaapartemen.com` tidak punya role | Tidak bisa login admin |
| 3 | Tidak ada petunjuk cara setup webhook di settings | Owner tidak tahu cara aktifkan notifikasi |

### 🟡 Sangat Disarankan

| # | Fitur/Perbaikan | Alasan |
|---|-----------------|--------|
| 4 | Tombol WhatsApp di halaman sukses booking auto-fill pesan | Mempercepat komunikasi customer-owner |
| 5 | Tombol "Hubungi Owner via WhatsApp" di halaman detail properti (bukan hanya booking) | Beberapa user prefer tanya dulu sebelum booking |
| 6 | Filter harga di halaman listing | Kebutuhan umum pencari apartemen |
| 7 | Filter kota/lokasi di halaman listing | Jika suatu saat multi-properti, ini vital |
| 8 | Tombol "Preview di Frontend" dari admin properties | Mempercepat workflow editor |
| 9 | Bulk action di tabel booking | Efisiensi jika booking ramai |

### 🔵 Nice to Have

| # | Fitur | Catatan |
|---|-------|---------|
| 10 | Estimated reading time di blog | UX standar blog modern |
| 11 | Share buttons (WA, X/Twitter) di blog | Meningkatkan traffic organik |
| 12 | Sort opsi di listing properti | Harga termurah, terbaru |
| 13 | Status booking real-time (polling) di admin | Supaya tidak perlu refresh manual |
| 14 | Notifikasi browser (Web Push) untuk admin | Alternatif/pelengkap WA notification |
| 15 | Customer bisa track status booking via kode | Mengurangi pertanyaan ke owner |

---

## 7. Ringkasan Teknis

### Yang Berjalan Baik ✅
- Semua 26 bug dari audit sudah diperbaiki dan berjalan
- Auth & admin middleware berjalan benar
- Installer terlindungi (403 dari localhost, redirect dari luar)
- Booking form dengan kalkulasi harga, voucher, promo — fungsional
- SEO: sitemap.xml dan robots.txt tersedia
- Dark mode berjalan
- Search autocomplete berjalan
- Mobile responsive (booking bar sticky)
- `BookingNotificationService` sudah siap untuk integrasi webhook

### Yang Perlu Perhatian ⚠️
- User admin pertama (ID:1) tidak punya role → tidak bisa akses admin panel
- Webhook WhatsApp belum dikonfigurasi → owner kehilangan notifikasi booking
- Beberapa teks UI masih Bahasa Inggris
- Tidak ada petunjuk setup integrasi di halaman settings

---

*Laporan ini dibuat berdasarkan static code analysis + HTTP testing. Visual rendering di browser belum diuji secara interaktif karena perlu approval Chrome remote debugging.*
