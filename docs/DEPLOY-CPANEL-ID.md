# Troubleshooting Deployment cPanel (Bahasa Indonesia)

> Panduan pemecahan masalah untuk error fatal saat deploy Laravel CMS ke cPanel dengan **docroot terkunci** di `/home/<user>/public_html` (tidak bisa diarahkan ke `/public`).

---

## 1. Ringkasan Masalah

**Gejala:**

- `https://<domain>/install` tidak bisa dibuka.
- Halaman menampilkan fatal error (atau blank/500) dengan pesan kira-kira:

  ```
  Fatal error: require(): Failed opening required
  '/home/<user>/public_html/../sewa-cms/bootstrap/app.php'
  (include_path='...') in /home/<user>/public_html/index.php on line 17
  ```

- Di cPanel **Error Logs** (`Metrics > Errors`) atau log PHP muncul baris yang sama.

**Akar masalah singkat:** isi zip installer di-upload ke lokasi yang **salah**. `index.php` yang sudah diedit mencari folder `../sewa-cms/` (relatif terhadap `public_html/`), tetapi folder `sewa-cms/` **tidak ada** di `/home/<user>/`. PHP fatal terjadi **sebelum** route installer sempat dijalankan.

---

## 2. Diagnosa Root Cause

### 2.1 Struktur yang Diharapkan

Deployment dengan docroot terkunci (panduan **Opsi 3A** pada [`DEPLOYMENT-CPANEL.md`](DEPLOYMENT-CPANEL.md)) mengharuskan aplikasi penuh berada **di luar** docroot:

```
/home/<user>/
├── public_html/              # Apache document root (TERKUNCI)
│   ├── index.php            # Salinan dari sewa-cms/public/index.php (sudah diedit)
│   ├── .htaccess            # Salinan dari sewa-cms/public/.htaccess
│   ├── build/
│   ├── favicon.ico
│   ├── robots.txt
│   ├── <asset public lainnya>
│   └── storage -> /home/<user>/sewa-cms/storage/app/public   (symlink)
│
└── sewa-cms/                # SELURUH aplikasi (di luar public_html)
    ├── app/
    ├── bootstrap/
    ├── config/
    ├── database/
    ├── lang/
    ├── public/              # TIDAK ikut dipindah — hanya isinya yang disalin ke public_html
    ├── resources/
    ├── routes/
    ├── storage/
    ├── vendor/
    ├── .env
    ├── artisan
    ├── composer.json
    └── ...
```

### 2.2 Yang Terjadi

User meng-upload isi zip installer ke `public_html/` — sehingga seluruh aplikasi (bukan hanya isi `public/`) berada di dalam docroot, dan folder `/home/<user>/sewa-cms/` **tidak pernah dibuat**.

| Yang benar | Yang terjadi (salah) |
|---|---|
| `sewa-cms/` berisi seluruh app di `/home/<user>/` | Tidak ada folder `sewa-cms/` sama sekali |
| `public_html/` hanya berisi isi `public/` + symlink `storage` | `public_html/` berisi seluruh app (app, bootstrap, config, vendor, dll.) |
| `index.php` diedit dengan `../sewa-cms/...` | `index.php` diedit dengan `../sewa-cms/...` → path tidak ada → **fatal error** |

`index.php` yang sudah diedit berisi:

```php
require __DIR__.'/../sewa-cms/vendor/autoload.php';

$app = require_once __DIR__.'/../sewa-cms/bootstrap/app.php';
```

Karena `index.php` berada di `/home/<user>/public_html/`, maka `__DIR__.'/../sewa-cms'` = `/home/<user>/sewa-cms`. Folder itu harus ada; jika tidak, PHP fatal di line 17 sebelum Laravel (termasuk route installer) sempat di-bootstrap.

> **Kesimpulan:** masalah ini **bukan** karena zip rusak, bukan karena `.htaccess`, dan bukan karena database. Murni karena struktur folder tidak sesuai dengan yang diharapkan `index.php`.

---

## 3. Perbaikan Langkah Demi Langkah (dari Kondisi Saat Ini)

Perbaiki lewat **cPanel File Manager**. Cara termudah: karena seluruh app sudah ada di `public_html/`, pindahkan ke tempat yang benar.

### a. Buat folder `/home/<user>/sewa-cms`

1. Login cPanel → **File Manager**.
2. Klik **Home** (atau `..` sampai ke `/home/<user>/`).
3. Klik **+ Folder** → nama: `sewa-cms` → **Create New Folder**.

### b. Pindahkan (MOVE, bukan copy) isi aplikasi ke `sewa-cms/`

Masih di File Manager, masuk ke folder `public_html/`. **Pilih** (centang) item berikut lalu klik **Move**:

| Folder | File |
|---|---|
| `app` | `artisan` |
| `bootstrap` | `composer.json` |
| `config` | `composer.lock` (jika ada) |
| `database` | `package.json` (opsional) |
| `lang` | |
| `resources` | |
| `routes` | |
| `storage` | |
| `vendor` | |

Tujuan move: `/home/<user>/sewa-cms/`.

**JANGAN** ikut dipindah:

- Folder `public` — biarkan dulu; isinya akan dipindah/copy ke root `public_html/` (langkah c).
- File `.htaccess` di root `public_html/` — ini milik docroot.
- File/folder lain yang bukan bagian aplikasi (mis. `cgi-bin`, `error_log`, `well-known`).

> **Catatan `storage`:** isi folder `storage` dipindah bersama aplikasi ke `sewa-cms/storage`. Jika ada file di dalamnya yang baru dibuat (mis. `storage/logs/laravel.log`), tidak masalah — tetap bisa dipindah.

### c. Pastikan isi `public/` ada di root `public_html/`

Isi folder `sewa-cms/public/` harus berada **langsung** di `public_html/` (bukan di `public_html/public/`).

1. Buka `sewa-cms/public/` → pilih semua isinya → **Move** ke `/home/<user>/public_html/` (atau **Copy** lalu hapus folder `public`).
2. Yang harus berpindah:

   ```
   index.php
   .htaccess
   build/
   favicon.ico
   robots.txt
   <asset publik lain, mis. css/, js/, images/ jika ada>
   ```

3. Pastikan `public_html/index.php` ada di **root** `public_html/` — bukan di `public_html/public/index.php`.
4. Jika sudah, folder `sewa-cms/public/` boleh dihapus.

Hasil akhir diharapkan:

```
/home/<user>/
├── public_html/
│   ├── index.php          ← SALINAN dari public/ (sudah diedit, lihat §2.2)
│   ├── .htaccess
│   ├── build/
│   ├── favicon.ico
│   ├── robots.txt
│   └── storage -> /home/<user>/sewa-cms/storage/app/public
└── sewa-cms/
    ├── app/
    ├── bootstrap/
    ├── config/
    ├── database/
    ├── lang/
    ├── resources/
    ├── routes/
    ├── storage/
    ├── vendor/
    ├── .env
    ├── artisan
    └── composer.json
```

### d. Buat symlink `storage`

Via **SSH / Terminal** (di cPanel: **Terminal** di grup Advanced, jika akun mengizinkan):

```bash
ln -s /home/<user>/sewa-cms/storage/app/public /home/<user>/public_html/storage
```

**Jika tidak ada akses Terminal/SSH:** ajukan tiket ke support hosting dengan template lengkap:

```
Subject: Request to create a symbolic link (shared hosting)

Dear Support,

I need to deploy a Laravel application on my account. Please create the
following symbolic link for me:

  ln -s /home/<user>/sewa-cms/storage/app/public /home/<user>/public_html/storage

The target directory already exists and is owned by my account. No other
changes to my account are required. Thank you.

Regards,
<nama Anda>
```

### e. Set izin (permission)

Via Terminal, atau lewat File Manager (klik kanan folder → **Change Permissions**):

```bash
chmod 775 /home/<user>/sewa-cms/storage
chmod 775 /home/<user>/sewa-cms/storage/framework
chmod 775 /home/<user>/sewa-cms/storage/logs
chmod 775 /home/<user>/sewa-cms/bootstrap/cache
```

### f. Buat database

1. cPanel → **MySQL® Databases**.
2. Buat database baru (mis. `lyarooms_sewacms`).
3. Buat user database + assign **ALL PRIVILEGES** ke database tersebut.
4. Catat: nama database, user, password — dipakai di langkah g (installer).

### g. Buka installer

Buka di browser:

```
https://<domain>/install
```

Ikuti langkah installer (requirements → konfigurasi aplikasi → database → admin → selesai). Detail ada di [`INSTALLER.md`](INSTALLER.md).

### h. Cache konfigurasi (setelah install selesai)

Via Terminal:

```bash
cd /home/<user>/sewa-cms && php artisan config:cache
```

> Jika tanpa Terminal: lewati langkah ini, atau minta support host menjalankan perintah di atas. Aplikasi tetap berjalan tanpa cache config.

---

## 4. Alternatif (Opsi B): Semua di Dalam `public_html`

Gunakan jika **tidak bisa** membuat folder di luar docroot (mis. upload langsung ke `public_html/` dan tidak ada akses ke home directory). Ini sesuai **Opsi 3B** pada [`DEPLOYMENT-CPANEL.md`](DEPLOYMENT-CPANEL.md).

> ⚠️ **Peringatan:** risiko keamanan **lebih tinggi** — seluruh kode aplikasi (`vendor/`, `config/`, `.env`) berada di dalam docroot yang bisa diakses web. Aturan deny `.htaccess` adalah satu-satunya pelindung. **Utamakan Opsi A** (section 3) bila memungkinkan.

### 4.1 Kembalikan `index.php` ke path relatif docroot

`public_html/index.php` harus dikembalikan menjadi:

```php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
```

(Balikkan dari versi `../sewa-cms/...` — karena sekarang aplikasi berada **di dalam** `public_html/`, bukan di `../sewa-cms/`.)

### 4.2 Struktur yang berlaku

```
/home/<user>/public_html/     # Document root DAN root aplikasi
├── app/
├── bootstrap/
├── config/
├── database/
├── resources/
├── routes/
├── storage/                  # symlink → /home/<user>/sewa-storage/app/public
├── vendor/
├── lang/
├── tests/
├── .env
├── .htaccess                # DITAMBAH aturan deny (lihat §4.4)
├── index.php                # versi __DIR__.'/vendor/...' (lihat §4.1)
├── artisan
├── build/
├── favicon.ico
├── robots.txt
└── ...
```

### 4.3 Symlink storage — masalah loop dan solusinya

Symlink `public_html/storage → public_html/storage/app/public` akan **loop ke dirinya sendiri** (target berada di dalam sumber). Solusinya: pindahkan storage **ke luar** docroot.

1. Buat folder `/home/<user>/sewa-storage` (mis. copy `storage/app`, `storage/framework`, `storage/logs` dari aplikasi).
2. Beri tahu Laravel lokasi storage — tambahkan di `bootstrap/app.php` **sebelum** `return $app;`:

   ```php
   $app->useStoragePath('/home/<user>/sewa-storage');
   ```

3. Buat symlink untuk upload publik:

   ```bash
   ln -s /home/<user>/sewa-storage/app/public /home/<user>/public_html/storage
   ```

   Tanpa shell access, ajukan ke support host dengan template di §3.d (ganti target menjadi `/home/<user>/sewa-storage/app/public`).

### 4.4 Wajib: aturan deny di `public_html/.htaccess`

Tambahkan **di bagian atas** `public_html/.htaccess` (sebelum aturan rewrite):

```apache
RedirectMatch 403 ^/(app|bootstrap|config|database|routes|storage|vendor|lang|tests)/
RedirectMatch 403 ^/\.(env|git)
```

Verifikasi setelah deploy (harus balas **403**):

```bash
curl -I https://<domain>/.env
curl -I https://<domain>/config/app.php
curl -I https://<domain>/storage/logs/laravel.log
```

### 4.5 Lanjutkan

- Buat database (sama seperti §3.f).
- Buka `https://<domain>/install`.
- Cache config:

  ```bash
  cd /home/<user>/public_html && php artisan config:cache
  ```

---

## 5. Catatan Penting

- **Zip installer sudah mengecualikan `storage/installed.lock`** — sehingga route `/install` langsung aktif setelah upload, tanpa perlu menghapus lock file.
- **Jangan meng-upload ulang `storage/installed.lock`** (mis. dari backup lama). Jika file itu ada, installer akan dialihkan (redirect) dan `/install` tidak bisa diakses lagi.
- **Verifikasi struktur cepat** — sebelum membuka `/install`, pastikan file berikut ada di lokasinya:

| Lokasi | Wajib ada |
|---|---|
| `/home/<user>/sewa-cms/vendor/autoload.php` | Ya (hasil `composer install`) |
| `/home/<user>/sewa-cms/bootstrap/app.php` | Ya |
| `/home/<user>/sewa-cms/storage/` | Ya (writable, chmod 775) |
| `/home/<user>/sewa-cms/bootstrap/cache/` | Ya (writable, chmod 775) |
| `/home/<user>/public_html/index.php` | Ya (di root, bukan di `public_html/public/`) |
| `/home/<user>/public_html/.htaccess` | Ya (di root) |
| `/home/<user>/public_html/storage` | Symlink → `sewa-cms/storage/app/public` |

Cek cepat via **File Manager**: jika `public_html/` masih berisi folder `app/`, `bootstrap/`, `config/`, `vendor/`, dst. — struktur masih salah (belum mengikuti §3.b).

---

## 6. FAQ

### "Kenapa saya upload zip ke `public_html` tapi tetap error?"

Karena panduan yang dipakai (`index.php` versi `../sewa-cms/...`) mengharuskan aplikasi berada di `/home/<user>/sewa-cms/`, **bukan** di `public_html/`. Meng-upload isi zip ke `public_html/` membuat `../sewa-cms/` tidak ditemukan → fatal error. Ikuti §3 untuk memindahkan aplikasi ke lokasi yang benar, atau pakai Opsi B (§4) jika tidak bisa keluar docroot.

### "Apakah saya perlu zip baru?"

Tidak. Zip yang sama cukup — masalahnya hanya lokasi file, bukan isi zip. Cukup pindahkan folder sesuai §3, atau pakai Opsi B dengan mengembalikan `index.php` (§4.1).

### "Bagaimana kalau saya tidak punya SSH/Terminal?"

Tetap bisa:
- **Move/pindah file** — pakai File Manager (section 3).
- **Symlink storage** — ajukan ke support hosting dengan template di §3.d.
- **Chmod** — File Manager → klik kanan folder → Change Permissions.
- **Cache config** — opsional; minta support menjalankan `php artisan config:cache` di `/home/<user>/sewa-cms`, atau lewati (aplikasi tetap berjalan).

---

## Referensi

- Panduan deploy lengkap: [`DEPLOYMENT-CPANEL.md`](DEPLOYMENT-CPANEL.md) (Opsi 3A / 3B)
- Panduan installer: [`INSTALLER.md`](INSTALLER.md)
- Troubleshooting umum: [`TROUBLESHOOTING.md`](TROUBLESHOOTING.md)
