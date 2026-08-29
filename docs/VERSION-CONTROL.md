# Version Control & Rollback (Admin)

_Version Control & Rollback (Admin)_

Dashboard **Version Control** di panel admin (Settings → Version Control) — fitur yang dibangun baru untuk mengelola update kode dari remote git dan rollback. **Baca bagian peringatan rollback dengan saksama.**

_The **Version Control** dashboard in the admin panel (Settings → Version Control) — a newly built feature to manage code updates from the git remote and rollback. **Read the rollback warning section carefully.**_

---

## Fitur / _Features_

Backend oleh [`GitService`](../app/Services/GitService.php) + [`SettingsController`](../app/Http/Controllers/SettingsController.php:525) (metode `git*`). View: [`resources/views/admin/settings/partials/_git.blade.php`](../resources/views/admin/settings/partials/_git.blade.php).

### 1. Info Remote Origin (dengan redaksi kredensial)

Menampilkan URL remote git (`git remote get-url origin`). Kredensial tertanam (mis. `https://user:pass@host/...`) **selalu diredaksi** sebelum disimpan/ditampilkan.

### 2. Riwayat Commit

Tabel commit dari `git log --all`:

| Kolom | Isi |
|-------|-----|
| **Waktu Commit** | Relative time jika < 1 hari (mis. "2 jam lalu"); jika lebih, `DD/MM/YYYY HH:mm` **WIB** (timezone `Asia/Jakarta` di-pin — [`GitService::DISPLAY_TIMEZONE`](../app/Services/GitService.php:55)) |
| **Commit Message** | Subject commit |
| **Author** | Nama author (`%an`) |
| **Commit ID** | Hash pendek (`%h`) |
| **Branch** | Ref/branch yang menunjuk commit |
| **Action** | Tombol rollback / info HEAD |

- **Default 5 commit** ([`GitService::COMMIT_DISPLAY_LIMIT`](../app/Services/GitService.php:23)) + tombol **Show More** yang menambah 20 ([`SHOW_MORE_INCREMENT`](../app/Services/GitService.php:28)) per klik.
- Endpoint: [`gitCommitHistory`](../app/Http/Controllers/SettingsController.php:818) — `limit` di-clamp 1..200, `skip` ≥ 0 (anti hasil set patologis).
- Format log pakai unit separator (`\x1f`) & record separator (`\x1e`) agar aman terhadap pesan commit eksotis.

### 3. Update Checker & Lencana Header / _Update Checker & Header Badge_

- Artisan command: [`CheckForGitUpdates`](../app/Console/Commands/CheckForGitUpdates.php) (`git:check-updates`).
- **Terjadwal harian 01:00 WIB** (`Asia/Jakarta` di-pin) di [`routes/console.php`](../routes/console.php:20).
- Hasil disimpan ke cache (`key: git_update_check`, driver `file`) via `Cache::forever`.
- Header admin membaca cache ini **murah tanpa menyentuh git/network saat render halaman** — ketika ada update, menampilkan **lencana kuning beranimasi `animate-ping`** (dilindungi `motion-safe:` untuk `prefers-reduced-motion`) dengan jumlah commit tertinggal; ketika tidak ada update, lencana tidak dirender sama sekali.
- Tombol **"Periksa pembaruan sekarang"** di area Version Control memicu pemeriksaan on-demand ([`gitCheckUpdates`](../app/Http/Controllers/SettingsController.php:1025)).
- **Kondisi kegagalan aman**: detached HEAD, git tidak tersedia, tidak ada remote, tidak ada jaringan → semua menghasilkan status aman tanpa laporan update palsu.
- Command idempotent: skip jika hasil sudah di-cache hari ini kecuali `--force`.

### 4. Aksi Lain / _Other Actions_

- **Post-update** ([`gitPostUpdate`](../app/Http/Controllers/SettingsController.php:609)) — aksi setelah update (via [`PostUpdateActionService`](../app/Services/PostUpdateActionService.php)): jalankan migrasi, `config:cache`/`route:cache`, dll.
- **Backup database** ([`gitBackupDatabase`](../app/Http/Controllers/SettingsController.php:971)) — dump `.sql` lengkap via [`BackupService`](../app/Services/BackupService.php), disimpan di `storage/app/private`, mengembalikan link download.
- **Kembali ke puncak branch** ([`gitReturnToBranch`](../app/Http/Controllers/SettingsController.php:936)) — escape hatch dari detached HEAD (lihat di bawah).

---

## ⚠️ SEMANTIK ROLLBACK — BACA INI DULU / _ROLLBACK SEMANTICS — READ THIS FIRST_

> Ini adalah **peringatan keselamatan paling penting** di seluruh dokumentasi.

### Apa yang dilakukan rollback / _What rollback does_

**Rollback adalah `git checkout <commit>`** ([`gitRollback`](../app/Http/Controllers/SettingsController.php:857)):

- SHA datang dari klien, divalidasi server-side terhadap `^[0-9a-f]{7,40}$` **dan** di-resolve ke objek `commit` nyata (`git cat-file -t` = `commit`) sebelum checkout.
- Hanya super-admin (role `admin`/`super-admin`).
- Hasilnya: **DETACHED HEAD** — posisi kode di commit tersebut, tidak lagi di puncak branch.

### Yang TIDAK dilakukan rollback / _What rollback does NOT do_

**Rollback TIDAK men-rollback skema database.**

- Rollback hanya memindahkan **kode** (file). Migrasi yang sudah pernah dijalankan di database **tetap ada**.
- Jika commit target lebih tua dari sebuah migrasi (commit target mendahului migrasi tersebut), maka:
  - **Skema database lebih maju (ahead) dari kode.**
  - Kode lama tidak tahu apa-apa tentang kolom/tabel baru → berpotensi error runtime, atau (lebih berbahaya) kode lama menulis data yang tidak konsisten dengan skema baru.
- Migrasi **tidak otomatis di-rollback** oleh fitur ini. Jangan harapkan `migrate:rollback` terjadi.

### Langkah wajib / _Mandatory steps_

1. **Backup database dulu** — gunakan tombol **Backup Database** di dashboard Version Control (atau **System → Backup & Restore**) **sebelum rollback**. Simpan hasil `.sql` di luar server.
2. Lakukan rollback hanya jika Anda memahami risiko di atas.
3. Setelah rollback, **verifikasi aplikasi** (muat halaman publik & admin, jalankan aksi penting).

### Keluar dari Detached HEAD — "Kembali ke Puncak Branch" / _Leaving Detached HEAD — "Return to Branch Tip"_

- Setelah rollback, HEAD terlepas. Untuk kembali ke puncak branch (mis. `main`/`master`):
  - Gunakan tombol **"Kembali ke puncak branch"** di dashboard → menjalankan `git checkout <branch>` dan mengembalikan HEAD ke puncak branch (deteksi otomatis `main` → `master` — [`GitService::DEFAULT_BRANCH_CANDIDATES`](../app/Services/GitService.php:38)).
  - Atau manual via terminal: `git checkout main` (atau `master`).
- **Catatan**: commit apa pun yang Anda buat saat detached HEAD tidak terhubung ke branch dan bisa hilang. Jangan buat perubahan/commit saat dalam status detached kecuali Anda tahu apa yang Anda lakukan.

### Mengapa ini berbahaya — contoh konkret / _Why this is dangerous — concrete example_

```
Deploy v1.2.0  →  migrate (menambahkan kolom X)
  ↓
Rollback ke v1.0.0  →  kode v1.0.0 TIDAK tahu kolom X
  ↓
Skema DB masih punya kolom X (ahead), kode v1.0.0 mungkin gagal
```

Jika ini terjadi, opsi pemulihan: **restore database dari backup** (dikembalikan ke titik sebelum migrasi v1.2.0) **atau** kembali ke puncak branch (kode v1.2.0 yang cocok dengan skema).

---

## Keamanan / _Security_

- Semua invokasi git via **Symfony Process dengan argumen array** — tidak pernah shell string ([`GitService::runGit`](../app/Services/GitService.php:64)). Tidak ada input user yang mencapai shell interpreter.
- SHA divalidasi regex + resolve objek nyata.
- Rollback & aksi destruktif: hanya super-admin.
- Lihat [`docs/SECURITY.md`](SECURITY.md).

## Test / _Tests_

- [`tests/Feature/GitRollbackFeatureTest.php`](../tests/Feature/GitRollbackFeatureTest.php)
- [`tests/Feature/GitUpdateCheckTest.php`](../tests/Feature/GitUpdateCheckTest.php)
- [`tests/Feature/GitDashboardErrorTest.php`](../tests/Feature/GitDashboardErrorTest.php)
- [`tests/Feature/GitPostUpdateTest.php`](../tests/Feature/GitPostUpdateTest.php)

## Lihat Juga / _See Also_

- [`docs/DEPLOYMENT.md`](DEPLOYMENT.md) — deployment manual & cron
- [`docs/ADMIN.md`](ADMIN.md) — Settings → Version Control
- [`CHANGELOG.md`](../CHANGELOG.md) — entri "Scheduled Update Check + Admin Header Update Badge"
