

import * as Turbo from '@hotwired/turbo';
// ponytail: Turbo Drive aktif otomatis untuk link same-origin & submit form;
// tidak dipasang Stimulus karena belum dibutuhkan — tambah saat butuh interaktivitas reaktif.

import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Autocomplete pencarian publik (Turbo-compatible).
// Data komponen dipakai via `x-data="searchAutocomplete({ action: '...' })"`
// pada wrapper input; Alpine.start() sekali + MutationObserver otomatis
// meng-init node baru hasil body-swap Turbo — tanpa butuh `turbo:load`.
Alpine.data('searchAutocomplete', (config = {}) => ({
    query: '',
    results: [],
    open: false,
    loading: false,
    highlighted: -1,
    timer: null,
    label: config.label ?? '',
    placeholder: config.placeholder ?? '',
    action: config.action ?? '',
    fieldName: config.fieldName ?? 'search',

    get hasResults() {
        return this.results.length > 0;
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
