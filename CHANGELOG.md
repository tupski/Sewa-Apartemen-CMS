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

_Belum ada perubahan sejak 1.1.0._
_No changes yet since 1.1.0._

---

## [1.1.0] - 2026-09-07

Rilis fitur: media responsif (WebP/AVIF + srcset), markup Core Web Vitals
(LCP/CLS), arsitektur blog pillar–cluster, manajemen hari libur nasional, SEO
override halaman sistem, dan rangkaian tooling Version Control di admin.
_Feature release: responsive media (WebP/AVIF + srcset), Core Web Vitals markup
(LCP/CLS), blog pillar–cluster architecture, national holiday management,
system page SEO overrides, and the admin Version Control tooling suite._

### Added

- **Media Responsif — Varian WebP/AVIF + srcset (CWV Phase B)**: Service baru
  `App\Services\ImageVariantService` menghasilkan varian lebar 400/800/1600 px
  (tanpa upscale melebihi original) plus re-encode WebP dan AVIF untuk setiap
  upload gambar raster, disimpan di folder `variants/` di samping file asli dan
  dicatat ke `media.metadata.variants` (entry berisi width/height/size/url/path).
  Generasi di-hook ke pipeline upload terpusat (`MediaController@persistUploadedFile`)
  sehingga berlaku untuk upload file, drag & drop multi-file, dan import dari URL.
  Semuanya best-effort: kegagalan codec atau sumber rusak hanya melewati varian
  tersebut — upload tidak pernah gagal karena generasi varian. Penghapusan media
  ikut membersihkan semua varian. Codec dideteksi runtime (`imagewebp`/`imageavif`),
  sehingga host tanpa AVIF otomatis skip varian AVIF tanpa error.
  _**Responsive Media — WebP/AVIF Variants + srcset (CWV Phase B)**: New service
  `App\Services\ImageVariantService` generates 400/800/1600 px width variants
  (never upscaling past the original) plus WebP and AVIF re-encodes for every
  raster image upload, stored under a sibling `variants/` folder and recorded in
  `media.metadata.variants` (entries carry width/height/size/url/path). Generation
  is hooked into the centralized upload pipeline (`MediaController@persistUploadedFile`)
  so single uploads, multi-file drag & drop, and URL imports all get variants.
  Everything is best-effort: codec or corrupt-source failures skip that variant —
  uploads never fail because of variant generation. Deleting media also removes
  all its variants. Codecs are detected at runtime (`imagewebp`/`imageavif`), so
  hosts without AVIF silently skip AVIF variants._

- **Komponen Blade `x-media-image`**: Komponen tunggal untuk semua gambar berbasis
  Media di frontend. Merender `<picture>` dengan `<source type="image/avif">` dan
  `<source type="image/webp">` (saat varian tersedia) plus `srcset` ladder pada
  format asli, atribut `sizes` per-slot, `width`/`height` intrinsik dari metadata
  (tidak pernah dikarang), `loading` + `fetchpriority` via prop `eager` untuk LCP,
  dan degradasi mulus ke `<img>` biasa tanpa varian. Dipakai di kartu property
  (`_card`), homepage (carousel + grid), detail property (foto utama, thumbnail,
  fallback featured image), dan sukses booking — menggantikan `<img>` manual.
  _**Blade component `x-media-image`**: A single component for every Media-based
  image on the frontend. It renders a `<picture>` with `<source type="image/avif">`
  and `<source type="image/webp">` (when variants exist) plus a srcset ladder on
  the original format, per-slot `sizes`, intrinsic `width`/`height` from metadata
  (never fabricated), `loading` + `fetchpriority` via the `eager` prop for LCP,
  and degrades to a plain `<img>` without variants. Used by the property card
  partial, homepage (carousel + grid), property detail (hero, thumbnails,
  featured-image fallback), and booking success — replacing hand-written `<img>`s._

- **Command `media:generate-variants`**: Backfill varian untuk media lama.
  Idempotent secara default (hanya memproses record tanpa `metadata.variants`),
  `--force` untuk regenerate semua, `--id=` untuk satu record. Laporan per-record
  dengan ringkasan generated/skipped/failed.
  _**`media:generate-variants` command**: Backfills variants for legacy media.
  Idempotent by default (only processes records without `metadata.variants`),
  `--force` regenerates everything, `--id=` targets a single record. Per-record
  output with a generated/skipped/failed summary._

- **Core Web Vitals — Prioritas LCP & Stabilisasi CLS (CWV Phase A)**: Semua
  `<img>` frontend berbasis Media kini membawa dimensi intrinsik asli
  (`width`/`height` dari `media.width`/`media.height`) sehingga browser memesan
  ruang layout sebelum gambar termuat. Kandidat LCP ditentukan dari layout nyata —
  bukan sekadar menghapus `loading="lazy"`: kartu property pertama di homepage
  (hero hanya gradient teks) dan foto galeri pertama di halaman property
  mendapat `loading="eager" fetchpriority="high"`; tepat satu kandidat per
  halaman. Halaman detail property mem-preload tepat satu gambar LCP via
  `@stack('head')` — dengan `imagesrcset`/`imagesizes`/`type` yang identik dengan
  pilihan `<picture>` (AVIF → WebP → original) saat varian tersedia, sehingga
  tidak ada double-fetch; preload dilewati total bila metadata dimensi kosong.
  Script Lucide dipindah ke versi pinned `1.42.0` + `defer` (tidak lagi
  render-blocking `@latest`), dengan `lucide.createIcons()` dibungkus
  `DOMContentLoaded` di layout frontend dan admin.
  _**Core Web Vitals — LCP Prioritisation & CLS Stabilisation (CWV Phase A)**:
  Every Media-based frontend `<img>` now carries real intrinsic dimensions
  (`width`/`height` from `media.width`/`media.height`) so browsers reserve layout
  space before the image loads. The LCP candidate is derived from the actual
  layout — not by stripping `loading="lazy"` everywhere: the first property card
  on the homepage (the hero is a text gradient) and the first gallery photo on
  property pages get `loading="eager" fetchpriority="high"`; exactly one candidate
  per page. Property detail preloads exactly one LCP image via `@stack('head')` —
  with `imagesrcset`/`imagesizes`/`type` matching what `<picture>` selects
  (AVIF → WebP → original) when variants exist, avoiding double fetches; the
  preload is skipped entirely when dimension metadata is missing. The Lucide
  script is now pinned at `1.42.0` + `defer` (no longer render-blocking
  `@latest`), with `lucide.createIcons()` wrapped in `DOMContentLoaded` in both
  frontend and admin layouts._

- **Manajemen Hari Libur Nasional**: Model + tabel `national_holidays`, service
  sinkronisasi terjadwal, kalender libur di dashboard admin, dan integrasi:
  peringatan libur pada form post admin, sidebar blog, dan perhitungan booking.
  _**National Holiday Management**: New `national_holidays` model/table, a
  scheduled sync service, a holiday calendar in the admin dashboard, and
  integrations: holiday hints on the admin post form, blog sidebar, and booking
  calculations._

- **Arsitektur Blog Pillar–Cluster**: Kolom `pillar_post_id` pada tabel posts,
  relasi dan helper di model `Post`, pemilihan cluster di form admin, dan
  tampilan artikel terkait berbasis cluster pada halaman blog — mendukung struktur
  konten topik-utama/anak-topik untuk SEO topikal.
  _**Blog Pillar–Cluster Architecture**: A `pillar_post_id` column on posts,
  relations and helpers on the `Post` model, cluster selection in the admin form,
  and cluster-based related-article rendering on blog pages — enabling
  topic/cluster content structure for topical SEO._

- **SEO Override Halaman Sistem**: Controller dedikasi untuk mengelola override
  judul/deskripsi/robots halaman-halaman sistem (home, kontak, dsb.) dengan
  integrasi `SeoService`.
  _**System Page SEO Overrides**: A dedicated controller to manage title/
  description/robots overrides for system pages (home, contact, etc.), wired into
  `SeoService`._

- **SEO Meta Kategori & Tag Blog**: Kolom deskripsi untuk tags, meta title dan
  description untuk halaman arsip kategori dan tag, termasuk verifikasi foto alt
  pada validasi property.
  _**Blog Category & Tag SEO Meta**: A description column for tags plus meta
  titles and descriptions for category/tag archive pages, including photo alt
  validation enhancements on properties._

- **Schema `postArticle` Diperkaya**: Output JSON-LD `postArticle` kini menyertakan
  `wordCount`, `articleSection`, dan `about` — divalidasi unit test.
  _**Enriched `postArticle` Schema**: The `postArticle` JSON-LD output now
  includes `wordCount`, `articleSection`, and `about` — covered by unit tests._

- **Property CTA di Blog**: Section call-to-action property pada halaman artikel
  dan sidebar blog, dikendalikan konfigurasi `config/blog.php` via service
  `BlogPropertyService`.
  _**Property CTA in Blog**: Property call-to-action sections on article pages
  and the blog sidebar, driven by `config/blog.php` via `BlogPropertyService`._

- **Featured Image Handling di Admin Posts**: Pemilih dan pratinjau featured
  image yang lebih baik pada form post admin (alur upload + library media).
  _**Featured Image Handling in Admin Posts**: Improved featured-image picker and
  preview on the admin post form (upload + media library flow)._

- **Dokumentasi Indonesian-First**: Set dokumentasi baru (`docs/ADMIN.md`,
  `docs/ARCHITECTURE.md`, `docs/BOOKING.md`, `docs/DATABASE.md`,
  `docs/DEPLOYMENT.md`, `docs/FRONTEND.md`, dsb.) dengan README hub sebagai
  pintu masuk.
  _**Indonesian-First Documentation**: A new documentation set (`docs/ADMIN.md`,
  `docs/ARCHITECTURE.md`, `docs/BOOKING.md`, `docs/DATABASE.md`,
  `docs/DEPLOYMENT.md`, `docs/FRONTEND.md`, etc.) with a README hub as the entry
  point._

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

- **TypeError `syncTags()` pada PostController**: `PostController::syncTags()`
  menerima `string $tagString` dan memanggil `explode(',', $tagString)` — ketika
  request mengirim field `tags` bernilai null (mis. refactor validasi atau payload
  JSON eksplisit), `ConvertEmptyStringsToNull` membuat `input('tags', '')` tetap
  `null` sehingga method melempar `TypeError` (HTTP 500). Signature kini
  `?string $tagString` dengan normalisasi `$tagString ?? ''` di dalam method.
  _**TypeError in `PostController::syncTags()`**: `PostController::syncTags()`
  accepted `string $tagString` and called `explode(',', $tagString)` — when a
  request posted a null `tags` field (e.g. after a validation refactor or an
  explicit JSON payload), `ConvertEmptyStringsToNull` made `input('tags', '')`
  return `null`, so the method threw a `TypeError` (HTTP 500). The signature is
  now `?string $tagString` with `$tagString ?? ''` normalisation inside the
  method._

- **Error 500 edit property dengan pricing**: Perbaikan pada render form pricing
  admin property yang error saat update harga (cast/struktur data `prices`),
  ditutup dengan `PropertyEdit500FixTest`.
  _**500 error on property edit with pricing**: Fixed the admin property pricing
  form erroring on price updates (the `prices` cast/structure), covered by
  `PropertyEdit500FixTest`._

- **Render form & aksesibilitas admin panel**: Perbaikan masalah render form dan
  temuan aksesibilitas di panel admin (label, kontras, dan struktur fokus).
  _**Admin panel form rendering & accessibility**: Fixed form rendering issues
  and accessibility findings in the admin panel (labels, contrast, focus
  structure)._

- Pesan validasi pada Admin > Settings > SEO tidak lagi membocorkan kunci
  terjemahan mentah `validation.regex`; kolom yang formatnya salah sekarang
  menampilkan pesan yang bisa dibaca pengguna.
  _Validation messages in Admin > Settings > SEO no longer leak the raw
  `validation.regex` translation key; incorrectly formatted fields now show a
  human-readable message._

### Performance

- **Pengiriman gambar responsif**: Gambar frontend kini dilayani sesuai ukuran
  slot dan kemampuan browser — varian 400/800/1600 px via `srcset`/`sizes`,
  format modern WebP/AVIF via `<picture>`. Original ~300 KB per foto kini
  umumnya digantikan 25–80 KB pada slot kartu; halaman galeri property yang
  sebelumnya memuat 3 MB+ foto original turun drastis byte transfernya.
  _**Responsive image delivery**: Frontend images are now served at slot size
  and codec support — 400/800/1600 px variants via `srcset`/`sizes`, modern
  WebP/AVIF via `<picture>`. The ~300 KB per-photo original is commonly replaced
  by 25–80 KB files in card slots; property gallery pages that previously
  shipped 3 MB+ of originals drop their transfer size dramatically._

- **Prioritas & preload LCP**: Tepat satu gambar LCP per halaman mendapat
  `fetchpriority="high"` dan di-preload dengan kandidat yang sama dengan pilihan
  `<picture>`, mempercepat Largest Contentful Paint tanpa double-fetch.
  _**LCP priority & preload**: Exactly one LCP image per page gets
  `fetchpriority="high"` and is preloaded with the same candidate `<picture>`
  selects, speeding up Largest Contentful Paint without double fetches._

- **Lucide non-blocking**: Script ikon Lucide tidak lagi render-blocking
  (pinned `1.42.0` + `defer`).
  _**Non-blocking Lucide**: The Lucide icon script no longer blocks rendering
  (pinned `1.42.0` + `defer`)._

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
