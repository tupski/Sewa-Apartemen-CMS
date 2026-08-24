

import * as Turbo from '@hotwired/turbo';
// ponytail: Turbo Drive aktif otomatis untuk link same-origin & submit form;
// tidak dipasang Stimulus karena belum dibutuhkan — tambah saat butuh interaktivitas reaktif.

import Alpine from 'alpinejs';

window.Alpine = Alpine;

// ─── Turbo Progress Bar — aktifkan dengan delay 0 agar langsung muncul ─────
Turbo.setProgressBarDelay(0);

// ─── Skeleton Loading ────────────────────────────────────────────────────────
// Strategi: saat Turbo mulai fetch, langsung navigasi ke halaman tujuan
// (biarkan Turbo Drive kerja normal), lalu tampilkan skeleton overlay
// selama render berlangsung. Overlay hilang setelah render selesai.
//
// Skeleton shape ditentukan berdasarkan URL tujuan agar mirip layout aslinya.

function buildSkeleton(url) {
    const path = url ? new URL(url, window.location.href).pathname : window.location.pathname;

    // Shared shimmer card untuk listing
    const card = () => `
        <div class="sk-card">
            <div class="sk-pulse sk-img"></div>
            <div class="p-4 space-y-2.5">
                <div class="sk-pulse sk-bar" style="width:70%"></div>
                <div class="sk-pulse sk-text" style="width:50%"></div>
                <div class="sk-pulse sk-text" style="width:40%"></div>
                <div class="sk-pulse sk-bar mt-3" style="width:35%"></div>
            </div>
        </div>`;

    // Halaman listing apartemen (/apartments)
    if (/^\/apartments\b/.test(path) && !/\/apartments\//.test(path)) {
        return `
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="sk-pulse sk-title mb-8" style="width:220px;height:52px;border-radius:1rem;"></div>
            <div class="flex gap-8">
                <div class="hidden lg:block w-72 shrink-0 space-y-4">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 space-y-4 border border-gray-100 dark:border-gray-700">
                        <div class="sk-pulse sk-bar" style="width:60%"></div>
                        ${[80,60,70,55,65].map(w=>`<div class="sk-pulse sk-text" style="width:${w}%"></div>`).join('')}
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex justify-between mb-5">
                        <div class="sk-pulse sk-bar" style="width:160px"></div>
                        <div class="sk-pulse sk-bar" style="width:120px"></div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        ${[...Array(6)].map(()=>card()).join('')}
                    </div>
                </div>
            </div>
        </div>`;
    }

    // Halaman detail apartemen (/apartments/slug)
    if (/^\/apartments\//.test(path)) {
        return `
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="sk-pulse sk-title mb-4" style="width:55%"></div>
            <div class="sk-pulse sk-text mb-8" style="width:30%"></div>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 space-y-5">
                    <div class="sk-pulse rounded-2xl" style="height:320px;width:100%"></div>
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 space-y-3">
                        ${[70,55,60,45].map(w=>`<div class="sk-pulse sk-text" style="width:${w}%"></div>`).join('')}
                    </div>
                </div>
                <div class="space-y-4">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 space-y-3">
                        ${[50,65,40,100,100].map((w,i)=>`<div class="sk-pulse" style="height:${i>=3?'40px':'0.75rem'};width:${w}%;border-radius:.5rem;margin-bottom:.5rem"></div>`).join('')}
                    </div>
                </div>
            </div>
        </div>`;
    }

    // Admin pages — tabel listing
    if (/^\/admin/.test(path)) {
        return `
        <div class="p-6 space-y-4">
            <div class="sk-pulse sk-title" style="width:200px"></div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 space-y-3">
                <div class="flex gap-3 mb-4">
                    <div class="sk-pulse" style="width:220px;height:36px;border-radius:.5rem"></div>
                    <div class="sk-pulse" style="width:100px;height:36px;border-radius:.5rem"></div>
                </div>
                ${[...Array(8)].map(()=>`
                <div class="flex gap-4 items-center">
                    <div class="sk-pulse sk-text" style="width:5%"></div>
                    <div class="sk-pulse sk-text" style="width:30%"></div>
                    <div class="sk-pulse sk-text" style="width:20%"></div>
                    <div class="sk-pulse sk-text" style="width:15%"></div>
                    <div class="sk-pulse sk-text" style="width:10%"></div>
                </div>`).join('')}
            </div>
        </div>`;
    }

    // Homepage & halaman lain — generic
    return `
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-6">
            <div class="sk-pulse sk-title"></div>
            <div class="sk-pulse sk-bar" style="width:60%"></div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mt-8">
                ${[...Array(3)].map(()=>card()).join('')}
            </div>
        </div>`;
}

function showSkeleton(url) {
    let el = document.getElementById('page-skeleton');
    if (!el) return;
    el.innerHTML = buildSkeleton(url);
    el.classList.add('visible');
}

function hideSkeleton() {
    const el = document.getElementById('page-skeleton');
    if (el) el.classList.remove('visible');
}

// Tampilkan skeleton saat Turbo mulai fetch halaman baru
document.addEventListener('turbo:before-fetch-request', (e) => {
    // Hanya untuk navigasi (bukan form submit non-GET)
    // Skip bfcache restoration — halaman sudah di-cache, tak butuh skeleton
    if (e.detail?.visit?.action === 'restore') return;
    const method = (e.detail?.fetchOptions?.method || 'GET').toUpperCase();
    if (method === 'GET') {
        showSkeleton(e.detail?.url?.href);
    }
});

// Sembunyikan skeleton sesaat sebelum Turbo render halaman baru
// (halaman tujuan sudah ada di DOM, skeleton tidak dibutuhkan lagi)
document.addEventListener('turbo:before-render', () => {
    hideSkeleton();
});

// Fallback: pastikan skeleton hilang setelah render selesai
document.addEventListener('turbo:render', () => {
    hideSkeleton();
});

// Jika navigasi dibatalkan atau error
document.addEventListener('turbo:fetch-request-error', () => {
    hideSkeleton();
});
document.addEventListener('turbo:frame-missing', () => {
    hideSkeleton();
});

// Escape HTML sebelum interpolasi ke x-html: title & highlight harus bersih
// dari XSS (title dari server, query dari user — keduanya ikut dirender).
function escapeHtml(str) {
    // Numeric char refs (bukan entitas &...;) — hasil sama, aman untuk x-html.
    return str.replace(/[&<>"']/g, (c) => '&#' + c.charCodeAt(0) + ';');
}

// Autocomplete pencarian publik (Turbo-compatible).
// Data komponen dipakai via `x-data="searchAutocomplete({ action: '...' })"`
// pada wrapper input; Alpine.start() sekali + MutationObserver otomatis
// meng-init node baru hasil body-swap Turbo — tanpa butuh `turbo:load`.
Alpine.data('searchAutocomplete', (config = {}) => ({
    query: config.value ?? '',
    results: [],
    open: false,
    loading: false,
    highlighted: -1,
    timer: null,
    label: config.label ?? '',
    placeholder: config.placeholder ?? '',
    action: config.action ?? '',
    fieldName: config.fieldName ?? 'search',
    inputClasses: config.inputClasses ?? '',

    get hasResults() {
        return this.results.length > 0;
    },

    // Sorot semua kemunculan query (case-insensitive) di judul hasil.
    // Aman untuk x-html: title DI-ESCAPE dulu, lalu query ter-escape dibungkus <mark>.
    // Replacer function (bukan string) -> hasil literal, tak ada substitusi $&.
    highlight(title) {
        const q = this.query.trim();
        const escaped = escapeHtml(String(title));
        if (!q) return escaped;
        const pattern = escapeHtml(q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'));
        const mark = '<mark class="bg-yellow-200 text-inherit px-0.5 rounded-sm dark:bg-yellow-500/30">' + pattern + '</mark>';
        return escaped.replace(new RegExp(pattern, 'gi'), () => mark);
    },

    // ponytail: batas panjang query & debounce di client; endpoint tetap
    // divalidasi ulang (>=2 karakter) sebagai trust boundary.
    async search() {
        clearTimeout(this.timer);
        const q = this.query.trim();

        if (!q) {
            this.results = [];
            this.open = false;
            this.loading = false;
            return;
        }

        this.timer = setTimeout(async () => {
            if (this.query.trim().length < 2) {
                this.results = [];
                this.open = false;
                this.loading = false;
                return;
            }
            this.open = true;
            this.loading = true;
            try {
                // Absolut URL + encodeURIComponent: hasil fetch boleh di-cache
                // Turbo, dan aman dari race (setiap hasil attach ke response-nya).
                const res = await fetch(this.action + '?q=' + encodeURIComponent(this.query.trim()), {
                    headers: { 'Accept': 'application/json' },
                });
                const json = await res.json();
                this.results = json.data ?? [];
                this.highlighted = this.results.length ? 0 : -1;
                this.open = true;
            } catch (e) {
                // Abaikan: autocomplete non-kritis, form utama tetap jalan.
                this.results = [];
                this.open = false;
            } finally {
                this.loading = false;
            }
        }, 300);
    },

    onKeydown(e) {
        if (!this.open || this.results.length === 0) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            this.highlighted = (this.highlighted + 1) % this.results.length;
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            this.highlighted = (this.highlighted - 1 + this.results.length) % this.results.length;
        } else if (e.key === 'Enter' && this.highlighted >= 0) {
            e.preventDefault();
            this.go(this.results[this.highlighted]);
        } else if (e.key === 'Escape') {
            e.preventDefault();
            this.close();
        }
    },

    go(result) {
        this.close();
        window.location.href = result.url;
    },

    close() {
        this.open = false;
        this.results = [];
        this.highlighted = -1;
    },
}));

// ponytail: Alpine.start() one-time app-level, sengaja dibiarkan di sini — TIDAK di `turbo:load`.
// Aman: Alpine memakai MutationObserver pada document (lifecycle.js, startObservingMutations),
// sehingga node baru dari Turbo body-swap ter-init otomatis via onElAdded -> initTree.
// Tidak ada DOMContentLoaded/jQuery/editor init di resources/js yang perlu di-rebind.
Alpine.start();
