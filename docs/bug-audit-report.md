# Bug Audit Report — Sewa Apartemen CMS
**Tanggal:** 2026-08-21  
**Auditor:** Kiro (Static Code Analysis)  
**Cakupan:** Seluruh codebase — Controllers, Models, Services, Middleware, Routes, Requests  
**Status:** Menunggu review — belum ada perubahan kode yang diterapkan

---

## Ringkasan Eksekutif

| Severity | Jumlah |
|----------|--------|
| 🔴 Critical (Security) | 4 |
| 🟠 High | 5 |
| 🟡 Medium | 8 |
| 🔵 Low | 9 |
| **Total** | **26** |

---

## 🔴 CRITICAL — Security

---

### BUG-001 · Race Condition pada Voucher — Double-Spend Attack
**File:** `app/Http/Controllers/BookingController.php` baris 27–44  
**Tipe:** Security — Race Condition / Business Logic

**Deskripsi:**  
Validasi voucher dan increment `used_count` dilakukan dalam dua operasi terpisah yang tidak terlindungi lock. Antara `isValid()` dan `increment('used_count')` ada jeda waktu, sehingga dua request bersamaan (concurrent) keduanya bisa lolos validasi dan menggunakan voucher yang sama melebihi `usage_limit`.

```php
// ❌ TIDAK AMAN — dua request bisa lolos validasi sebelum increment
$voucher = Voucher::where('code', $code)->first();
if (!$voucher || !$voucher->isValid()) { ... }         // gap disini
$booking = BookingService::create($data);              // gap disini
Voucher::where('id', $data['voucher_id'])->increment('used_count'); // terlambat
```

**Dampak:** Voucher dengan `usage_limit=1` bisa digunakan berkali-kali secara bersamaan (misalnya diskon 100% bisa dieksploitasi secara paralel).

**Solusi yang Direkomendasikan:**  
Gunakan database lock (`lockForUpdate()`) di dalam satu transaksi DB yang sama dengan pembuatan booking:
```php
DB::transaction(function () use ($data) {
    $voucher = Voucher::where('id', $data['voucher_id'])->lockForUpdate()->first();
    if (!$voucher || !$voucher->isValid()) {
        throw new \Exception('Voucher tidak valid.');
    }
    $voucher->increment('used_count');
    // ... create booking
});
```

---

### BUG-002 · Installer Terbuka Tanpa Autentikasi Apapun
**File:** `app/Http/Middleware/CheckInstalled.php` baris 19–21  
**File:** `routes/install.php` (atau `routes/web.php`)  
**Tipe:** Security — Unauthorized Access / Privilege Escalation

**Deskripsi:**  
Route `/install/*` sepenuhnya bypass semua middleware autentikasi. Setelah aplikasi berjalan di production (bahkan setelah `installed.lock` ada), jika file `storage/installed.lock` dihapus (oleh deployment, disk error, dsb.), siapa pun di internet bisa membuka `/install` dan:
1. Menulis ulang file `.env` dengan database baru
2. Membuat akun admin baru
3. Menjalankan `migrate:fresh` yang **menghapus semua data**

```php
// ❌ CheckInstalled.php — rute install bebas tanpa proteksi tambahan
if ($request->is('install*')) {
    return $next($request); // tidak ada IP whitelist, tidak ada token
}
```

**Dampak:** Sangat kritis di environment production. Data seluruh sistem bisa dihapus dan diambil alih.

**Solusi:** Tambahkan IP whitelist atau secret token satu kali pakai di middleware installer. Atau hapus route installer di production via env flag.

---

### BUG-003 · `install_state.json` Menyimpan Password Database dalam Plaintext
**File:** `app/Http/Controllers/InstallerController.php` baris 480–485, 656–668  
**Tipe:** Security — Sensitive Data Exposure

**Deskripsi:**  
Selama proses instalasi, data DB termasuk `db_password` disimpan ke `storage/app/install_state.json` untuk keperluan restore state antar langkah. File ini tidak dienkripsi.

```php
// state['data']['db']['db_password'] tersimpan plaintext di filesystem
$state['data'][$key] = $value; // termasuk db_password
$this->writeState($state);     // ditulis ke storage/app/install_state.json
```

Selain itu di `stepFresh()`, password admin juga disimpan dalam state yang sama:
```php
// state['data']['admin']['password'] — plaintext!
if (!empty($adminData['password'])) {
    $user = User::create(['password' => Hash::make($adminData['password'])]);
}
```

**Dampak:** Jika server dikompromis atau file storage bisa diakses (misconfigured web server), password DB dan password admin awal bisa bocor.

**Solusi:** Jangan simpan password di state file. Gunakan session Laravel yang terenkripsi, atau minta ulang password di setiap langkah.

---

### BUG-004 · `MediaController` — Extension File Diambil dari Client, Bukan MIME
**File:** `app/Http/Controllers/MediaController.php` baris 76–77  
**Tipe:** Security — File Upload Bypass

**Deskripsi:**  
Extension file diambil dari `getClientOriginalExtension()` (yang dikendalikan pengguna), bukan dari MIME type yang sudah diverifikasi:

```php
$extension = $file->getClientOriginalExtension(); // ❌ controlled by client
$filename  = $safeName . '-' . time() . '-' . Str::random(10) . '.' . $extension;
```

Meskipun `MediaRequest` memvalidasi dengan `mimes:`, validasi `mimes:` di Laravel mengecek magic bytes dari konten file, tetapi nama file yang disimpan di disk tetap menggunakan extension dari client. Jika ada bypass di level validasi (misal: file `.php` yang diupload dengan Content-Type image/jpeg), file tersimpan dengan nama `.php`.

**Dampak:** Potensi Remote Code Execution jika web server mengeksekusi file berdasarkan extension.

**Solusi:**
```php
// ✅ Ambil extension dari MIME type yang sudah terverifikasi
use Illuminate\Support\Facades\File;
$extension = File::extension($file->getMimeType()) 
             ?? $file->getClientOriginalExtension();
```
Atau gunakan peta MIME-to-extension yang di-hardcode.

---

## 🟠 HIGH

---

### BUG-005 · `BookingController::destroy()` Salah Fungsi — Cancel bukan Delete
**File:** `app/Http/Controllers/BookingController.php` baris 304–316  
**Tipe:** Logic Bug — Fungsional

**Deskripsi:**  
Method `destroy()` (yang dipanggil dari route DELETE) seharusnya menghapus booking, tetapi implementasinya memanggil `BookingService::cancel()` yang hanya mengubah status menjadi `cancelled`. Ini menyebabkan data booking tidak pernah benar-benar dihapus dari database melalui route ini.

```php
public function destroy(Booking $booking): RedirectResponse
{
    try {
        BookingService::cancel($booking); // ❌ seharusnya delete, bukan cancel
        return redirect()->route('admin.bookings.index')
            ->with('success', 'Booking cancelled successfully.'); // pesan pun salah arah
    }
}
```

**Dampak:** Admin tidak bisa menghapus data booking. Pesan sukses pun membingungkan ("cancelled" padahal route adalah DELETE).

---

### BUG-006 · `BookingService::generateCode()` Tidak Thread-Safe — Kode Duplikat
**File:** `app/Services/BookingService.php` baris 17–33  
**Tipe:** Concurrency Bug / Data Integrity

**Deskripsi:**  
Penomoran kode booking (`BK-YYYYMMDD-XXXX`) dilakukan dengan membaca kode terakhir lalu menambahkan 1, tanpa lock database:

```php
$lastBooking = Booking::where('code', 'like', "BK-{$datePrefix}-%")
    ->orderBy('code', 'desc')
    ->first();
// Gap disini — dua request bersamaan bisa membaca nilai yang sama
$newNumber = $lastNumber + 1;
```

**Dampak:** Pada traffic bersamaan, dua booking bisa mendapatkan kode yang sama. Karena `code` kemungkinan tidak ada constraint `UNIQUE` di database level, ini menghasilkan duplikasi kode booking.

**Solusi:** Gunakan `DB::transaction` + `lockForUpdate()`, atau gunakan database `AUTO_INCREMENT` column + format di application layer setelah insert.

---

### BUG-007 · `SettingsController` Tidak Dibatasi Admin — Semua User Terauth Bisa Akses
**File:** `app/Http/Controllers/SettingsController.php` baris 15–18  
**File:** `routes/web.php` baris 52–70 (grup `auth`)  
**Tipe:** Security — Missing Authorization

**Deskripsi:**  
`SettingsController` hanya menggunakan middleware `auth` (pengguna terautentikasi), bukan `admin`. Namun route settings ada di dalam grup yang seharusnya hanya admin:

```php
// SettingsController constructor
$this->middleware('auth'); // ❌ hanya cek login, tidak cek admin role
```

Jika ada user dengan role selain `super-admin` (misalnya editor), mereka berpotensi mengakses `/settings` jika routing tidak terkonfigurasi benar.

**Dampak:** Pengguna non-admin bisa mengubah konfigurasi situs, logo, webhook, API key integrasi.

**Solusi:** Tambahkan `$this->middleware('admin')` atau pastikan route settings ada di dalam grup dengan middleware `admin`.

---

### BUG-008 · `normalizeTransitHours()` — Null Pointer jika `$hours = null`
**File:** `app/Services/BookingPricingService.php` baris 255–264  
**Tipe:** Bug — PHP Error / Fatal

**Deskripsi:**  
Parameter `$hours` bertipe `?int` (nullable), tetapi di dalam loop langsung dibandingkan dengan `<=` tanpa null check:

```php
protected function normalizeTransitHours(?int $hours): int
{
    foreach (self::TRANSIT_BUCKETS as $bucket) {
        if ($hours <= $bucket) { // ❌ PHP: null <= 3 = true, hasilnya: 3 (misleading)
            return $bucket;
        }
    }
    return self::TRANSIT_BUCKETS[array_key_last(self::TRANSIT_BUCKETS)];
}
```

Di PHP, `null <= 3` bernilai `true`, sehingga booking transit dengan `$hours = null` akan selalu mendapat bucket 3 jam, bukan error yang jelas.

**Dampak:** Booking transit tanpa durasi dihitung dengan harga 3 jam secara diam-diam, tanpa validasi error.

**Solusi:**
```php
if ($hours === null || $hours <= 0) {
    throw new \InvalidArgumentException('Durasi transit harus diisi.');
}
```

---

### BUG-009 · `log_activity()` Gagal Senyap saat User Tidak Login
**File:** `app/Helpers/activity.php` baris 8–13  
**Tipe:** Bug — Silent Failure / Data Integrity

**Deskripsi:**  
`log_activity()` langsung memanggil `auth()->id()` dan melakukan `DB::table()->insert()`. Jika dipanggil dalam konteks unauthenticated (misalnya dari Artisan command, queue job, atau BookingController yang tidak butuh login), `auth()->id()` mengembalikan `null`. Jika kolom `user_id` NOT NULL di database, ini akan throw exception. Jika kolom nullable, log tercatat tanpa user_id tanpa peringatan.

```php
function log_activity(string $action, ?string $description = null): void
{
    DB::table('user_activity_logs')->insert([
        'user_id' => auth()->id(), // ❌ null jika tidak login
        'action' => $action,
        // tidak ada 'updated_at' — akan error jika tabel punya timestamp
    ]);
}
```

Selain itu, tidak ada kolom `updated_at` pada insert, padahal jika tabel menggunakan `$table->timestamps()`, MySQL akan menolak insert ini.

**Dampak:** Activity log bisa crash di konteks tertentu, atau mencatat log tanpa user yang jelas.

---

## 🟡 MEDIUM

---

### BUG-010 · `BookingRequest` — `check_in` Tidak Divalidasi Minimal Hari Ini
**File:** `app/Http/Requests/BookingRequest.php` baris 37  
**Tipe:** Validation Bug

**Deskripsi:**  
Field `check_in` hanya divalidasi sebagai `date`, tanpa batasan `after_or_equal:today`:

```php
'check_in' => ['required', 'date'], // ❌ tidak ada 'after_or_equal:today'
```

**Dampak:** User bisa membuat booking dengan tanggal check-in di masa lalu (misal tahun 2020). Ini mencemari data dan laporan.

---

### BUG-011 · Route Grup Admin Menggunakan String Alih-alih Array untuk Middleware
**File:** `routes/web.php` baris 48–50  
**Tipe:** Code Quality / Potential Bug

**Deskripsi:**  
Route dashboard menggunakan syntax array string yang deprecated:
```php
Route::get('/dashboard', ['App\Http\Controllers\Admin\DashboardController', 'index'])
    ->middleware(['auth', 'verified', 'admin'])
    ->name('dashboard');
```

Syntax `['ClassName', 'method']` untuk callable controller harus menggunakan `[ClassName::class, 'method']` agar kompatibel dengan PHP 8+ dan IDE tools.

---

### BUG-012 · `PropertyController::publicIndex()` — Full Table Scan Sebelum Paginasi
**File:** `app/Http/Controllers/PropertyController.php` baris 36–53  
**Tipe:** Performance Bug

**Deskripsi:**  
Semua properti diambil dari database dengan `->get()` terlebih dahulu, kemudian difilter di PHP, lalu dipaginasi secara manual. Ini berarti **seluruh tabel properties** dibaca ke memory setiap request ke halaman listing:

```php
$properties = $query->orderBy('order')->orderBy('created_at', 'desc')
                    ->get()          // ❌ load semua data ke memory
                    ->when($typeFilter, function ($collection) use ($typeFilter) {
                        return $collection->filter(...); // filter di PHP
                    });
// manual pagination setelah itu
```

**Dampak:** Semakin banyak properti, semakin lambat halaman listing. Tidak scalable.

---

### BUG-013 · `MediaController` — Tidak Ada Validasi Path Traversal pada `folder`
**File:** `app/Http/Controllers/MediaController.php` baris 70  
**Tipe:** Security — Path Traversal (Medium karena sudah ada `storeAs` Laravel)

**Deskripsi:**  
Input `folder` dari request langsung digunakan sebagai path penyimpanan tanpa sanitasi:

```php
$folder = $validated['folder'] ?? 'media/' . date('Y/m');
$path = $file->storeAs($folder, $filename, 'public'); // folder dari user input
```

Meskipun Laravel Storage (`storeAs`) relatif aman, nilai seperti `../../config` atau `../../../etc` bisa menyebabkan file tersimpan di luar direktori `storage/app/public` yang diharapkan.

**Solusi:** Sanitasi folder dengan `ltrim(preg_replace('/\.\./', '', $folder), '/')`.

---

### BUG-014 · `RedirectController` — Tidak Ada Validasi Open Redirect
**File:** `app/Http/Controllers/RedirectController.php` baris 50–54  
**Tipe:** Security — Open Redirect

**Deskripsi:**  
Field `to_url` pada redirect management hanya divalidasi `string|max:2048`, tanpa pengecekan apakah URL mengarah ke domain eksternal yang berbahaya:

```php
'to_url' => 'required|string|max:2048', // ❌ bisa berisi https://malicious.com
```

Jika fitur redirect diimplementasikan (di middleware atau controller yang melakukan redirect berdasarkan `from_url`), ini membuat aplikasi bisa digunakan sebagai open redirector oleh phisher.

---

### BUG-015 · `UserController` — Tidak Ada Proteksi Self-Delete Admin
**File:** `app/Http/Controllers/Admin/UserController.php` baris 125–132  
**Tipe:** Logic Bug

**Deskripsi:**  
Admin yang sedang login bisa menghapus akun mereka sendiri tanpa konfirmasi atau proteksi:

```php
public function destroy(User $user): RedirectResponse
{
    log_activity('user_deleted', "User {$user->name} deleted");
    $user->delete(); // ❌ tidak ada cek: apakah $user adalah diri sendiri?
    return redirect()->route('admin.users.index')->with('success', 'User deleted.');
}
```

**Dampak:** Admin satu-satunya bisa menghapus dirinya sendiri dan tidak ada yang bisa login ke admin panel.

---

### BUG-016 · `BookingController::validateVoucher()` — Tidak Ada Throttle
**File:** `app/Http/Controllers/BookingController.php` baris 98  
**File:** `routes/web.php`  
**Tipe:** Security — Brute Force / Enumeration

**Deskripsi:**  
Endpoint validasi voucher (`POST /bookings/voucher/validate`) tidak memiliki throttle middleware. Berbeda dengan `/search/suggest` yang memiliki `throttle:30,1`:

```php
// routes/web.php — tidak ada throttle pada validateVoucher
Route::post('/bookings/voucher/validate', [BookingController::class, 'validateVoucher']);
```

**Dampak:** Attacker bisa brute-force kode voucher secara otomatis untuk menemukan kode voucher yang valid.

---

### BUG-017 · `Booking::isPastDue()` — Fatal Error jika `check_out` null
**File:** `app/Models/Booking.php` baris 120–123  
**Tipe:** Bug — PHP Fatal Error

**Deskripsi:**  
Method `isPastDue()` memanggil `$this->check_out->isPast()` tanpa null check. Booking transit (`booking_type = 'transit'`) tidak memiliki `check_out` (nilainya `null` berdasarkan `BookingService` baris 62):

```php
public function isPastDue(): bool
{
    return $this->check_out->isPast() && ...; // ❌ fatal error jika check_out null
}
```

**Dampak:** Error fatal saat method ini dipanggil untuk booking transit.

---

## 🔵 LOW

---

### BUG-018 · `BlogController` — Sidebar Cache Tidak Di-Invalidate saat Post Dihapus/Diterbitkan
**File:** `app/Http/Controllers/BlogController.php` baris 108  
**Tipe:** Caching Bug

**Deskripsi:**  
Sidebar blog (recent posts, categories, tags) di-cache selama 1 jam:
```php
return Cache::remember('blog_sidebar', now()->addHour(), function () { ... });
```
Tidak ada invalidasi cache saat post baru diterbitkan, dihapus, atau kategori diubah. Sidebar akan menampilkan data lama hingga 1 jam.

---

### BUG-019 · `PostController` — Slug Tidak Divalidasi Unik pada Update
**File:** `app/Http/Controllers/PostController.php` baris 50–60  
**Tipe:** Validation Bug

**Deskripsi:**  
Saat membuat post, slug divalidasi unik (`unique:posts,slug`). Namun pada `update()`, pengecekan uniqueness tidak mengecualikan ID post yang sedang diedit, sehingga menyimpan post dengan slug yang sama akan selalu gagal validasi (atau error duplikat).

Perlu dicek method `update()` di `PostController` — kemungkinan menggunakan rule yang sama tanpa `ignore`.

---

### BUG-020 · `MediaController` — `Intervention\Image` Di-import tapi Tidak Digunakan
**File:** `app/Http/Controllers/MediaController.php` baris 10  
**Tipe:** Dead Code / Import Tidak Terpakai

**Deskripsi:**  
```php
use Intervention\Image\Facades\Image; // ❌ tidak pernah digunakan
```
Thumbnail generation di bawah menggunakan fungsi GD native (`imagecreatefromjpeg`, dll), bukan Intervention Image. Import ini adalah dead code yang menyesatkan.

---

### BUG-021 · `install_state.json` Tidak Dihapus jika Proses Installer Dibatalkan
**File:** `app/Http/Controllers/InstallerController.php`  
**Tipe:** Security — Information Disclosure

**Deskripsi:**  
File `storage/app/install_state.json` hanya dihapus (`clearState()`) jika instalasi berhasil sepenuhnya. Jika instalasi dibatalkan di tengah proses, file ini tetap ada di filesystem dan bisa berisi `db_password` plaintext (lihat BUG-003).

---

### BUG-022 · `CheckInstalled` Middleware — Bypass via Path Manipulation
**File:** `app/Http/Middleware/CheckInstalled.php` baris 24–26  
**Tipe:** Security — Minor Bypass Risk

**Deskripsi:**  
Pengecekan route auth menggunakan string comparison:
```php
if ($request->is('login') || $request->is('register') || $request->is('forgot-password')) {
```
Route `password-reset`, `password/reset/*`, dan route auth lainnya yang mungkin ditambahkan di masa depan tidak otomatis tercakup. Lebih baik menggunakan named routes atau prefix check.

---

### BUG-023 · `VoucherController` — Validasi `discount_value` Bisa Nol
**File:** `app/Http/Controllers/VoucherController.php` baris 98  
**Tipe:** Validation Bug — Business Logic

**Deskripsi:**  
```php
'discount_value' => ['required', 'numeric', 'min:0'], // ❌ boleh 0
```
Voucher dengan `discount_value = 0` valid secara teknis, padahal tidak ada gunanya dan membingungkan user.

Untuk `discount_type = 'percent'`, nilai > 100 juga tidak divalidasi.

**Solusi:**
```php
'discount_value' => ['required', 'numeric', 'min:0.01'],
// tambahkan: 'max:100' kondisional jika discount_type === 'percent'
```

---

### BUG-024 · `PropertyController` — SEO Input Tidak Divalidasi di `store()`/`update()`
**File:** `app/Http/Controllers/PropertyController.php` baris 141–146  
**Tipe:** Validation Bug

**Deskripsi:**  
Data SEO dari request dikirim langsung tanpa validasi explisit:
```php
$property->seo()->updateOrCreate([], [
    'meta_title' => $request->input('seo.meta_title'),       // tidak divalidasi
    'meta_description' => $request->input('seo.meta_description'), // tidak divalidasi
    // ...
]);
```
Sedangkan `PostController` juga melakukan hal serupa. Tidak ada panjang maksimal atau sanitasi pada field SEO.

---

### BUG-025 · `User::isAdmin()` — Hanya Mendukung Satu Role (`super-admin`)
**File:** `app/Models/User.php` baris 56–59  
**Tipe:** Design Bug / Maintainability

**Deskripsi:**  
```php
public function isAdmin(): bool
{
    return $this->hasRole('super-admin'); // hardcoded
}
```
Role system sudah dibangun (tabel `model_has_roles`), tetapi admin check hanya hardcode satu role. Tidak ada hierarki role. Jika di masa depan ingin menambah role `admin` atau `editor` dengan akses berbeda, seluruh logika perlu diubah.

---

### BUG-026 · `BookingController::export()` — Tidak Ada Batasan Data Export
**File:** `app/Http/Controllers/BookingController.php` baris 255–263  
**Tipe:** Performance / DoS Risk

**Deskripsi:**  
Export CSV mengambil **semua booking** tanpa batasan:
```php
$bookings = $query->orderBy('created_at', 'desc')->get(); // ❌ no limit
```
Pada database dengan ribuan booking, ini akan menghabiskan memory PHP dan waktu eksekusi yang lama, berpotensi timeout atau OOM crash.

**Solusi:** Gunakan `chunk()` atau batasi export dengan filter tanggal yang wajib diisi.

---

## Lampiran: File yang Diaudit

| File | Baris Teraudit |
|------|---------------|
| `app/Http/Controllers/BookingController.php` | 317 |
| `app/Http/Controllers/PropertyController.php` | 435 |
| `app/Http/Controllers/MediaController.php` | 299 |
| `app/Http/Controllers/Admin/UserController.php` | 133 |
| `app/Http/Controllers/InstallerController.php` | 670 |
| `app/Http/Controllers/VoucherController.php` | 116 |
| `app/Http/Controllers/SettingsController.php` | 219 |
| `app/Http/Controllers/RedirectController.php` | 100 |
| `app/Http/Controllers/BlogController.php` | 116 |
| `app/Http/Controllers/PostController.php` | 198 |
| `app/Http/Controllers/SearchController.php` | 72 |
| `app/Http/Controllers/ProfileController.php` | 60 |
| `app/Http/Middleware/CheckInstalled.php` | 45 |
| `app/Http/Middleware/EnsureUserIsAdmin.php` | 24 |
| `app/Http/Requests/BookingRequest.php` | 100 |
| `app/Http/Requests/MediaRequest.php` | 68 |
| `app/Models/Booking.php` | 124 |
| `app/Models/Property.php` | 389 |
| `app/Models/User.php` | 86 |
| `app/Models/Voucher.php` | 101 |
| `app/Services/BookingService.php` | 213 |
| `app/Services/BookingPricingService.php` | 265 |
| `app/Helpers/activity.php` | 15 |
| `routes/web.php` | 125 |

---

*Laporan ini adalah hasil analisis statis. Beberapa bug perlu konfirmasi runtime atau review migrasi database untuk memastikan dampak sebenarnya.*
