# Laporan Audit Keamanan — Sewa Apartemen CMS

**Tanggal:** 2026-08-27
**Metode:** Static code review (read-only) — controllers, models, services, middleware, requests, routes, config, `.env`, `bootstrap/app.php`.
**Stack:** Laravel 13, PHP 8.3, Blade + Alpine + Tailwind, MySQL.
**Sifat laporan:** Dokumen ini satu-satunya artefak. Tidak ada kode aplikasi yang diubah.

> Temuan hanya dilaporkan bila input attacker-controlled terkonfirmasi DAN proteksi framework tidak menutupnya. Banyak temuan lama (booking token, voucher race, XSS admin, installer, mass-assignment) sudah ditutup di kode saat ini — dikonfirmasi dalam review dan dicatat di §5 "Kontrol Positif".

---

## 1. Ringkasan Eksekutif

| Severity | Jumlah |
|----------|--------|
| 🔴 Critical | 2 |
| 🟠 High | 3 |
| 🟡 Medium | 5 |
| 🔵 Low | 4 |
| **Total** | **14** |

Dua temuan Critical bersifat **konfigurasi produksi**, bukan cacat logika:
1. `APP_DEBUG=true` sementara `APP_ENV=production` → membocorkan stack trace, kredensial, dan isi `.env` ke publik pada error apa pun.
2. `cyberstrike.json` (114 KB) ter-*track* di git & tidak masuk `.gitignore` → berpotensi membocorkan konfigurasi provider/endpoint AI ke repo.

Temuan High berpusat pada **command execution via Git dashboard** (`exec()` git dari panel admin), **stored XSS via unsanitized map embed & analytics fields**, dan **SVG upload → stored XSS**.

Prioritas remediasi: (1) `APP_DEBUG=false` + rotasi `APP_KEY`/DB bila `.env` pernah bocor, (2) keluarkan `cyberstrike.json` dari git, (3) kunci/whitelist Git dashboard, (4) sanitasi map-embed & sniff SVG uploads.

---

## 2. Skala Severity

| Level | Definisi |
|-------|----------|
| 🔴 Critical | Bocor kredensial, RCE, atau eksposur data massal dengan jalur nyata |
| 🟠 High | XSS tersimpan, command injection terbatas, kontrol akses lemah pada jalur sensitif |
| 🟡 Medium | Hardening hilang (header, cookie), DoS terbatas, info leak parsial |
| 🔵 Low | Defense-in-depth, praktik baik, ketahanan jangka panjang |

---

## 3. Temuan

### 🔴 CRITICAL

#### SEC-01 · `APP_DEBUG=true` di environment `production`
**File:** `.env` (`APP_ENV=production`, `APP_DEBUG=true`)
**Deskripsi:** Whoops/Ignition menampilkan stack trace lengkap pada setiap exception — termasuk fragmen `.env`, query SQL, path, dan nilai variabel. Di masa lalu bug Ignition (CVE-2021-3129) bahkan memungkinkan RCE.
**Dampak:** Attacker memicu error (mis. input malformed) → membaca kredensial DB, `APP_KEY`, kunci mail/webhook. `APP_KEY` bocor = semua session/cookie terenkripsi dapat dipalsukan.
**Solusi:**
```dotenv
APP_DEBUG=false
```
Lalu:
```bash
php artisan config:clear
php artisan config:cache
```
Verifikasi: picu 500 error, pastikan hanya halaman generic yang tampil.

---

#### SEC-02 · `cyberstrike.json` ter-track di git & tidak di-ignore
**File:** `cyberstrike.json` (114 KB, `git ls-files` menunjukkan ter-track), `.gitignore` (tidak memuat entri)
**Deskripsi:** File konfigurasi provider AI (endpoint, nama model, kemungkinan kredensial/URL internal) ikut ter-commit. Bila repo di-push ke remote/publik, konfigurasi ini bocor permanen di histori git.
**Dampak:** Eksposur endpoint & konfigurasi pihak ketiga; jika ada token → penyalahgunaan kuota/akses. Histori git menyimpan salinan meski file dihapus kemudian.
**Solusi:**
```bash
echo 'cyberstrike.json' >> .gitignore
git rm --cached cyberstrike.json
git commit -m "chore: stop tracking cyberstrike.json (contains provider config)"
```
Jika file pernah di-push ke remote publik: rotasi kredensial apa pun di dalamnya dan pertimbangkan `git filter-repo` untuk membersihkan histori. **(Irreversible di histori — konfirmasi sebelum rewrite history.)**

---

### 🟠 HIGH

#### SEC-03 · Command execution via Git dashboard (`exec()`)
**File:** `app/Http/Controllers/SettingsController.php:452` (`gitPull`), `:472` (`gitFetch`), `:491` (`runGit` → `exec()` + `chdir()`)
**Deskripsi:** Panel admin mengekspos `git pull`/`git fetch`/`git status` yang dieksekusi via `exec()` pada shell server. Perintah saat ini hardcoded (aman dari injeksi argumen langsung), tetapi: (a) siapa pun dengan sesi super-admin dapat memicu `git pull` yang menarik & mengeksekusi kode arbitrer dari remote; (b) `chdir()` mengubah cwd proses secara global (race pada request konkuren); (c) eksposur output git mentah ke UI.
**Dampak:** Kompromi akun super-admin → remote code pull → potensi RCE bila remote/branch dibajak. `chdir` global dapat menyebabkan operasi file request lain salah direktori.
**Solusi:**
- Nonaktifkan fitur di production kecuali benar-benar perlu; gating tambahan (IP allowlist / env flag `GIT_DASHBOARD_ENABLED`).
- Ganti `chdir()+exec()` dengan `Symfony\Component\Process\Process` yang menerima `cwd` argumen (tanpa mengubah cwd global):
```php
use Symfony\Component\Process\Process;
$p = new Process(['git', 'pull', 'origin', 'main'], base_path());
$p->setTimeout(60)->run();
$output = $p->getOutput() . $p->getErrorOutput();
```
- Pin remote & branch; verifikasi signature commit bila memungkinkan.

---

#### SEC-04 · Stored XSS via `contact_map_embed` (unsanitized `{!! !!}`)
**File:** `resources/views/contact/index.blade.php:11,97` (`{!! $mapEmbed !!}`), sumber `SettingsService::get('contact_map_embed')`
**Deskripsi:** Nilai map-embed di-render mentah tanpa melewati `SafeHtmlService`. Setting ini disimpan sebagai raw HTML (iframe). Tidak ada rute/UI setter yang ditemukan (`grep` kosong) — nilai bisa berasal dari seeder/DB langsung — tetapi field-nya raw-rendered, jadi siapa pun yang dapat menulis setting itu (admin panel di masa depan, restore backup, akses DB) mendapat XSS tersimpan persisten pada halaman kontak publik.
**Dampak:** Eksekusi JS di browser semua pengunjung halaman `/kontak` → pencurian sesi, defacement.
**Solusi:** Batasi ke iframe Google Maps yang divalidasi, bukan raw HTML:
```php
// saat menyimpan setting: hanya izinkan iframe dari domain maps Google
$allowed = preg_match('#^<iframe[^>]+src="https://www\.google\.com/maps/embed[^"]*"[^>]*></iframe>$#i', trim($embed));
```
atau simpan hanya URL `src` dan bangun iframe di server-side. Jika harus raw, lewati `SafeHtmlService::sanitize()` (perlu allow `iframe` dengan src-allowlist).

---

#### SEC-05 · SVG upload → stored XSS
**File:** `app/Http/Requests/MediaRequest.php:29`, `app/Http/Controllers/MediaController.php:23,332,434` (SVG diizinkan; `getimagesize`/thumbnail dilewati untuk SVG)
**Deskripsi:** Upload SVG diizinkan dan disimpan ke disk `public`. SVG dapat memuat `<script>`/`onload`. Bila di-serve dengan `Content-Type: image/svg+xml` dan diakses langsung (`/storage/...svg`), browser mengeksekusi script-nya (same-origin dengan aplikasi).
**Dampak:** Admin/editor (atau siapa pun dengan akses upload) mengunggah SVG berisi JS → XSS tersimpan saat file dibuka langsung; bisa dieksploitasi lewat link.
**Solusi:** Pilih salah satu:
- Sanitasi SVG saat upload (hapus `<script>`, event handler, `<foreignObject>`) — reuse pendekatan `SafeHtmlService` khusus SVG.
- Atau serve semua media user dengan header `Content-Disposition: attachment` / `Content-Security-Policy: default-src 'none'` untuk path `/storage`.
- Atau hapus `svg` dari daftar mimes yang diizinkan bila tidak esensial.

---

### 🟡 MEDIUM

#### SEC-06 · Cookie `secure` & `SESSION_ENCRYPT` tidak dipaksakan di production
**File:** `.env` (`SESSION_ENCRYPT=false`, tidak ada `SESSION_SECURE_COOKIE`), `config/session.php:172`
**Deskripsi:** `SESSION_SECURE_COOKIE` kosong → cookie sesi bisa terkirim via HTTP polos (app melayani di belakang Cloudflare Tunnel TLS). `same_site=lax` OK, `http_only=true` OK. Tanpa `secure`, cookie rawan tersadap pada koneksi non-TLS.
**Dampak:** Session hijacking pada jaringan tidak tepercaya bila ada jalur HTTP.
**Solusi:**
```dotenv
SESSION_SECURE_COOKIE=true
```
(Opsional `SESSION_ENCRYPT=true` untuk defense-in-depth.) Aplikasi sudah `ForceHttps` saat `isSecure()`, jadi ini aman diaktifkan.

---

#### SEC-07 · Content-Security-Policy & HSTS tidak ada
**File:** `app/Http/Middleware/SecurityHeaders.php:15-17`
**Deskripsi:** Hanya `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`. Tidak ada CSP (mitigasi XSS berlapis, relevan untuk SEC-04/05) maupun `Strict-Transport-Security`.
**Dampak:** Tidak ada lapisan kedua bila XSS lolos; downgrade HTTPS mungkin.
**Solusi:** Tambah HSTS langsung (aman, banyak inline script menyulitkan CSP ketat):
```php
$response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
```
CSP: mulai `Content-Security-Policy-Report-Only` dulu karena banyak inline `<script>` (analytics/Alpine) — pindahkan inline JS ke file sebelum menerapkan CSP enforcing.

---

#### SEC-08 · SSRF: redirect diikuti + rentang IP tidak lengkap pada import URL
**File:** `app/Http/Controllers/MediaController.php:367-486` (`downloadFromUrl`/`assertPublicHost`)
**Deskripsi:** Guard SSRF sudah bagus (allowlist scheme, blok private/loopback via `FILTER_FLAG_NO_PRIV_RANGE|NO_RES_RANGE`, cap ukuran, `FOLLOWLOCATION=false`). Namun: (a) resolusi DNS terjadi lalu koneksi memakai host yang sama — celah **DNS rebinding** teoretis (TOCTOU antara `assertPublicHost` dan `curl`); (b) `FILTER_FLAG_NO_RES_RANGE` tidak menutup semua rentang khusus cloud metadata IPv6 / `100.64.0.0/10` (CGNAT).
**Dampak:** Terbatas — attacker perlu kontrol DNS + timing. Bisa menembak layanan internal via metadata endpoint di skenario tertentu.
**Solusi:** Resolve IP sekali, lalu paksa `curl` konek ke IP itu (`CURLOPT_RESOLVE`) alih-alih meresolusi ulang; tambah blokir eksplisit `169.254.169.254` dan `100.64.0.0/10`.

---

#### SEC-09 · Backup restore truncate + `FOREIGN_KEY_CHECKS=0` (data-loss & privilege)
**File:** `app/Services/BackupService.php:108-142`, `app/Http/Controllers/Admin/BackupController.php`
**Deskripsi:** Restore men-`truncate()` tabel target (termasuk `users`, `roles`, `model_has_roles`) lalu insert dari file JSON yang di-upload admin, dengan FK checks dimatikan. Tidak ada validasi skema/isi baris terhadap kolom yang diharapkan.
**Dampak:** File backup jahat/rusak → penghapusan data (truncate berjalan sebelum insert gagal di dalam transaksi, tapi baris JSON arbitrer bisa menimpa `users`/`roles` → eskalasi privilege dengan menanam super-admin). Path traversal sudah ditutup di `confirmRestore` (baik).
**Solusi:** Validasi setiap baris terhadap kolom whitelist per tabel sebelum insert; tolak kolom tak dikenal; pertimbangkan larangan restore grup `users`/`roles` via UI atau wajib konfirmasi ekstra + audit log.

---

#### SEC-10 · CAPTCHA & contact form fail-open + tanpa CAPTCHA di endpoint tulis publik
**File:** `app/Http/Middleware/VerifyCaptcha.php:24,38`, `routes/web.php:71` (`bookings.store` hanya `throttle:10,1`)
**Deskripsi:** CAPTCHA sengaja fail-open saat provider error / misconfigured (mencegah lockout — desain diterima). Namun `bookings.store` publik hanya dilindungi throttle, tanpa CAPTCHA/honeypot → spam booking. Contact form punya honeypot + captcha (baik).
**Dampak:** Pembuatan booking massal otomatis (spam DB, notifikasi WhatsApp/email membanjir, konsumsi voucher).
**Solusi:** Terapkan `captcha` middleware pada `bookings.store`, atau tambah honeypot field seperti contact form. Pertahankan throttle.

---

### 🔵 LOW

#### SEC-11 · `env()` dipakai di runtime middleware (`ProtectInstaller`)
**File:** `app/Http/Middleware/ProtectInstaller.php:27-28`
**Deskripsi:** `env('INSTALLER_ALLOWED_IPS')` / `env('INSTALLER_TOKEN')` dibaca langsung. Setelah `php artisan config:cache`, `env()` di luar config mengembalikan `null` → whitelist/token menjadi kosong, hanya localhost yang lolos (fail-secure di sini, tapi perilaku membingungkan).
**Solusi:** Pindahkan ke `config/installer.php` dan baca via `config('installer.allowed_ips')`.

---

#### SEC-12 · `storage/installed.lock` ter-track di git
**File:** `git ls-files` → `storage/installed.lock`
**Deskripsi:** File lock instalasi ikut versi. Meng-clone repo membawa lock, sehingga installer selalu terkunci di environment baru (mungkin disengaja), tapi lock seharusnya artefak runtime, bukan repo.
**Solusi:** `git rm --cached storage/installed.lock` dan tambahkan ke `.gitignore` bila lock dimaksud per-environment.

---

#### SEC-13 · Output git & pesan exception mentah ke UI admin
**File:** `SettingsController.php:444,464,481` (`$e->getMessage()` dikembalikan ke JSON), `SettingsController.php:360`
**Deskripsi:** Pesan exception mentah (path, detail git, SQL) dikembalikan ke klien admin. Info leak minor (butuh sesi admin).
**Solusi:** Log detail via `Log::error`, kembalikan pesan generik ke klien.

---

#### SEC-14 · Rate limit login 5x tetapi tanpa CAPTCHA bertingkat / lockout akun
**File:** `app/Http/Requests/Auth/LoginRequest.php:63` (`tooManyAttempts(...,5)`)
**Deskripsi:** Throttle per email+IP (baik) tetapi attacker dengan banyak IP dapat menyebar brute force; tidak ada CAPTCHA setelah N gagal.
**Solusi:** Aktifkan `captcha` middleware pada rute login (sudah tersedia) setelah beberapa kegagalan, atau tambah delay progresif. Prioritas rendah karena bcrypt 12 + throttle sudah memadai untuk kebanyakan kasus.

---

## 4. Rencana Remediasi Bertahap

| Fase | Temuan | Aksi | Reversible |
|------|--------|------|------------|
| 1 (segera) | SEC-01, SEC-02 | `APP_DEBUG=false`; keluarkan `cyberstrike.json` dari git; rotasi kredensial bila `.env` pernah bocor | Config: ya. History rewrite: tidak |
| 2 | SEC-03, SEC-04, SEC-05 | Kunci Git dashboard (Process+flag); validasi map-embed; sanitasi/attachment SVG | ya |
| 3 | SEC-06 … SEC-10 | Secure cookie, HSTS, SSRF hardening, validasi restore, CAPTCHA booking | ya |
| 4 | SEC-11 … SEC-14 | config() untuk env, unignore lock, generic errors, login captcha | ya |

---

## 5. Kontrol Positif (Sudah Aman — Dikonfirmasi)

- **SQL injection:** query pakai Eloquent/parameter binding. `whereRaw` di `PropertyController:43` & `DashboardController:44` & `PromotionController:33` memakai placeholder/kolom statis, bukan input mentah. LIKE wildcard di `SearchController:31` di-escape. ✅
- **Booking PII exposure:** rute publik dikunci `access_token` acak 24 char (`BookingService:167`), bukan id/kode sekuensial. ✅
- **Voucher double-spend:** `lockForUpdate()` + increment di dalam `DB::transaction` (`BookingService:64,189`). ✅
- **Double-booking TOCTOU:** `lockForUpdate()` pada cek ketersediaan dalam transaksi create (`BookingService:219-227`). ✅
- **Stored XSS konten admin:** `SafeHtmlService::sanitize()` diterapkan di Block/Page/Post/Property content sebelum persist. ✅
- **Mass assignment:** controller pakai `$request->validate()` + `Model::create($validated)`, tidak ada `->fill($request->all())`. ✅
- **Upload extension bypass:** ekstensi diturunkan dari MIME map server-side (`app/Helpers/upload.php:64`), bukan `getClientOriginalExtension()`. ✅
- **Installer:** `ProtectInstaller` (localhost/IP/token) + `installed.lock`; password admin tidak ditulis ke state file (`InstallerController:378`); identifier DB divalidasi regex sebelum masuk DSN (`:271`). ✅
- **Path traversal (backup restore):** `realpath` + `str_starts_with($baseDir)` guard (`BackupController:110-119`). ✅
- **Open redirect:** `RedirectMiddleware` hanya dari tabel `redirects` terkelola + cycle detection. ✅
- **Self-privilege escalation / self-delete:** dijaga di `UserController:89,170`; role assignment dibatasi `assignableRoleIds()`. ✅
- **Admin gating:** `['auth','verified','admin']` pada grup admin + `/dashboard`; `EnsureUserIsAdmin` abort(403). ✅
- **CSRF:** default Laravel web middleware aktif. **Password:** bcrypt 12 rounds. ✅

---

## 6. Catatan

- `docs/security-audit-report.md` (2026-08-24) sudah ada; laporan ini adalah pass ulang pada kode terkini — banyak temuan lama sudah remediated (lihat §5). Fokus baru: config produksi (`APP_DEBUG`), file provider ter-track, Git dashboard, map-embed & SVG XSS.
- Tidak ada perubahan kode dilakukan. Semua fix di §3 memerlukan persetujuan sebelum implementasi; SEC-02 history-rewrite bersifat irreversible.
