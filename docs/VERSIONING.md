# Versioning & Kebijakan Changelog

_Versioning & Changelog Policy_

**Semantic Versioning (SemVer)** — sumber tunggal nomor versi di `config/artivo.php`; setiap entri changelog mengikuti format [Keep a Changelog](https://keepachangelog.com/id-ID/1.1.0/). Dokumen ini **selaras dengan** [`CHANGELOG.md`](../CHANGELOG.md) — jangan bertentangan.

_Semantic Versioning — single source of truth for the version number in `config/artivo.php`; every changelog entry follows the [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) format. This document is **aligned with** [`CHANGELOG.md`](../CHANGELOG.md) — do not contradict it._

---

## Sumber Versi / _Version Source_

**Satu-satunya tempat** nomor versi ditulis: [`config/artivo.php`](../config/artivo.php:21):

```php
'version' => '1.0.0',
```

Baca di mana saja (termasuk Blade, controller, service) dengan:

```php
config('artivo.version')
```

Ini adalah literal biasa (bukan `env()`) — selalu tersedia, bertahan `php artisan config:cache`, tidak perlu `.env` key, tidak perlu shelling out ke git.

## Kebijakan MAJOR/MINOR/PATCH / _MAJOR/MINOR/PATCH Policy_

Dari [`CHANGELOG.md`](../CHANGELOG.md):

| Jenis | Arti | Contoh |
|-------|------|--------|
| **MAJOR** | Perubahan inkompatibel ke belakang (API, booking flow, pricing, database schema yang tidak sekedar aditif, tema) | Merombak sistem pricing, menghapus endpoint publik |
| **MINOR** | Fitur baru yang kompatibel ke belakang | Resource admin baru, migrasi aditif, komponen/halaman baru, penambahan bahasa/pengaturan |
| **PATCH** | Perbaikan bug, penguatan keamanan (tanpa perubahan perilaku), perbaikan UI/teks, penyesuaian performa, perbaikan terjemahan | Bug fix, keamanan, CSS fix, typo |

## Cara Merilis / _How to Cut a Release_

1. Sunting `version` di [`config/artivo.php`](../config/artivo.php:21).
2. Pindahkan isi `[Unreleased]` di [`CHANGELOG.md`](../CHANGELOG.md) ke entri bertanggal baru (format `## [1.0.1] — 2026-08-28`).
3. Tambahkan perbandingan link di bagian bawah changelog jika perlu.
4. Jalankan di produksi:
   ```bash
   php artisan config:cache
   ```

## Format Changelog / _Changelog Format_

- Setiap entri ditulis **Bahasa Indonesia dahulu**, lalu terjemahan Inggris dalam baris _italic_ di bawahnya.
- Gunakan bahasa Indonesia yang alami untuk developer/operator Indonesia (informal, langsung, jelas).
- Sub-headings: `### Added`, `### Changed`, `### Fixed`, `### Security`, `### Removed` — sesuai Keep a Changelog.
- Sertakan referensi file yang relevan dengan link relatif berformat `[nama-file](path/file:line)` (mis. `[`config/artivo.php`](../config/artivo.php:21)`).

## Lihat Juga / _See Also_

- [`CHANGELOG.md`](../CHANGELOG.md) — changelog lengkap
- [`config/artivo.php`](../config/artivo.php) — sumber versi
- [`docs/README.md`](README.md) — indeks dokumentasi
