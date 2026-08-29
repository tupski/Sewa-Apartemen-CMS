# Changelog

Semua perubahan penting pada **Artivo CMS** dicatat di berkas ini.
_All notable changes to **Artivo CMS** are documented in this file._

Produk: **Artivo CMS** — dibangun untuk **PT KAKARAMA Samudera Group**.
_Product: **Artivo CMS** — built for **PT KAKARAMA Samudera Group**._

Format mengikuti [Keep a Changelog](https://keepachangelog.com/id-ID/1.1.0/)
dan proyek ini menganut [Semantic Versioning](https://semver.org/lang/id/).
_Format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and this
project adheres to [Semantic Versioning](https://semver.org/)._

Setiap entri ditulis **Bahasa Indonesia dahulu**, lalu terjemahan Inggris dalam
baris _italic_ di bawahnya.
_Each entry is written **Indonesian first**, followed by the English translation
in an italic line beneath it._

> Sumber tunggal nomor versi: `config/artivo.php` → kunci `version`, dibaca
> dengan `config('artivo.version')`. Naikkan nomor di sana dan tambahkan entri
> bertanggal di bawah ini pada setiap rilis.
> _Single source of truth for the version number: `config/artivo.php` → the
> `version` key, read via `config('artivo.version')`. Bump it there and add a
> dated entry below on every release._

---

## [Unreleased]

### Added

- **Pemeriksaan Pembaruan Terjadwal + Lencana Pembaruan di Header Admin**: Artisan command baru `git:check-updates`
  memeriksa apakah kode yang di-deploy tertinggal dari remote Git-nya. Command dijadwalkan harian pukul 01:00 WIB
  (zona waktu `Asia/Jakarta` di-pin secara eksplisit di `routes/console.php` terlepas dari timezone app). Hasil
  disimpan ke cache (key `git_update_check`, driver `file`) sehingga header admin dapat membacanya murah tanpa
  menyentuh git atau jaringan saat render halaman. Ketika ada pembaruan, header admin menampilkan lencana kuning
  beranimasi `animate-ping` (dilindungi `motion-safe:` untuk pengguna `prefers-reduced-motion`) dengan jumlah
  commit tertinggal; ketika tidak ada pembaruan, lencana tidak dirender sama sekali. Tombol "Periksa pembaruan
  sekarang" di area Version Control memungkinkan admin memicu pemeriksaan on-demand tanpa menunggu jadwal.
  Kondisi kegagalan ditangani dengan baik: detached HEAD, git tidak tersedia, tidak ada remote, dan tidak ada
  jaringan semuanya menghasilkan status aman tanpa melaporkan pembaruan palsu. URL remote dengan kredensial
  tertanam selalu diredaksi sebelum disimpan ke cache.
  _**Scheduled Update Check + Admin Header Update Badge**: New Artisan command `git:check-updates` checks whether
  the deployed code is behind its Git remote. Scheduled daily at 01:00 WIB (`Asia/Jakarta` timezone pinned
  explicitly in `routes/console.php` regardless of app timezone). Result is persisted to cache (key
  `git_update_check`, `file` driver) so the admin header reads it cheaply without touching git or the network on
  page render. When updates are available, the admin header shows an animated amber badge with the commits-behind
  count (animation gated behind `motion-safe:` for `prefers-reduced-motion` users); when no update is available,
  the badge is not rendered at all. A "Check for updates now" button in the Version Control area lets admins
  trigger an on-demand check without waiting for the scheduler. Failure cases are handled gracefully: detached
  HEAD, git unavailable, no remote, no network all yield a safe state without reporting a false update. Remote
  URLs with embedded credentials are always redacted before being written to cache._

- **Version Control → Remote Origin Info**: Area Version Control di admin sekarang menampilkan URL remote origin
  (dengan kredensial yang disamarkan, misalnya `https://***@github.com/...`), branch aktif, upstream
  tracking branch, dan indikator Detached HEAD.
  _**Version Control → Remote Origin Info**: The admin Version Control area now shows the remote origin
  URL (credentials redacted, e.g. `https://***@github.com/...`), the active branch, upstream tracking
  branch, and a Detached HEAD indicator._

- **Version Control → Riwayat Commit (tabel)**: Tabel commit terbaru ditampilkan di bawah konten Version
  Control yang sudah ada. Kolom: Waktu Commit · Pesan Commit · Author · Commit ID · Branch · Action.
  Standar: 5 commit terbaru; tombol "Tampilkan lebih banyak" memuat tambahan +20 sekaligus. Waktu
  commit < 1 hari ditampilkan sebagai string relatif (misal "5 menit yang lalu"); ≥ 1 hari ditampilkan
  sebagai `DD/MM/YYYY HH:mm` dalam zona waktu Asia/Jakarta (WIB). Commit HEAD saat ini ditandai dan
  tombol Rollback-nya dinonaktifkan.
  _**Version Control → Commit History (table)**: A commit history table is rendered below the existing
  Version Control content. Columns: Commit Time · Commit Message · Author · Commit ID · Branch · Action.
  Default: 5 most recent commits; a "Show more" button loads +20 at a time. Commit times < 1 day render
  as a relative human string (e.g. "5 menit yang lalu"); ≥ 1 day render as `DD/MM/YYYY HH:mm` in
  Asia/Jakarta (WIB). The current HEAD commit is marked and its Rollback button is disabled._

- **Version Control → Rollback (git checkout detached HEAD)**: Tombol "Rollback" pada setiap baris commit
  membuka modal peringatan yang menjelaskan: apa yang akan terjadi, commit mana yang menjadi target,
  dan bahwa rollback kode TIDAK mengembalikan skema database — jika commit target dibuat sebelum
  migrasi terbaru, skema akan lebih baru dari kode. Modal berisi dua aksi: **"Backup Database"**
  (menghasilkan dump `.sql` lengkap via `BackupService::dumpSql()`) dan **"Saya sudah backup,
  lanjutkan"** (melanjutkan rollback). Progres rollback ditampilkan per langkah: pemeriksaan
  working-tree → fetch → checkout commit → catatan migrasi. SHA commit divalidasi di sisi server
  terhadap pola `/^[0-9a-f]{7,40}$/` dan keberadaannya diverifikasi via `git cat-file -t` sebelum
  perintah checkout dijalankan. Rollback menggunakan `git checkout <commit>` (detached HEAD) — tidak
  ada penulisan ulang histori, tidak ada force push, tidak ada `reset --hard`.
  _**Version Control → Rollback (git checkout detached HEAD)**: The "Rollback" button on each commit row
  opens a warning modal explaining: what will happen, which commit is the target, and that rolling back
  code does NOT roll back the database schema — if the target commit predates a migration the schema
  will be ahead of the code. The modal has two actions: **"Backup Database"** (produces a full `.sql`
  dump via `BackupService::dumpSql()`) and **"Saya sudah backup, lanjutkan"** (proceeds with the
  rollback). Rollback progress is shown per step: dirty-tree check → fetch → checkout commit →
  migrations note. The commit SHA is validated server-side against `/^[0-9a-f]{7,40}$/` and its
  existence is verified via `git cat-file -t` before the checkout command runs. Rollback uses
  `git checkout <commit>` (detached HEAD) — no history rewrite, no force push, no `reset --hard`._

- **Version Control → Kembali ke Ujung Branch**: Ketika repo berada dalam mode Detached HEAD, UI
  menampilkan indikator kuning dan tombol "Kembali ke ujung branch" yang menjalankan
  `git checkout main` (atau `master`, atau branch default yang terdeteksi) untuk mengembalikan ke
  ujung branch yang normal.
  _**Version Control → Return to branch tip**: When the repo is in Detached HEAD mode the UI shows a
  yellow indicator and a "Return to branch tip" button that runs `git checkout main` (or `master`, or
  the detected default branch) to restore normal branch-tip state._

- **BackupService::dumpSql()**: Perluasan minimal pada `BackupService` yang sudah ada untuk menghasilkan
  dump MySQL `.sql` lengkap via `mysqldump` melalui Symfony Process (argument array, tanpa string
  shell). Jika `mysqldump` tidak ada di PATH, pesan error yang actionable dikembalikan; kredensial DB
  tidak pernah di-log atau ditampilkan ke klien. File dump disimpan di `storage/app/private/`.
  _**BackupService::dumpSql()**: A minimal extension on the existing `BackupService` to produce a full
  MySQL `.sql` dump via `mysqldump` through Symfony Process (argument array, no shell string). If
  `mysqldump` is not on PATH, an actionable error message is returned; DB credentials are never logged
  or exposed to the client. Dump files are stored under `storage/app/private/`._

- **app/Services/GitService.php**: Baru — semua logika git baru disentralisasi di sini: parsing
  riwayat commit dengan delimiter `\x1f`/`\x1e` (tidak pernah `|`), pemformatan waktu WIB, validasi
  SHA, redaksi URL remote, rollback, dan pemulihan dari Detached HEAD.
  _**app/Services/GitService.php**: New — all new git logic is centralized here: commit history parsing
  using `\x1f`/`\x1e` delimiters (never `|`), WIB time formatting, SHA validation, remote URL
  redaction, rollback, and Detached HEAD recovery._

### Fixed

- Pesan validasi pada Admin > Settings > SEO tidak lagi membocorkan kunci
  terjemahan mentah `validation.regex`; kolom yang formatnya salah sekarang
  menampilkan pesan yang bisa dibaca pengguna.
  _Validation messages in Admin > Settings > SEO no longer leak the raw
  `validation.regex` translation key; incorrectly formatted fields now show a
  human-readable message._

### Security

- Empat kolom ID analitik (`google_analytics_id`, `meta_pixel_id`,
  `microsoft_clarity_id`, dan `google_tag_manager_id` yang diperketat)
  sebelumnya diinterpolasi langsung ke keluaran `<script>` inline tanpa
  validasi format. Keempatnya sekarang dibatasi ketat sesuai format resmi
  masing-masing penyedia, sehingga menutup jalur injeksi skrip tersimpan
  melalui halaman pengaturan admin.
  _Four analytics ID fields (`google_analytics_id`, `meta_pixel_id`,
  `microsoft_clarity_id`, plus a hardened `google_tag_manager_id`) were
  previously interpolated straight into inline `<script>` output with no format
  validation. All four are now strictly format-constrained to each provider's
  official format, closing a stored script-injection path through the admin
  settings page._

---

## [1.0.0] - 2026-08-28

Rilis dasar (baseline) — keadaan aplikasi yang sudah berjalan saat penomoran
versi mulai diterapkan.
_Baseline release — the shipped state of the application at the point formal
versioning was introduced._

### Added

- **CMS properti/listing** — pengelolaan properti beserta foto, galeri,
  fasilitas (amenities), tipe unit, kebijakan, dan halaman detail publik.
  _**Property/listing CMS** — property management with photos, galleries,
  amenities, unit types, policies, and public detail pages._
- **Sistem booking** dengan layanan kanonis untuk perhitungan harga dan
  voucher: `BookingPricingService::calculate()`, `BookingService::create()`
  (transaksional), dan `Voucher::calculateDiscount()`. Mendukung tarif
  transit/harian/mingguan/bulanan, promo rate, serta pencarian status booking
  oleh tamu melalui token akses.
  _**Booking system** with canonical pricing and voucher services:
  `BookingPricingService::calculate()`, `BookingService::create()`
  (transactional), and `Voucher::calculateDiscount()`. Supports
  transit/daily/weekly/monthly rates, promo rates, and guest booking-status
  lookup via an access token._
- **Multi-bahasa (id/en)** — berkas terjemahan JSON serta pengelolaan bahasa
  di panel admin.
  _**Multi-language (id/en)** — JSON translation files plus language management
  in the admin panel._
- **Blog** — artikel, kategori, dan tag beserta halaman indeks dan detail
  publik.
  _**Blog** — posts, categories, and tags with public index and detail pages._
- **Media library** — unggah berkas, impor dari URL dengan proteksi SSRF, dan
  penyimpanan pada disk `public`.
  _**Media library** — file uploads, URL import with SSRF protections, and
  storage on the `public` disk._
- **SEO** — metadata polimorfik (`SeoMetadata`), `sitemap.xml`, `robots.txt`,
  dan pengelolaan redirect.
  _**SEO** — polymorphic metadata (`SeoMetadata`), `sitemap.xml`, `robots.txt`,
  and redirect management._
- **Pipeline tempat terdekat (Geoapify)** — POI dipersistensi ke tabel `places`
  dan `property_places` melalui `FetchNearbyPlacesJob` yang di-queue dan
  di-cache 24 jam; halaman properti publik tidak melakukan panggilan API
  keluar.
  _**Geoapify nearby-places pipeline** — POIs persisted into the `places` and
  `property_places` tables via the queued, 24h-cached `FetchNearbyPlacesJob`;
  the public property page makes no outbound API calls._
- **Panel admin** kustom berbasis Blade, lengkap dengan backup & restore serta
  dasbor pembaruan Git.
  _Custom Blade-based **admin panel**, including backup & restore and a Git
  update dashboard._
- **Web installer** untuk penyiapan awal aplikasi (persyaratan sistem,
  database, akun admin, identitas situs).
  _**Web installer** for initial application setup (system requirements,
  database, admin account, site identity)._
- **Kredit "Powered by Artivo CMS"** pada footer publik dan footer admin,
  menampilkan versi langsung dari `config('artivo.version')`.
  _**"Powered by Artivo CMS" credit** in the public and admin footers, rendering
  the live version from `config('artivo.version')`._

---

## Kebijakan Versi / Versioning Policy

Artivo CMS menggunakan `MAJOR.MINOR.PATCH`.
_Artivo CMS uses `MAJOR.MINOR.PATCH`._

- **MAJOR** — perubahan yang memutus kompatibilitas: perubahan aturan bisnis
  (formula harga, alur status booking, semantik voucher), migrasi destruktif,
  perubahan autentikasi/peran, penghapusan atau perubahan kontrak URL/rute
  publik, atau pembaruan yang menuntut langkah manual dari operator.
  _**MAJOR** — breaking changes: business-rule changes (pricing formulas,
  booking status flow, voucher semantics), destructive migrations,
  auth/role changes, removing or changing public URL/route contracts, or an
  upgrade that requires manual operator steps._
- **MINOR** — fitur baru yang kompatibel ke belakang: sumber daya admin baru,
  migrasi aditif, komponen atau halaman baru, penambahan bahasa atau
  pengaturan.
  _**MINOR** — backward-compatible new features: new admin resources, additive
  migrations, new components or pages, added languages or settings._
- **PATCH** — perbaikan bug, penguatan keamanan tanpa perubahan perilaku,
  perbaikan tampilan/teks, penyesuaian performa, dan perbaikan terjemahan.
  _**PATCH** — bug fixes, security hardening with no behavioral change, UI/copy
  fixes, performance tuning, and translation corrections._

Menaikkan versi berarti: sunting `version` di
[`config/artivo.php`](config/artivo.php), pindahkan isi `[Unreleased]` ke entri
bertanggal baru di berkas ini, lalu jalankan `php artisan config:cache` di
produksi.
_To cut a release: edit `version` in [`config/artivo.php`](config/artivo.php),
move the `[Unreleased]` contents into a new dated entry in this file, then run
`php artisan config:cache` in production._
