import * as Turbo from '@hotwired/turbo';
// ponytail: Turbo Drive aktif otomatis untuk link same-origin & submit form;
// tidak dipasang Stimulus karena belum dibutuhkan — tambah saat butuh interaktivitas reaktif.

import Alpine from 'alpinejs';

window.Alpine = Alpine;

// ─── Turbo Progress Bar — aktifkan dengan delay 0 agar langsung muncul ─────
Turbo.config.drive.progressBarDelay = 0;

// Escape HTML sebelum interpolasi ke x-html: title & highlight harus bersih
// dari XSS (title dari server, query dari user — keduanya ikut dirender).
function escapeHtml(str) {
    // Numeric char refs (bukan entitas &...;) — hasil sama, aman untuk x-html.
    return str.replace(/[&<>"']/g, (c) => '&#' + c.charCodeAt(0) + ';');
}

// Autocomplete client-side cache — simple Map, max 50 entries, keyed by query string.
const searchCache = new Map();
const SEARCH_CACHE_MAX = 50;

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
    controller: null,
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

            const key = this.query.trim().toLowerCase();

            // Client-side cache hit
            if (searchCache.has(key)) {
                this.results = searchCache.get(key);
                this.highlighted = this.results.length ? 0 : -1;
                this.open = true;
                return;
            }

            this.open = true;
            this.loading = true;
            try {
                // AbortController race protection — batalkan fetch sebelumnya
                this.controller?.abort();
                this.controller = new AbortController();

                // 8s timeout race
                const res = await Promise.race([
                    fetch(this.action + '?q=' + encodeURIComponent(this.query.trim()), {
                        headers: { 'Accept': 'application/json' },
                        signal: this.controller.signal,
                    }),
                    new Promise((_, reject) => setTimeout(() => reject(new Error('timeout')), 8000)),
                ]);
                const json = await res.json();
                const data = json.data ?? [];
                this.results = data;
                this.highlighted = this.results.length ? 0 : -1;
                this.open = true;

                // Cache the result, evict oldest if at limit
                if (searchCache.size >= SEARCH_CACHE_MAX) {
                    const firstKey = searchCache.keys().next().value;
                    searchCache.delete(firstKey);
                }
                searchCache.set(key, data);
            } catch (e) {
                if (e?.name === 'AbortError') return; // silence abort
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
        Turbo.visit(result.url);
    },

    close() {
        this.open = false;
        this.highlighted = -1;
    },
}));

// ponytail: Alpine.start() one-time app-level, sengaja dibiarkan di sini — TIDAK di `turbo:load`.
// Aman: Alpine memakai MutationObserver pada document (lifecycle.js, startObservingMutations),
// sehingga node baru dari Turbo body-swap ter-init otomatis via onElAdded -> initTree.
// Tidak ada DOMContentLoaded/jQuery/editor init di resources/js yang perlu di-rebind.
Alpine.start();
