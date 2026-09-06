# Artivo CMS — Frontend UI/UX Refactor Plan

> **Status**: Planning-only. Tidak ada perubahan kode di dokumen ini.
> **Audience**: Coding agent yang akan mengeksekusi refactor ini fase per fase.
> **Dibuat**: September 2025
> **Stack**: Laravel 13.8 · Blade · Alpine.js 3 · Hotwired Turbo · Tailwind CSS v3 · Vite 8

---

## AGENT RULES — Baca Sebelum Eksekusi

Aturan ini mengikat seluruh pelaksanaan dokumen ini dan **lebih prioritas** dari pertimbangan agent sendiri:

1. **Jangan ubah business logic.** Pricing, booking calculation, voucher, status transitions — tidak boleh disentuh tanpa persetujuan eksplisit user. Ini mencakup perubahan pada controller, service, route, dan model — kecuali penambahan Blade component baru.

2. **Jangan otomatis konversi tabel menjadi card view.** Table-to-card hanya dilakukan pada tabel yang secara eksplisit ditandai di dokumen ini. Default untuk semua tabel: bungkus dengan `overflow-x-auto`. Tidak ada card rewrite tanpa instruksi eksplisit.

3. **Jangan tambah CSS custom properties baru** selain yang tercantum di Design System Contract (§8). Tailwind utility class adalah cara utama. Custom properties hanya untuk dynamic values dari SettingsService yang harus diinjeksikan via PHP.

4. **Jalankan QA checklist setiap phase sebelum melanjutkan ke phase berikutnya.** Checklist ada di akhir setiap phase.

5. **Item bertanda `[NEEDS APPROVAL]`**: berhenti, lapor ke user, tunggu konfirmasi. Jangan diimplementasi dalam dokumen ini.

6. **Pertahankan semua hook aksesibilitas yang ada** — `data-warn-unsaved`, `aria-*`, `role`, `x-cloak`, focus ring — jangan hapus saat refactor komponen.

---

## 1. Executive Summary

Artivo CMS adalah platform sewa apartemen dengan CMS, blog, dan booking system. Frontend publik sudah memiliki struktur matang (header sticky, filter drawer, search overlay, slider properti), namun **belum kohesif sebagai design system**. Admin panel lebih bermasalah: halaman menggunakan pola generik yang tidak konsisten, flash notification dirender dua kali, dan tidak ada breadcrumb.

**Tiga masalah terbesar:**
1. **Admin tidak punya design system** — radius, spacing, button style, dan table pattern berbeda-beda per halaman.
2. **Flash notification duplikat** — HTML block `session('success')` + Alpine `toastManager` aktif bersamaan.
3. **Anti-UI-Slop violations** — radial dot pattern di hero, WhatsApp wobble infinite, badge stacking di foto properti, merah semantik salah pada filter trigger mobile.

Refactor ini **tidak mengganti stack** dan **tidak mengubah business logic**. Tujuannya: menetapkan design system contract, membangun komponen admin yang reusable, dan menghapus pola visual yang tidak berfungsi.

---

## 2. Current Frontend Assessment

### 2.1 Public Frontend

| Area | Kondisi |
|---|---|
| Layout system | `layouts/frontend.blade.php` — mature, sticky header, drawer mobile, footer |
| Typography | Figtree via bunny.net — baik, tapi skala ukuran tidak konsisten antar halaman |
| Color system | Dynamic via `SettingsService` — terlalu banyak inline `style=` |
| Component reuse | `text-input`, `money-input`, `search-input`, `modal`, `dropdown` tersedia |
| Dark mode | Alpine + localStorage — functional, perlu audit contrast |
| Icons | **Font Awesome 6 + Lucide + SVG inline** — tiga pola berbeda |
| Animations | WhatsApp pulse+wobble infinite, card hover scale+shadow — berlebihan |
| Hero section | Gradient + radial dot pattern dekoratif |
| Property card | Shadow ganda + badge stacking hingga 5 badge di overlay foto |
| Filter | Desktop sidebar + mobile bottom sheet — pattern benar tapi kode duplikat |
| Search | Fullscreen overlay Alpine — baik |
| Pagination | Default Laravel — tidak dikustomisasi |

### 2.2 Admin Panel

| Area | Kondisi |
|---|---|
| Flash messages | **CRITICAL**: dirender dua kali (HTML block + Alpine toast) |
| Sidebar | `bg-gray-800`, group labels hilang saat collapsed, tidak ada tooltip, tidak ada active border |
| Tables | Vanilla `<table>` per halaman, tidak ada shared component |
| Buttons | Mix `rounded-md`/`rounded-lg` — tidak konsisten |
| Filter bars | Tiap halaman implementasi sendiri |
| Breadcrumbs | Tidak ada |
| Empty states | Inconsistent — beberapa ada, beberapa tidak |
| WYSIWYG | Quill 2 — tidak ada dark mode support |
| Dashboard | 5 section terpisah, section order tidak optimal |
| Settings | 12 group via `?group=` query param — tidak ada visual tab |

---

## 3. UX Problems

### P0 — Menghambat workflow

**UX-01**: Flash message muncul dua kali di admin.
- Blade `session('success')` HTML block + Alpine `toastManager` keduanya aktif
- User melihat pesan duplikat setiap save

**UX-02**: Sidebar icon-only mode tidak navigable.
- Group labels hilang saat `sidebarCollapsed`
- Tidak ada tooltip saat hover icon

**UX-03**: Destructive action hanya pakai `confirm()` native.
- Tidak branded, tidak menyebut nama item yang dihapus, tidak accessible

**UX-04**: Unsaved-changes guard tidak konsisten.
- `data-warn-unsaved` hanya ada di beberapa form

**UX-05**: Settings navigasi group tidak obvious.
- 12 group via `?group=` — tidak ada tab visual

### P1 — Terasa dalam pemakaian sehari-hari

**UX-06** `[NEEDS APPROVAL]`: Booking price preview sebelum submit — menyentuh pricing logic, tunda.

**UX-07**: Admin table tidak menampilkan total record.

**UX-08**: Empty state tidak actionable — tidak ada CTA.

**UX-09**: Filter trigger mobile `bg-red-600` — semantik salah. Merah = destruktif.

### P2 — Consistency dan polish

**UX-11**: Back navigation tidak konsisten di admin.

**UX-12**: Pagination frontend menggunakan default Laravel style.

**UX-14**: `localStorage` key berbeda: admin (`admin.theme`) vs frontend (`theme`) — state terpisah.

---

## 4. UI / Visual Problems

### P0 — Critical

**UI-01**: Flash notification duplikat (lihat UX-01).

**UI-02**: Admin button radius tidak konsisten — `rounded-md` vs `rounded-lg` tercampur.

### P1 — Terlihat jelas

**UI-03**: Icon library split — tiga pola (Font Awesome, Lucide, SVG inline).

**UI-04**: Table header `bg-gray-50` di atas halaman `bg-gray-100` — depth tidak tepat.

**UI-05**: Sidebar active state: `bg-gray-700` vs hover `hover:bg-gray-700` hampir identik, tidak ada left-border.

**UI-06**: Property card shadow ganda di resting state + tambah lagi saat hover.

**UI-07**: Badge stacking di overlay foto — hingga 5 badge, foto tidak terlihat.

**UI-08**: Quill editor tidak ada dark mode support.

### P2 — Consistency

**UI-09**: Radius tidak konsisten antara public (`rounded-2xl`) dan admin (`rounded-md`/`rounded-lg`).

**UI-10**: Spacing antara page title dan content admin tidak konsisten.

**UI-11**: Status badge diimplementasi berbeda-beda di tiap halaman.

---

## 5. Anti-UI-Slop Findings

### Slop-01: Radial dot pattern di hero — dekoratif murni
```html
<div class="absolute inset-0 opacity-10"
     style="background-image: radial-gradient(circle at 1px 1px, #fff 1px, transparent 0);
            background-size: 24px 24px;" aria-hidden="true">
```
**Fix**: Hapus. Linear gradient sudah cukup.

### Slop-02: WhatsApp — dua animasi infinite tanpa accessibility guard
```css
animation: whatsapp-pulse 2s ease-out infinite, whatsapp-wobble 5s ease-in-out infinite;
```
**Fix**: Hapus `whatsapp-wobble` sepenuhnya. Guard `whatsapp-pulse`:
```css
@media (prefers-reduced-motion: reduce) { .whatsapp-pulse { animation: none; } }
```

### Slop-03: Dashboard quick action — card-in-card-in-card
Border + icon container + pill badge "Buka →" semua warna sama di satu card.
**Fix**: Hapus pill badge. Sederhanakan ke icon + title + description.

### Slop-04: Dashboard — 15+ card sebelum informasi actionable
Quick Actions muncul sebelum situational metrics.
**Fix**: Reorder — Today Strip dulu, baru KPI, baru charts.

### Slop-05: Hero gradient dua warna bebas
Admin bisa pilih `primary_color` dan `secondary_color` yang tidak harmonis.
**Fix**: Gunakan `primary_color` → derived darker shade. Hapus ketergantungan `secondary_color` untuk gradient hero saja (bukan untuk seluruh site).

### Slop-06: Amenity badges di overlay foto
Hingga 5 badge di satu foto. Foto tidak terlihat.
**Fix**: Overlay foto hanya featured badge. Type badge pojok kanan atas. Amenity badges ke card body.

### Slop-07: Filter mobile trigger `bg-red-600` — semantik salah
**Fix**: Gunakan primary color dari SettingsService.

### Slop-08: Scroll-to-top inline script duplikat di dua layout
**Fix**: Pindahkan ke `app.js` dengan hook `[data-scroll-top]`.

---

## 6. Information Architecture

### 6.1 Admin Sidebar — Current vs Recommended

**Current** (Users mengambang, PromoRate tidak ada):
```
Dashboard
[Content]  Pages · Blocks · Media · Navigation · Properties · Amenities
[Blog]     Posts · Categories · Tags
[Booking]  Bookings · Voucher
Users
[System]   Bahasa · Kurs · Slug & Path · Redirects · Backup · Settings
```

**Recommended**:
```
Dashboard
[Properti]          Properties · Amenities
[Konten]            Pages · Blocks · Media · Navigation
[Blog]              Posts · Categories · Tags
[Booking & Revenue] Bookings · Vouchers · Promo Rates
[Admin]             Users
[System]            Languages · Currency · Slug Settings · Redirects · Backup · Settings
```

> Agent: Verifikasi di `routes/web.php` apakah PromoRate memiliki route admin. Tambahkan ke sidebar hanya jika route ada.

### 6.2 Settings Tab Navigation

12 group saat ini hanya via `?group=` URL — tidak ada visual tab.

**Cluster yang direkomendasikan**:
```
Branding:  general, theme
Halaman:   homepage, footer
SEO:       seo
Kontak:    integrations, mail
Bisnis:    pricing
Keamanan:  captcha
Integrasi: currency_api, git
Email:     email_templates
```

Implementasi: horizontal scrollable tab bar di atas form.

### 6.3 Dashboard — Information Priority

**Current (problematic)**: Quick Actions → Today → KPI → Revenue → Charts → Calendar → Tables

**Recommended**: Today Strip → KPI + Revenue (merged) → Charts → Recent items → Quick Actions

### 6.4 Breadcrumb — Tidak Ada

**Format**: `Properties › Edit: Grand Kamala Lagoon`

---

## 7. Design Direction

**Satu direction: "Professional Tool — Quiet Confidence"**

CMS dipakai berjam-jam per hari. UI harus efisien, tidak melelahkan, warna hanya untuk fungsi.

| Principle | Implementasi |
|---|---|
| **Dekoratif = hapus** | Tidak ada radial pattern, tidak ada gradient purely decorative |
| **Warna = makna** | Primary untuk CTA, amber untuk warning, red untuk danger saja, green untuk published/success |
| **Radius = konsisten** | Satu rule per context — lihat Design System Contract §8.1 |
| **Shadow = hierarchy** | Satu level per layer. Tidak ada hover shadow-on-shadow |
| **Typography = task** | Clarity di atas impression |
| **Motion = purposeful** | Transition state saja. Tidak ada looping animation tanpa reduced-motion guard |
| **Icons = satu library per area** | Admin: Font Awesome 6 saja. Frontend: Lucide saja. Tidak ada SVG inline kecuali brand icon |

### Admin Theme
- Body `bg-gray-100`, cards `bg-white`, table header `bg-gray-50`
- Sidebar: `bg-gray-900`, active item: `border-l-2 border-blue-500 bg-gray-700 text-white`
- Primary button: `bg-blue-600 rounded-lg`
- Destructive: `bg-red-600` — **hanya** untuk irreversible delete

### Public Theme
- Hero: gradient `primary_color` → derived darker shade (bukan secondary bebas)
- Cards: border `border-gray-200`, hover `border-primary-400` — tanpa box-shadow ganda
- Public CTA buttons: `rounded-full`; Form actions: `rounded-lg`

---

## 8. Design System Contract

**Semua perubahan visual harus konsisten dengan kontrak ini. Kontrak ini adalah sumber kebenaran untuk setiap keputusan styling.**

### 8.1 Radius Contract

| Context | Class | Digunakan untuk |
|---|---|---|
| Form inputs & selects | `rounded-lg` | `<input>`, `<select>`, `<textarea>` |
| Cards & modal containers | `rounded-xl` | Admin cards, modal, panels |
| Buttons (admin & form) | `rounded-lg` | Semua button di admin dan public form |
| Public CTA & nav buttons | `rounded-full` | WhatsApp button, "Cari Apartemen", filter active chips |
| Status pills & badges | `rounded-full` | Status badges |
| Admin sidebar nav items | `rounded-none` | Sidebar `<a>` links |

> Jangan gunakan `rounded-2xl` di admin. Jangan gunakan `rounded-md` baru setelah refactor ini selesai.

### 8.2 Shadow Contract

| Level | Tailwind class | Digunakan untuk |
|---|---|---|
| Resting card | `shadow-sm` | Cards, filter sidebar |
| Hover/lifted | `shadow-md` | Card hover — **menggantikan** resting, bukan menambah |
| Dropdown & popover | `shadow-lg` | Dropdown menus |
| Modal overlay | `shadow-xl` | Full modal dialog |

> Hover hanya boleh naik satu level dari resting. Tidak ada hover shadow aktif bersamaan dengan resting shadow.

### 8.3 CSS Custom Properties — Tidak Boleh Ditambah

**Tidak ada CSS custom properties baru** yang ditambahkan selama refactor ini. Tailwind utility class sudah cukup untuk semua kebutuhan styling statis. Dynamic values dari SettingsService tetap menggunakan inline `style=` yang sudah ada — jangan refactor ke custom properties.

> Alasan: `--color-*` properties tidak memberikan keuntungan nyata jika nilai sama persis dengan Tailwind defaults. Menambahnya hanya menciptakan dua sumber kebenaran.

### 8.4 Spacing Contract

| Kegunaan | Class | Keterangan |
|---|---|---|
| Page padding | `px-4 sm:px-6 lg:px-8` | Sudah ada di layout — pertahankan |
| Section gap | `space-y-6` | Antara card sections di dashboard |
| Card padding | `p-5 sm:p-6` | Standard admin card padding |
| Form field gap | `space-y-5` | Antara field dalam form |
| Table cell | `px-4 py-3` | Standard admin table cells |

### 8.5 Typography Contract

| Role | Classes |
|---|---|
| Page title | `text-2xl font-bold text-gray-900 dark:text-white` |
| Section title | `text-base font-semibold text-gray-800 dark:text-gray-100` |
| Form label | `text-sm font-medium text-gray-700 dark:text-gray-300` |
| Body | `text-sm text-gray-600 dark:text-gray-400` |
| Caption | `text-xs text-gray-500 dark:text-gray-400` |
| Badge | `text-xs font-semibold` |

### 8.6 Color Semantic Contract

| Warna | Semantic | Contoh penggunaan |
|---|---|---|
| `blue-600` | Primary action | CTA button, focus ring |
| `green-600` / `green-100` | Success / Published | Toast success, badge published |
| `amber-500` / `amber-100` | Warning / Pending | Toast warning, badge pending |
| `red-600` / `red-100` | Danger / Error | Delete button, badge cancelled |
| `gray-*` | Neutral / Draft | Badge draft, disabled state |
| `indigo-600` / `indigo-100` | Informational | Toast info, badge completed |

> Merah **hanya** untuk destruktif/error. Jangan gunakan untuk non-destructive actions.

---

## 9. Component Audit

### 9.1 Existing Components

| Component | Status | Catatan |
|---|---|---|
| `text-input` | ✅ Reuse | Baik |
| `money-input` | ✅ Reuse | Functional, format Rp |
| `password-input` | ✅ Reuse | Eye toggle |
| `search-input` | ✅ Reuse | Alpine autocomplete |
| `primary-button` | ⚠️ Extend | Hanya di auth context, tidak dipakai di admin |
| `secondary-button` | ⚠️ Extend | Sama |
| `danger-button` | ⚠️ Extend | Sama |
| `input-label` | ✅ Reuse | |
| `input-error` | ✅ Reuse | |
| `modal` | ✅ Reuse | Alpine-based, accessible |
| `dropdown` | ✅ Reuse | |
| `seo` | ✅ Reuse | |
| `captcha` | ✅ Reuse | |
| `share-modal` | ✅ Reuse | |

### 9.2 Missing Components

| Component | Dibutuhkan di | Mengapa perlu shared |
|---|---|---|
| `admin-page-header` | Semua admin index | h2 + description + create button diulang per halaman |
| `admin-status-badge` | Posts, Properties, Bookings, Dashboard | 3+ implementasi berbeda |
| `admin-filter-bar` | Posts, Properties, Bookings index | Filter form diimplementasi ulang per halaman |
| `admin-breadcrumb` | Semua admin create/edit/show | Tidak ada sama sekali |
| `admin-empty-state` | Semua admin index | Inconsistent — sebagian ada, sebagian tidak |
| `confirm-modal` | Semua delete actions | Mengganti `confirm()` native |
| `vendor/pagination/tailwind.blade.php` | Semua paginated views | Default pagination tidak styled |

### 9.3 Duplicate Patterns yang Harus Dihilangkan

| Pattern | Lokasi |
|---|---|
| Flash HTML block | `admin.blade.php` — hapus, biarkan hanya toast |
| Filter form | properties/index, posts/index, bookings/index — berbeda-beda |
| Status badge | dashboard, posts/index, bookings/index |
| Delete `confirm()` | Tiap tabel |
| Scroll-to-top inline script | `admin.blade.php` + `frontend.blade.php` |

---

## 10. Component Refactor Plan

### 10.1 `x-admin-page-header`
```
Props: title (req), description (opt), create-route (opt), create-label (opt, default "Tambah Baru")
Variants: dengan dan tanpa create button
```

### 10.2 `x-admin-status-badge`
```
Props: status (req) — 'draft'|'published'|'pending'|'confirmed'|'cancelled'|'completed'
       label (opt) — override text
Implementation: PHP match expression → Tailwind classes
Non-color differentiator: icon prefix "●" active, "○" inactive
```

### 10.3 `x-admin-filter-bar`
```
Props: action (req), method (opt, default GET), reset-route (opt)
Slot: filter fields (input, select, dll)
```

### 10.4 `x-admin-breadcrumb`
```
Props: items (array [['label', 'route'|null]]), current (string)
Rendering: "Parent › Child › Current"
```

### 10.5 `x-admin-empty-state`
```
Props: title (req — harus spesifik), description (opt), action-route (opt), action-label (opt), icon (opt)
```

### 10.6 `x-confirm-modal`
```
Props: id (req), title (req), message (opt), confirm-label (opt, default "Hapus"),
       cancel-label (opt, default "Batal"), confirm-form-id (req)
Behavior: role="alertdialog", auto-focus Cancel, Escape close
```

### 10.7 Fix Flash Notification Duplication

**Hapus** dari `layouts/admin.blade.php` (3 block berikut):
```blade
@if(session('success')) <div class="bg-green-100 ..."> @endif
@if(session('error'))   <div class="bg-red-100 ...">   @endif
@if(session('info'))    <div class="bg-blue-100 ...">  @endif
```

**Pertahankan** hanya Alpine `toastManager` — sudah auto-show dari session.

---

## 11. Page-by-Page Refactor

### 11.1 Admin: Dashboard

| Item | Action | Priority |
|---|---|---|
| Section order | Reorder: Today Strip → KPI → Charts → Tables → Quick Actions | P1 |
| Quick action cards | Hapus pill "Buka →", sederhanakan ke icon + title + desc | P2 |
| KPI + Revenue | Merge ke 1 section visual | P1 |
| Chart loading | Tambahkan skeleton bars sebelum Chart.js ready | P2 |
| Holiday calendar embedded | Pertimbangkan modal only — sudah ada tombol di header | P3 |

### 11.2 Admin: Posts Index

| Item | Action | Priority |
|---|---|---|
| Page header | Ganti → `x-admin-page-header` | P2 |
| Filter bar | Ganti → `x-admin-filter-bar` | P2 |
| Status badge | Ganti → `x-admin-status-badge` | P2 |
| Delete confirm | Ganti `confirm()` → `x-confirm-modal` | P1 |
| Empty state | Tambahkan → `x-admin-empty-state` dengan CTA "Buat Post Pertama" | P2 |
| Record count | Tambahkan "Menampilkan X dari Y" di atas tabel | P2 |

### 11.3 Admin: Posts Create/Edit

| Item | Action | Priority |
|---|---|---|
| Back link | Ganti → `x-admin-breadcrumb` | P2 |
| Quill editor | Tambahkan dark mode CSS overrides | P1 |
| Unsaved guard | ✅ Pertahankan `data-warn-unsaved` |  |
| Featured image dropzone | ✅ Pertahankan |  |
| "Publish vs Draft" buttons | `[NEEDS APPROVAL]` — menyentuh publishing flow | — |

### 11.4 Admin: Properties Index

| Item | Action | Priority |
|---|---|---|
| Bulk action bar | ✅ Pertahankan, polish styling | P3 |
| Filter bar | Ganti → `x-admin-filter-bar` | P2 |
| Page header | Ganti → `x-admin-page-header` | P2 |
| Status badge | Ganti → `x-admin-status-badge` | P2 |
| Delete confirm | Ganti `confirm()` → `x-confirm-modal` | P1 |

### 11.5 Admin: Properties Create/Edit

| Item | Action | Priority |
|---|---|---|
| Photo gallery Alpine | ✅ Pertahankan — functional |  |
| Form section headers | Tambahkan anchor jump links + `<h3>` separators | P2 |
| Required field markers | Tambahkan `*` secara konsisten | P1 |
| Breadcrumb | Tambahkan → `x-admin-breadcrumb` | P2 |
| Nearby POI tab | ✅ Pertahankan — functional | P3 |

### 11.6 Admin: Bookings Index

| Item | Action | Priority |
|---|---|---|
| Status badges | Ganti → `x-admin-status-badge` | P2 |
| Filter | Standardize → `x-admin-filter-bar` | P2 |
| Booking detail | Audit terpisah sebelum disentuh — jangan sentuh booking logic | P2 |

### 11.7 Admin: Settings Index

| Item | Action | Priority |
|---|---|---|
| Tab navigation | Tambahkan horizontal scrollable tab bar | P1 |
| Section `<h3>` styling | Standarisasi di semua 12 partials | P2 |
| Save buttons | ✅ Pertahankan posisi |  |
| CTA fields | Verifikasi `_homepage.blade.php` punya `cta_title`, `cta_text`, `cta_button_label`, `cta_button_url` | P1 |

### 11.8 Public: Home

| Item | Action | Priority |
|---|---|---|
| Hero radial dot overlay | Hapus | P0 |
| Hero gradient | `primary_color` → derived darker shade | P2 |
| WhatsApp wobble | Hapus, guard pulse dengan `prefers-reduced-motion` | P0 |
| Property card badges | Pindahkan amenity badges ke card body | P1 |
| Blog card hover shadow | Turunkan `hover:shadow-xl` → `hover:shadow-md` | P2 |
| CTA admin sync | Verifikasi settings fields ada dan berfungsi dari admin | P1 |

> **CTA Block**: Refactor visual tidak boleh mengubah cara field dikonfigurasi dari admin. `cta_title`, `cta_text`, `cta_button_label`, `cta_button_url`, `whatsapp_default` tetap dikontrol dari Settings › Homepage.

### 11.9 Public: Properties Listing

| Item | Action | Priority |
|---|---|---|
| Filter mobile trigger | `bg-red-600` → primary color | P0 |
| Property card | Sama seperti §11.8 | P1 |
| Pagination | Custom Tailwind view | P2 |
| Active filter chips | ✅ Pertahankan |  |
| Sort dropdown duplikat | Extract → `_sort-dropdown.blade.php` | P2 |

### 11.10 Public: Property Detail

Halaman ini (`properties/show.blade.php`) belum diaudit penuh.

| Item | Action | Priority |
|---|---|---|
| Gallery lightbox | ✅ Swipe gesture sudah ada | P3 |
| Booking form | **Audit di Phase 0 sebelum disentuh** | P1 — audit first |
| Nearby POI map | ✅ Pertahankan |  |
| Share modal | ✅ Pertahankan |  |

> **Jangan sentuh booking form** tanpa audit dan konfirmasi user — ini berkaitan dengan booking flow yang merupakan business logic.

---

## 12. CMS Workflow Improvements

### 12.1 Publishing Workflow `[NEEDS APPROVAL]`

"Publish vs Save Draft" sebagai dua distinct button **membutuhkan perubahan controller dan form action**. Ini bukan perubahan visual murni. **Tunda ke discussion terpisah dengan user.**

### 12.2 Property Form Navigation (Aman)

Tidak mengubah route atau logic:
- Tambahkan jump links di top of form: "Umum · Foto · Harga · Kebijakan · Lokasi"
- Tambahkan `<h3>` section separators yang jelas
- Tambahkan required field markers (`*`) secara konsisten

### 12.3 Media Library Visual Polish (Aman)

Tidak mengubah `photoGallery` Alpine logic:
- Upload progress bar lebih visible
- Thumbnail grid ukuran konsisten
- Featured photo indicator: border accent, bukan hanya star icon

### 12.4 Bulk Action Consistency

Properties index sudah punya `bulkSelect()`. Cek posts dan bookings index — jika perlu, gunakan pola yang sama. Jangan buat pola baru.

---

## 13. Responsive Strategy

### Breakpoint Behavior

| Component | Desktop (≥1024) | Tablet (768–1023) | Mobile (<768) |
|---|---|---|---|
| Admin sidebar | Persistent, collapsible | Hidden → drawer | Hidden → drawer |
| Admin tables | Full table | `overflow-x-auto` | `overflow-x-auto` |
| Filter (admin) | Inline di atas tabel | Inline, wrap | Collapsible |
| Filter (public) | Sidebar kiri | Sidebar kiri | Bottom sheet |
| Property slider | 3 cards | 2 cards | 1 card |
| Property grid | 3-col | 2-col | 1-col |

### Table Policy

**Default untuk semua admin tabel**: tambahkan `<div class="overflow-x-auto">` jika belum ada. **Tidak ada konversi ke card view** kecuali tabel dengan ≥6 kolom yang secara eksplisit disetujui user.

### Overflow Fixes

| Area | Fix |
|---|---|
| Admin tables tanpa wrapper | Tambahkan `<div class="overflow-x-auto">` |
| Admin filter forms di sm | Pastikan `flex-wrap` atau `grid` responsive |
| Quill editor | `min-width: 0` pada `.wysiwyg-container` |

---

## 14. Accessibility Strategy

### Yang Sudah Ada — Pertahankan
- ✅ Skip to content link
- ✅ `aria-label` pada hamburger, search, drawer
- ✅ `aria-expanded` pada dropdown, sidebar
- ✅ `aria-live="polite"` pada toast container
- ✅ Focus rings via `@tailwindcss/forms`
- ✅ `role="dialog" aria-modal="true"` pada mobile drawer
- ✅ Keyboard Escape untuk close overlay

### Issues yang Perlu Diperbaiki

**A11Y-01**: WhatsApp animation tanpa `prefers-reduced-motion`.
```css
@media (prefers-reduced-motion: reduce) { .whatsapp-pulse { animation: none; } }
```

**A11Y-02**: `confirm()` native tidak accessible. Fix: `x-confirm-modal` dengan `role="alertdialog"`.

**A11Y-03**: Slider card links di dalam container dengan `@pointerdown`. Test manual di Phase 0.

**A11Y-04**: Status badge hanya warna sebagai differentiator. Fix: icon prefix "●"/"○".

**A11Y-05**: Slider dots `w-2.5 h-2.5` (~10px) — terlalu kecil. Fix: wrapper `min-w-[44px] min-h-[44px]`.

**A11Y-06**: Property photo `alt` hanya nama properti. Fix: tambahkan nomor foto atau gunakan media `alt` field.

**A11Y-07**: Contrast issues — `text-gray-400` di atas white (2.8:1, FAIL normal text). Cek di Phase 7.

**A11Y-08**: `x-input-error` tidak terhubung ke input via `aria-describedby`. Fix di Phase 7.

---

## 15. Interaction & Feedback States

### Button States
```
Default   → bg-blue-600 text-white
Hover     → bg-blue-700
Focus     → ring-2 ring-blue-500 ring-offset-2
Active    → scale-[0.98]
Disabled  → opacity-50 cursor-not-allowed
Loading   → spinner kiri + "Menyimpan..." + disabled
```

### Form Input States
```
Default  → border-gray-300
Focus    → border-blue-500 ring-2 ring-blue-200
Error    → border-red-500 ring-2 ring-red-200 + error text
Disabled → bg-gray-50 opacity-60 cursor-not-allowed
```

### Toast (perbaikan minor)
Ganti emoji icons (`✅ ❌ ⚠️ ℹ️`) dengan SVG icons yang konsisten. Pertahankan posisi, timing, dan transisi.

### Loading States
| Area | Implementation |
|---|---|
| Form submit | `opacity-50 pointer-events-none` + spinner di button |
| Dashboard charts | Skeleton bars (gray rectangles) sebelum Chart.js load |
| Turbo navigation | `turbo-progress-bar` sudah ada ✅ |

---

## 16. Priority Matrix

### P0 — Fix Sekarang (tidak ada risiko regresi)

| ID | Item | Effort |
|---|---|---|
| UX-01 | Flash message duplikat | XS |
| Slop-02 | WhatsApp animation + reduced-motion | XS |
| Slop-07 | Filter mobile `bg-red-600` | XS |

### P1 — High Impact

| ID | Item | Effort |
|---|---|---|
| Slop-01 | Hapus radial dot overlay | XS |
| Slop-06 | Badge stacking di property card | S |
| UX-02 | Sidebar tooltip di icon-only mode | S |
| UX-03 | Confirm modal replace `confirm()` | M |
| UX-05 | Settings tab navigation | M |
| UI-02 | Standardize admin button `rounded-lg` | M |
| UI-05 | Sidebar active state left-border | XS |
| UI-08 | Quill dark mode | S |
| A11Y-05 | Slider dots touch target | XS |
| 11.7 | Verifikasi CTA fields di Settings | S |

### P2 — Penting

| ID | Item | Effort |
|---|---|---|
| Components | 5 admin components | M each |
| UI-09 | Radius consistency | S |
| UX-07 | Record count di tabel | S |
| 11.1 | Dashboard reorder + KPI consolidation | M |
| Pagination | Custom Tailwind view | S |
| A11Y-04 | Status badge icon prefix | S |
| Toast | Emoji → SVG icons | XS |

### P3 — Nice to Have

| ID | Item | Effort | Note |
|---|---|---|---|
| Slop-04 | Dashboard widget consolidation | M | |
| Slop-05 | Hero gradient constraint | S | |
| UX-06 | Booking price preview | L | **NEEDS APPROVAL** |
| UX-11 | Publishing workflow buttons | L | **NEEDS APPROVAL** |
| Holiday calendar | Pindah ke modal only | S | |

---

## 17. Implementation Roadmap

### Phase 0: Verify & Confirm (½ hari)
> Verifikasi asumsi audit sebelum menulis satu baris kode.

**Tasks**:
1. Baca `routes/web.php` — konfirmasi PromoRate route ada/tidak
2. Baca `_homepage.blade.php` — konfirmasi `cta_title`, `cta_text`, `cta_button_label`, `cta_button_url` ada; catat jika perlu ditambah
3. Baca `properties/show.blade.php` — audit booking form section, **catat tanpa mengubah**
4. Baca semua 12 settings partials — verifikasi `?group=` param names untuk tab navigation
5. Test manual A11Y-03: apakah Enter key pada property card di slider berfungsi
6. Baca `bookings/index.blade.php` — apakah bulk action ada

**Output Phase 0**: Laporan temuan baru (jika ada). Stop jika ada blocker.

**QA Phase 0**:
- [ ] PromoRate: ada/tidak di routes — sudah dikonfirmasi
- [ ] CTA fields: semua 4 field ada atau perlu ditambah — sudah dicatat
- [ ] Booking form: dipahami strukturnya, tidak disentuh
- [ ] 12 settings group names: terdokumentasi
- [ ] A11Y-03 slider Enter key: hasil test dicatat

---

### Phase 1: Foundation Fixes (1–2 hari)
> Hapus yang buruk. Zero regression risk.

**Tasks**:
1. Hapus 3 HTML flash blocks dari `layouts/admin.blade.php`
2. Hapus `@keyframes whatsapp-wobble` dan `animation: ..whatsapp-wobble..` dari `app.css`
3. Tambahkan `@media (prefers-reduced-motion: reduce) { .whatsapp-pulse { animation: none; } }`
4. Ganti `bg-red-600` filter mobile trigger di `properties/index.blade.php` → `style="background-color: {{ $primaryColor }}"`
5. Hapus radial dot `<div>` dari `home.blade.php` dan `properties/index.blade.php`
6. Tambahkan `border-l-2 border-blue-500` pada active sidebar items di `layouts/admin.blade.php`
7. Pindahkan scroll-to-top JS dari 2 inline scripts ke `app.js` dengan `[data-scroll-top]` hook

**QA Phase 1**:
- [ ] Flash message muncul satu kali saja setelah save (toast only, bukan HTML block + toast)
- [ ] WhatsApp button: tidak ada wobble animation
- [ ] WhatsApp button: pulse tidak berjalan jika `prefers-reduced-motion: reduce` aktif
- [ ] Filter mobile button: warna biru (primary), bukan merah
- [ ] Home page: tidak ada radial dot overlay di hero
- [ ] Properties listing: tidak ada radial dot overlay
- [ ] Sidebar: active item memiliki left border biru
- [ ] Scroll-to-top berfungsi di admin dan frontend
- [ ] Tidak ada regresi pada fitur yang ada

---

### Phase 2: Design System Foundation (1 hari)
> Stabilkan CSS sebelum membangun komponen.

**Tasks**:
1. Update `.property-card` di `app.css` — hapus shadow ganda, gunakan satu level
2. Update `.property-card:hover` — naik satu level saja dari resting
3. Tambahkan Quill dark mode CSS ke `app.css`:
   ```css
   .dark .ql-toolbar { background: #374151; border-color: #4b5563; }
   .dark .ql-toolbar .ql-stroke { stroke: #d1d5db; }
   .dark .ql-toolbar .ql-fill { fill: #d1d5db; }
   .dark .ql-editor { background: #1f2937; color: #f9fafb; }
   .dark .ql-container { border-color: #4b5563; }
   ```
4. Tambahkan `min-width: 0` pada `.wysiwyg-container`
5. Verifikasi `turbo-progress-bar` gradient — sudah `#3b82f6`, tidak perlu ubah

**Yang TIDAK dilakukan di Phase 2**:
- ❌ Tidak menambah CSS custom properties baru (sesuai §8.3)
- ❌ Tidak mengubah `tailwind.config.js`

**QA Phase 2**:
- [ ] Property card: satu shadow level saat resting
- [ ] Property card hover: naik satu level, tidak ada double shadow
- [ ] Quill toolbar: terbaca di dark mode
- [ ] Quill editor: content area terbaca di dark mode
- [ ] Quill: tidak overflow di narrow viewport

---

### Phase 3: Admin Shared Components (2–3 hari)
> Buat sekali, pakai di mana-mana.

**Tasks** (urutan direkomendasikan):
1. Buat + deploy `x-admin-page-header` di posts/index, properties/index
2. Buat + deploy `x-admin-filter-bar` di posts/index, properties/index
3. Buat + deploy `x-admin-status-badge` di posts/index, properties/index, dashboard
4. Buat + deploy `x-admin-empty-state` di posts/index, properties/index
5. Buat + deploy `x-admin-breadcrumb` di posts/edit, properties/edit
6. Buat + deploy `x-confirm-modal` di posts/index, properties/index
7. Update toast icons: emoji → SVG

**QA Phase 3**:
- [ ] `x-admin-page-header`: tampil di posts/index dan properties/index, styling konsisten
- [ ] `x-admin-status-badge`: "● Published"/"○ Draft" — warna + icon prefix
- [ ] `x-admin-filter-bar`: styling konsisten di posts dan properties
- [ ] `x-admin-empty-state`: muncul saat tidak ada data, ada CTA button
- [ ] `x-admin-breadcrumb`: link benar, "Properties › Edit: [nama]"
- [ ] `x-confirm-modal`: Cancel auto-focused, Escape close, form submit saat Confirm
- [ ] Toast: SVG icons, bukan emoji
- [ ] Dark mode: semua komponen baru berfungsi
- [ ] Tidak ada regresi — create, edit, delete masih berfungsi

---

### Phase 4: Admin Shell & Navigation (1 hari)
> Sidebar, settings tab, dashboard reorder.

**Tasks**:
1. Sidebar tooltip saat collapsed — Alpine + `title` attribute + CSS tooltip sederhana
2. Jika Phase 0 konfirmasi PromoRate route ada: tambahkan ke sidebar group "Booking & Revenue"
3. Settings: implementasi horizontal scrollable tab bar berdasarkan group names dari Phase 0
4. Dashboard: reorder sections sesuai §6.3
5. Dashboard quick actions: hapus pill badge, sederhanakan card
6. Dashboard KPI + Revenue: merge ke satu section
7. Breadcrumb: tambahkan ke categories/edit, tags/edit, bookings/show (jika ada)

**QA Phase 4**:
- [ ] Sidebar collapsed: hover icon menampilkan nama item
- [ ] Settings tab: semua group accessible via klik, keyboard navigable (Tab/Enter)
- [ ] Dashboard: Today Strip adalah section pertama setelah header
- [ ] Dashboard quick action cards: tidak ada pill "Buka →"
- [ ] Dashboard KPI + Revenue: dalam satu section visual
- [ ] Dark mode: semua area Phase 4 berfungsi

---

### Phase 5: Public Frontend Fixes (1–2 hari)
> Polish halaman publik.

**Tasks**:
1. Property card: pindahkan amenity badges ke card body, overlay hanya featured badge
2. Property card: type badge pojok kanan atas saja di overlay
3. Home hero: ubah gradient ke `primary_color → derived darker shade`
   - Gunakan `color-mix(in srgb, {{ $primaryColor }} 70%, black)` atau CSS `brightness()` filter sebagai fallback
4. CTA section: gradient fix sama seperti hero
5. Blog card hover: `hover:shadow-xl` → `hover:shadow-md`
6. Register `vendor/pagination/tailwind.blade.php` di `AppServiceProvider::boot()`
7. Sort dropdown: extract ke `_sort-dropdown.blade.php`
8. Slider dots: wrapper `min-w-[44px] min-h-[44px]` untuk touch target

**Verifikasi admin sync (dari output Phase 0)**:
- Jika CTA fields belum ada di `_homepage.blade.php`: tambahkan `cta_title`, `cta_text`, `cta_button_label`, `cta_button_url` sebagai input fields
- Jangan ubah cara Blade template merender field yang sudah ada

**QA Phase 5**:
- [ ] Property card: overlay bersih — hanya featured badge (jika ada)
- [ ] Property card: amenity badges di card body, bukan di foto
- [ ] Hero gradient: tidak aneh untuk primary color apapun
- [ ] CTA section gradient: konsisten dengan hero
- [ ] CTA settings: semua 4 field berfungsi dari admin Settings
- [ ] Pagination: styled, berfungsi di properties listing
- [ ] Blog card hover: shadow lebih soft
- [ ] Slider dots: touch target ≥ 44px di DevTools mobile emulation

---

### Phase 6: Responsive Polish (1 hari)
> Verifikasi semua breakpoints.

**Tasks**:
1. Tambahkan `overflow-x-auto` wrapper pada admin tables yang belum punya
2. Admin filter bars: pastikan responsive wrap di sm viewport
3. Dashboard KPI grid: `grid-cols-2` di xs
4. Verifikasi property slider 1/2/3 cards per breakpoint
5. Verifikasi hero font sizes di mobile viewport

**QA Phase 6**:
- [ ] Semua admin tables: tidak ada horizontal overflow tidak disengaja di mobile
- [ ] Filter bars admin: tidak overflow di sm
- [ ] Dashboard KPI: 2-col di xs, 4-col di lg
- [ ] Property slider: 1/2/3 cards sesuai breakpoint
- [ ] Hero: readable di 375px viewport

---

### Phase 7: Accessibility Fixes (1 hari)

**Tasks**:
1. Verifikasi status badge icon prefix (sudah dari Phase 3)
2. Property photo alt text: update ke `"{{ $property->name }} — foto {{ $loop->iteration }}"` atau dari media `alt` field
3. Contrast audit via DevTools — fix `text-gray-400` yang fail jika ditemukan
4. `aria-describedby` di form inputs — update `text-input` component
5. Jika A11Y-03 (slider Enter key) gagal di Phase 0: tambahkan `@keydown.enter` handler

**QA Phase 7**:
- [ ] Status badges: text differentiator (icon prefix), bukan hanya warna
- [ ] Property photo alt: informatif
- [ ] Normal text 14px+: contrast ≥ 4.5:1 (cek gray-400 dan toast text)
- [ ] Form validation errors: terhubung via `aria-describedby`
- [ ] Slider: Enter key berfungsi pada card link

---

### Phase 8: Interaction Polish (1 hari)
> States yang terlupakan.

**Tasks**:
1. Primary buttons: tambahkan loading state (spinner + disabled)
2. Dashboard charts: tambahkan skeleton placeholder sebelum Chart.js load
3. Form submit: tambahkan `opacity-50 pointer-events-none` saat processing
4. Media library picker: verifikasi loading state ada

**QA Phase 8 — Final & Regression**:
- [ ] Primary buttons: spinner saat loading, tidak bisa diklik dua kali
- [ ] Dashboard: placeholder ada sebelum chart render
- [ ] Form: tidak bisa double submit
- [ ] **Regression manual** (5 menit): create property → create post → lihat booking list → hapus post — semua masih berfungsi
- [ ] Semua P0 items selesai
- [ ] Semua P1 items selesai
- [ ] Tidak ada `[NEEDS APPROVAL]` items yang diimplementasi

---

## 18. Definition of Done

### Admin
- [ ] Flash message: toast only, tidak ada HTML block duplikat
- [ ] Setiap admin index: `x-admin-page-header`, `x-admin-filter-bar`, `x-admin-status-badge`, `x-admin-empty-state`
- [ ] Semua delete actions: `x-confirm-modal`, bukan `confirm()` native
- [ ] Breadcrumb: ada di semua create/edit/show admin
- [ ] Settings: tab navigation visible dan keyboard accessible
- [ ] Sidebar: tooltip di collapsed mode
- [ ] Dashboard: Today Strip adalah section pertama
- [ ] Quill editor: readable di dark mode
- [ ] Button radius: `rounded-lg` konsisten di seluruh admin

### Public
- [ ] Tidak ada radial dot pattern di halaman manapun
- [ ] WhatsApp: tidak ada wobble, pulse ada `prefers-reduced-motion` guard
- [ ] Property card: overlay bersih, amenity di card body
- [ ] Filter mobile trigger: primary color
- [ ] Slider dots: touch target ≥ 44px
- [ ] Custom pagination view aktif

### Design System Contract
- [ ] Radius: `rounded-lg` inputs, `rounded-xl` cards, `rounded-full` pills, `rounded-none` sidebar
- [ ] Shadow: tidak ada double shadow pada element manapun
- [ ] Status badges: icon prefix + warna
- [ ] Tidak ada CSS custom properties baru

### Accessibility
- [ ] Semua looping animations: `prefers-reduced-motion` guard
- [ ] Touch targets ≥ 44px
- [ ] Form errors: terhubung via `aria-describedby`
- [ ] Normal text contrast ≥ 4.5:1

### Business Logic Safety
- [ ] Tidak ada perubahan controller, service, atau model selain Blade components baru
- [ ] Pricing, booking, voucher logic tidak disentuh
- [ ] Semua `[NEEDS APPROVAL]` items: tidak diimplementasi

### Admin-Public Sync
- [ ] CTA section tetap dikontrol dari Settings › Homepage
- [ ] `primaryColor` dari SettingsService masih berfungsi di semua area yang direfactor

---

## Appendix: File Changes Map

### Dihapus / Dikurangi
- `resources/views/layouts/admin.blade.php` — hapus 3 HTML flash blocks; tambahkan sidebar tooltips, active border
- `resources/css/app.css` — hapus `whatsapp-wobble`; tambahkan reduced-motion guard, Quill dark mode, property card shadow fix
- `resources/views/home.blade.php` — hapus radial dot overlay; fix hero gradient
- `resources/views/properties/index.blade.php` — fix filter button color

### Dimodifikasi
- `resources/js/app.js` — pindahkan scroll-to-top logic dari inline scripts
- `resources/views/dashboard.blade.php` — reorder sections, simplify quick action cards
- `resources/views/admin/posts/index.blade.php` — shared components
- `resources/views/admin/posts/edit.blade.php` — breadcrumb
- `resources/views/admin/properties/index.blade.php` — shared components
- `resources/views/admin/properties/edit.blade.php` — breadcrumb, section headers
- `resources/views/admin/settings/index.blade.php` — tab navigation
- `resources/views/admin/settings/partials/_homepage.blade.php` — verifikasi/tambahkan CTA fields
- `app/Providers/AppServiceProvider.php` — register pagination view

### Dibuat Baru
- `resources/views/components/admin-page-header.blade.php`
- `resources/views/components/admin-status-badge.blade.php`
- `resources/views/components/admin-filter-bar.blade.php`
- `resources/views/components/admin-breadcrumb.blade.php`
- `resources/views/components/admin-empty-state.blade.php`
- `resources/views/components/confirm-modal.blade.php`
- `resources/views/vendor/pagination/tailwind.blade.php`
- `resources/views/properties/_sort-dropdown.blade.php`

---

*Mulai dari Phase 0. Selesaikan dan verifikasi QA checklist setiap phase sebelum lanjut ke phase berikutnya. Jangan skip Phase 0.*