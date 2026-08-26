import * as Turbo from '@hotwired/turbo';
// ponytail: Turbo Drive aktif otomatis untuk link same-origin & submit form;
// tidak dipasang Stimulus karena belum dibutuhkan — tambah saat butuh interaktivitas reaktif.

import Alpine from 'alpinejs';

window.Alpine = Alpine;

// ─── Turbo Progress Bar — aktifkan dengan delay 0 agar langsung muncul ─────
Turbo.config.drive.progressBarDelay = 0;

// ─── Dynamic script loader ─────────────────────────────────────────────────
// Loads a third-party <script src> once (deduped) and resolves when ready.
// Needed because CDN libs (Leaflet `L`, Chart.js `Chart`) are used by inline
// view scripts; with Turbo body-swaps the inline code can run before the async
// CDN script finishes. Wrapping usage in loadScript(src).then(...) guarantees
// the global is defined before it is referenced.
window.loadScript = function (src) {
    return new Promise(function (resolve, reject) {
        var existing = document.querySelector('script[data-dyn-src="' + src + '"]');
        if (existing) {
            if (existing.dataset.loaded === 'true') {
                resolve();
            } else {
                existing.addEventListener('load', function () { resolve(); });
                existing.addEventListener('error', reject);
            }
            return;
        }
        var s = document.createElement('script');
        s.src = src;
        s.async = false;
        s.setAttribute('data-dyn-src', src);
        s.addEventListener('load', function () { s.dataset.loaded = 'true'; resolve(); });
        s.addEventListener('error', reject);
        document.head.appendChild(s);
    });
};

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

// ─── searchOverlay — fullscreen search overlay (header magnifier) ───────────
// Reuses the SAME public endpoint (route('search.suggest')) and response shape
// ({ data: [{ title, url, type }] }) as the homepage `searchAutocomplete`
// component, so live results behave identically. State (open/close) is managed
// here; opening is triggered by the `open-search` window event dispatched from
// the header magnifier buttons (decouples Alpine scopes, mirrors share-modal).
Alpine.data('searchOverlay', (config = {}) => ({
    open: false,
    query: '',
    results: [],
    loading: false,
    highlighted: -1,
    timer: null,
    controller: null,
    action: config.action ?? '',

    get hasResults() {
        return this.results.length > 0;
    },

    openOverlay() {
        this.open = true;
        document.documentElement.classList.add('overflow-hidden');
        // Autofocus the large input once the overlay is painted.
        this.$nextTick(() => {
            const el = this.$refs.input;
            if (el) el.focus();
        });
    },

    closeOverlay() {
        this.open = false;
        this.query = '';
        this.results = [];
        this.highlighted = -1;
        this.loading = false;
        this.controller?.abort();
        document.documentElement.classList.remove('overflow-hidden');
    },

    // Highlight query matches in result titles (escaped -> safe for x-html).
    highlight(title) {
        const q = this.query.trim();
        const escaped = escapeHtml(String(title));
        if (!q) return escaped;
        const pattern = escapeHtml(q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'));
        const mark = '<mark class="bg-yellow-200 text-inherit px-0.5 rounded-sm dark:bg-yellow-500/30">' + pattern + '</mark>';
        return escaped.replace(new RegExp(pattern, 'gi'), () => mark);
    },

    async search() {
        clearTimeout(this.timer);
        const q = this.query.trim();

        if (!q) {
            this.results = [];
            this.loading = false;
            this.highlighted = -1;
            return;
        }

        this.timer = setTimeout(async () => {
            if (this.query.trim().length < 2) {
                this.results = [];
                this.loading = false;
                this.highlighted = -1;
                return;
            }

            const key = this.query.trim().toLowerCase();

            // Reuse the shared client-side cache from searchAutocomplete.
            if (searchCache.has(key)) {
                this.results = searchCache.get(key);
                this.highlighted = this.results.length ? 0 : -1;
                return;
            }

            this.loading = true;
            try {
                this.controller?.abort();
                this.controller = new AbortController();

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

                if (searchCache.size >= SEARCH_CACHE_MAX) {
                    const firstKey = searchCache.keys().next().value;
                    searchCache.delete(firstKey);
                }
                searchCache.set(key, data);
            } catch (e) {
                if (e?.name === 'AbortError') return;
                this.results = [];
            } finally {
                this.loading = false;
            }
        }, 300);
    },

    onKeydown(e) {
        if (e.key === 'Escape') {
            e.preventDefault();
            this.closeOverlay();
            return;
        }
        if (!this.hasResults) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            this.highlighted = (this.highlighted + 1) % this.results.length;
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            this.highlighted = (this.highlighted - 1 + this.results.length) % this.results.length;
        } else if (e.key === 'Enter' && this.highlighted >= 0) {
            e.preventDefault();
            this.go(this.results[this.highlighted]);
        }
    },

    go(result) {
        const url = result.url;
        this.closeOverlay();
        Turbo.visit(url);
    },
}));

// ─── photoGallery — property photo uploader / gallery ──────────────────────
// Registered here (before Alpine.start()) so the component is guaranteed to
// exist when x-data="photoGallery({...})" is initialised, including after
// Turbo body-swaps. Previously this lived in an `alpine:init` listener inside
// a @push('scripts') block that ran AFTER Alpine.start(), so it never
// registered — causing "isDragging/errors/existingPhotos/... is not defined"
// and "init is not defined" ReferenceErrors.
//
// Config object:
//   existing        – array of {id, media_id, url, category} from server
//   initialFeatured – current featured_image_id (media ID) or null
//   categories      – ordered array of category option strings
Alpine.data('photoGallery', function (config = {}) {
    return {
        /* ── state ──────────────────────────────────────────────────── */
        categories:      config.categories  || [],
        existingPhotos:  JSON.parse(JSON.stringify(config.existing || [])),
        newPhotos:       [],          // {uid, file, preview, category}
        deletedIds:      [],          // PropertyPhoto IDs to delete
        featuredMediaId: config.initialFeatured || null,  // existing photo
        featuredNewUid:  null,        // new photo uid (visual only)
        errors:          [],
        isDragging:      false,
        isDragOver:      false,

        /* ── constants ───────────────────────────────────────────────── */
        MAX_MB:    10,
        MIME_OK:   ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],

        /* ── lifecycle ───────────────────────────────────────────────── */
        init() {
            // Hook into the parent form's submit event so we can inject
            // the real file inputs + hidden fields before the browser
            // serialises the form data.
            var self    = this;
            var section = this.$el;
            var form    = section.closest('form');
            if (form) {
                form.addEventListener('submit', function (e) {
                    self.prepareSubmit(e, form);
                }, { capture: true });
            }
        },

        /* ── drag & drop ─────────────────────────────────────────────── */
        handleDrop(event) {
            var files = event.dataTransfer ? event.dataTransfer.files : [];
            this.processFiles(Array.from(files));
        },

        handleFileInput(event) {
            var files = event.target.files || [];
            this.processFiles(Array.from(files));
            // Reset so the same file can be re-selected if removed
            event.target.value = '';
        },

        /* ── file processing ─────────────────────────────────────────── */
        processFiles(files) {
            var self   = this;
            var newErr = [];

            files.forEach(function (file) {
                if (!self.MIME_OK.includes(file.type)) {
                    newErr.push(file.name + ': tipe file tidak didukung (gunakan JPEG, PNG, WebP, atau GIF).');
                    return;
                }
                if (file.size > self.MAX_MB * 1024 * 1024) {
                    newErr.push(file.name + ': ukuran melebihi ' + self.MAX_MB + ' MB.');
                    return;
                }

                var uid    = Math.random().toString(36).slice(2) + Date.now().toString(36);
                var reader = new FileReader();

                reader.onload = function (e) {
                    self.newPhotos.push({
                        uid:      uid,
                        file:     file,
                        preview:  e.target.result,
                        category: 'Others',
                    });
                };
                reader.readAsDataURL(file);
            });

            if (newErr.length) {
                this.errors = this.errors.concat(newErr);
            }
        },

        /* ── star / featured ─────────────────────────────────────────── */
        setFeatured(photo) {
            // Toggle: clicking the active star deselects it
            if (this.featuredMediaId === photo.media_id) {
                this.featuredMediaId = null;
            } else {
                this.featuredMediaId = photo.media_id;
                this.featuredNewUid  = null;  // clear new-photo star
            }
        },

        setFeaturedNew(photo) {
            if (this.featuredNewUid === photo.uid) {
                this.featuredNewUid = null;
            } else {
                this.featuredNewUid  = photo.uid;
                this.featuredMediaId = null;  // clear existing-photo star
            }
        },

        /* ── deletion ────────────────────────────────────────────────── */
        removeExisting(photo) {
            this.deletedIds.push(photo.id);
            this.existingPhotos = this.existingPhotos.filter(function (p) {
                return p.id !== photo.id;
            });
            // If we deleted the featured photo, clear the selection
            if (this.featuredMediaId === photo.media_id) {
                this.featuredMediaId = null;
            }
        },

        removeNew(photo) {
            this.newPhotos = this.newPhotos.filter(function (p) {
                return p.uid !== photo.uid;
            });
            if (this.featuredNewUid === photo.uid) {
                this.featuredNewUid = null;
            }
            // Revoke the object URL to free memory (no-op for data URLs but harmless)
            if (photo.preview && photo.preview.startsWith('blob:')) {
                URL.revokeObjectURL(photo.preview);
            }
        },

        /* ── form-submit preparation ─────────────────────────────────── */
        // Called in the form's capture-phase submit listener. Injects all
        // photo-related hidden inputs + real file inputs into
        // #photo-submit-container so the browser includes them in the
        // multipart/form-data body.
        //
        // Field mapping:
        //   photo_categories            JSON array of distinct categories
        //   gallery_uploads[N][]        file input per category index N
        //   deleted_photo_ids           comma-separated existing photo IDs
        //   photo_categories_update[id] already handled by x-model hidden inputs
        //   featured_image_id           media_id of chosen photo (if existing)
        prepareSubmit(event, form) {
            var container = document.getElementById('photo-submit-container');
            if (!container) { return; }

            // Wipe any previous injections (e.g. failed validation re-submit)
            container.innerHTML = '';

            /* 1. Collect unique categories from new photos */
            var catIndex = {};   // category → array index
            var catList  = [];   // ordered list

            this.newPhotos.forEach(function (p) {
                if (!(p.category in catIndex)) {
                    catIndex[p.category] = catList.length;
                    catList.push(p.category);
                }
            });

            /* 2. photo_categories (JSON) */
            if (catList.length > 0) {
                var catInput = document.createElement('input');
                catInput.type  = 'hidden';
                catInput.name  = 'photo_categories';
                catInput.value = JSON.stringify(catList);
                container.appendChild(catInput);
            }

            /* 3. gallery_uploads[N][] – one <input type="file"> per new photo */
            //    We must use a DataTransfer to programmatically assign a File
            //    to a file input cross-browser.
            if (this.newPhotos.length > 0 && typeof DataTransfer !== 'undefined') {
                var fileInputs = {};  // category → <input type="file">

                this.newPhotos.forEach(function (p) {
                    var idx = catIndex[p.category];
                    var key = String(idx);
                    if (!fileInputs[key]) {
                        var fi = document.createElement('input');
                        fi.type     = 'file';
                        fi.name     = 'gallery_uploads[' + idx + '][]';
                        fi.multiple = true;
                        fi.style.display = 'none';
                        var dt = new DataTransfer();
                        dt.items.add(p.file);
                        fi.files = dt.files;
                        fileInputs[key] = { el: fi, dt: dt };
                    } else {
                        fileInputs[key].dt.items.add(p.file);
                        fileInputs[key].el.files = fileInputs[key].dt.files;
                    }
                });

                Object.values(fileInputs).forEach(function (entry) {
                    container.appendChild(entry.el);
                });
            }

            /* 4. deleted_photo_ids */
            if (this.deletedIds.length > 0) {
                var delInput = document.createElement('input');
                delInput.type  = 'hidden';
                delInput.name  = 'deleted_photo_ids';
                delInput.value = this.deletedIds.join(',');
                container.appendChild(delInput);
            }

            /* 5. featured_image_id — only when an EXISTING photo is starred */
            if (this.featuredMediaId) {
                var featInput = document.createElement('input');
                featInput.type  = 'hidden';
                featInput.name  = 'featured_image_id';
                featInput.value = this.featuredMediaId;
                container.appendChild(featInput);
            }
            // Note: if a NEW photo is starred (featuredNewUid set) we do NOT
            // send featured_image_id — the controller will skip it, and the
            // user can come back and star the photo after the first save.
        },
    };
});

// ─── Money inputs — "Rp" prefix + thousand-separator formatting ────────────
// A single reusable, framework-agnostic handler for every input marked with
// `data-money` (see <x-money-input> Blade component). It:
//   1. Formats the visible value with dot thousand separators as the user types
//      (e.g. 150000 → 150.000), matching the frontend price display which uses
//      PHP number_format($n, 0, ',', '.').
//   2. Formats any pre-filled value on load (edit forms).
//   3. Strips the separators back to a plain integer right before the owning
//      form submits, so the server receives clean digits (e.g. "150000") and
//      existing numeric/integer validation keeps passing — no server change
//      required.
//
// Delegated listeners on `document` make this Turbo-safe: inputs injected by
// body-swaps or dynamically shown price rows are handled without re-binding.
(function () {
    var GROUP = '.'; // thousand separator — matches number_format(n, 0, ',', '.')

    // Keep only digits, then group into threes with GROUP. Leading zeros and
    // any non-digit characters are discarded. Empty input stays empty.
    function formatMoney(raw) {
        var digits = String(raw == null ? '' : raw).replace(/\D/g, '');
        digits = digits.replace(/^0+(?=\d)/, ''); // strip leading zeros
        if (digits === '') return '';
        return digits.replace(/\B(?=(\d{3})+(?!\d))/g, GROUP);
    }

    // Plain integer string (digits only) for submission.
    function unformatMoney(value) {
        return String(value == null ? '' : value).replace(/\D/g, '');
    }

    function applyFormat(el) {
        if (!el) return;
        var formatted = formatMoney(el.value);
        if (el.value !== formatted) {
            el.value = formatted;
        }
    }

    // Format on input (delegated).
    document.addEventListener('input', function (e) {
        var el = e.target;
        if (el && el.matches && el.matches('[data-money]')) {
            applyFormat(el);
        }
    });

    // Format freshly-rendered values on load and after Turbo navigations.
    function formatAll(root) {
        (root || document).querySelectorAll('[data-money]').forEach(applyFormat);
    }
    document.addEventListener('DOMContentLoaded', function () { formatAll(document); });
    document.addEventListener('turbo:load', function () { formatAll(document); });
    document.addEventListener('turbo:render', function () { formatAll(document); });

    // Strip separators to plain integers right before submit (capture phase so
    // it runs before other submit handlers serialise the form). We restore the
    // formatted view afterwards so a validation-failed page keeps looking right.
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form || !form.querySelectorAll) return;
        var moneyEls = form.querySelectorAll('[data-money]');
        if (!moneyEls.length) return;

        moneyEls.forEach(function (el) {
            el.value = unformatMoney(el.value);
        });

        // Re-apply formatting on the next tick (submit may be prevented, or the
        // page may stay if navigation is blocked).
        setTimeout(function () {
            moneyEls.forEach(applyFormat);
        }, 0);
    }, true);
})();

// ponytail: Alpine.start() one-time app-level, sengaja dibiarkan di sini — TIDAK di `turbo:load`.
// Aman: Alpine memakai MutationObserver pada document (lifecycle.js, startObservingMutations),
// sehingga node baru dari Turbo body-swap ter-init otomatis via onElAdded -> initTree.
// Tidak ada DOMContentLoaded/jQuery/editor init di resources/js yang perlu di-rebind.
Alpine.start();
