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
        libraryPhotos:   [],          // {media_id, url, thumbnail_url, filename, category} — picked from media library, not yet saved
        deletedIds:      [],          // PropertyPhoto IDs to delete
        featuredMediaId: config.initialFeatured || null,  // existing photo
        featuredNewUid:  null,        // new photo uid (visual only)
        errors:          [],
        isDragging:      false,
        isDragOver:      false,

        /* ── media picker modal state ────────────────────────────────── */
        pickerOpen:        false,
        pickerTab:         'gallery',   // 'gallery' | 'upload'
        pickerMediaUrl:    config.mediaIndexUrl || '',
        pickerCsrf:        config.csrf || '',
        pickerItems:       [],
        pickerLoading:     false,
        pickerLoaded:      false,
        pickerSearch:      '',
        pickerPage:        1,
        pickerLastPage:    1,
        pickerSelected:    {},          // { media_id: true } — checked in modal
        pickerUploading:   false,
        pickerUploadUrl:   config.mediaUploadUrl || '',

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

        /* ── media picker modal ──────────────────────────────────────── */
        openPicker() {
            this.pickerOpen    = true;
            this.pickerTab     = 'gallery';
            this.pickerSearch  = '';
            this.pickerSelected = {};
            if (!this.pickerLoaded) {
                this.loadPickerMedia();
            }
        },

        closePicker() {
            this.pickerOpen = false;
        },

        loadPickerMedia(page) {
            if (!this.pickerMediaUrl) return;
            page = page || 1;
            this.pickerLoading = true;
            var url = this.pickerMediaUrl + '?json=1&type=image&page=' + page;
            if (this.pickerSearch) {
                url += '&search=' + encodeURIComponent(this.pickerSearch);
            }
            var self = this;
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (json) {
                    if (page === 1) {
                        self.pickerItems = json.data || [];
                    } else {
                        self.pickerItems = self.pickerItems.concat(json.data || []);
                    }
                    self.pickerPage     = json.meta ? json.meta.current_page : 1;
                    self.pickerLastPage = json.meta ? json.meta.last_page    : 1;
                    self.pickerLoaded   = true;
                    self.pickerLoading  = false;
                })
                .catch(function () { self.pickerLoading = false; });
        },

        searchPickerMedia() {
            this.pickerPage    = 1;
            this.pickerLoaded  = false;
            this.pickerItems   = [];
            this.loadPickerMedia(1);
        },

        loadMorePickerMedia() {
            if (this.pickerPage < this.pickerLastPage) {
                this.loadPickerMedia(this.pickerPage + 1);
            }
        },

        togglePickerSelect(item) {
            var id = String(item.id);
            if (this.pickerSelected[id]) {
                var copy = Object.assign({}, this.pickerSelected);
                delete copy[id];
                this.pickerSelected = copy;
            } else {
                this.pickerSelected = Object.assign({}, this.pickerSelected, { [id]: true });
            }
        },

        isPickerSelected(item) {
            return !!this.pickerSelected[String(item.id)];
        },

        // Already-attached media IDs (existing saved photos) — used to dim them in the picker
        alreadyAttachedMediaIds() {
            var ids = {};
            this.existingPhotos.forEach(function (p) { ids[String(p.media_id)] = true; });
            this.libraryPhotos.forEach(function (p) { ids[String(p.media_id)] = true; });
            return ids;
        },

        confirmPickerSelection() {
            var self    = this;
            var already = this.alreadyAttachedMediaIds();
            Object.keys(this.pickerSelected).forEach(function (idStr) {
                if (already[idStr]) return;   // skip duplicates
                var item = self.pickerItems.find(function (m) { return String(m.id) === idStr; });
                if (!item) return;
                self.libraryPhotos.push({
                    media_id:      item.id,
                    url:           item.url           || item.thumbnail_url || '',
                    thumbnail_url: item.thumbnail_url || item.url           || '',
                    filename:      item.original_filename || item.filename  || '',
                    category:      'Others',
                });
            });
            this.pickerSelected = {};
            this.pickerOpen     = false;
        },

        removeLibraryPhoto(photo) {
            this.libraryPhotos = this.libraryPhotos.filter(function (p) {
                return p.media_id !== photo.media_id;
            });
            if (this.featuredMediaId === photo.media_id) {
                this.featuredMediaId = null;
            }
        },

        setFeaturedLibrary(photo) {
            if (this.featuredMediaId === photo.media_id) {
                this.featuredMediaId = null;
            } else {
                this.featuredMediaId = photo.media_id;
                this.featuredNewUid  = null;
            }
        },

        /* ── modal upload tab ────────────────────────────────────────── */
        handlePickerDrop(event) {
            var files = event.dataTransfer ? event.dataTransfer.files : [];
            this.processPickerFiles(Array.from(files));
        },

        handlePickerFileInput(event) {
            var files = event.target.files || [];
            this.processPickerFiles(Array.from(files));
            event.target.value = '';
        },

        processPickerFiles(files) {
            // Adds files to newPhotos (same as the drag-drop zone)
            // then closes the modal so the user can see the grid
            this.processFiles(files);
            if (files.length > 0) {
                this.pickerOpen = false;
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

            /* 5. gallery_media_ids[mediaId] = category — library-picked photos */
            this.libraryPhotos.forEach(function (p) {
                var libInput = document.createElement('input');
                libInput.type  = 'hidden';
                libInput.name  = 'gallery_media_ids[' + p.media_id + ']';
                libInput.value = p.category || 'Others';
                container.appendChild(libInput);
            });

            /* 6. featured_image_id — existing or library photo starred */
            if (this.featuredMediaId) {
                var featInput = document.createElement('input');
                featInput.type  = 'hidden';
                featInput.name  = 'featured_image_id';
                featInput.value = this.featuredMediaId;
                container.appendChild(featInput);
            }
            // Note: if a NEW upload is starred (featuredNewUid set) we do NOT
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

// ─── Unsaved-changes guard for admin forms ─────────────────────────────────
// Warns the user before they navigate away (Turbo visit) or close/refresh the
// tab (beforeunload) when a marked form has unsaved edits.
//
// Opt-in: add `data-warn-unsaved` to the primary create/edit <form>. Search /
// filter bars are NOT marked so they never trigger the warning.
//
// Turbo-safe design:
//   • Global listeners (beforeunload, turbo:before-visit, delegated
//     input/change/submit) are attached exactly ONCE via a module-level flag,
//     so Turbo body-swaps never stack duplicate handlers.
//   • Forms are (re-)snapshotted on every turbo:load / turbo:render / initial
//     DOMContentLoaded, because Turbo replaces <body> without reloading JS.
(function () {
    var DEFAULT_MSG = 'You have unsaved changes. Are you sure you want to leave this page?';

    // The currently-tracked marked form and its baseline serialized snapshot.
    // We track a single active form (admin create/edit pages show one main form).
    var trackedForm = null;
    var baseline = '';
    var isDirty = false;
    var submitting = false;

    // Read the translated warning message from the <meta> tag injected by the
    // admin layout; fall back to the English default when absent.
    function warningMessage() {
        var meta = document.querySelector('meta[name="unsaved-warning"]');
        var content = meta && meta.getAttribute('content');
        return content && content.trim() ? content : DEFAULT_MSG;
    }

    // Serialize a form's user-editable fields into a stable string. We iterate
    // elements (rather than FormData) so we can include unchecked checkboxes,
    // disabled-then-enabled fields, and keep ordering deterministic.
    function serialize(form) {
        if (!form || !form.elements) return '';
        var parts = [];
        for (var i = 0; i < form.elements.length; i++) {
            var el = form.elements[i];
            if (!el.name) continue;
            var type = (el.type || '').toLowerCase();
            // Skip buttons and CSRF/method tokens (never user-editable state).
            if (type === 'submit' || type === 'button' || type === 'reset' || type === 'file') continue;
            if (el.name === '_token' || el.name === '_method') continue;
            if (type === 'checkbox' || type === 'radio') {
                parts.push(el.name + '=' + (el.checked ? '1' : '0'));
            } else {
                parts.push(el.name + '=' + (el.value == null ? '' : el.value));
            }
        }
        return parts.join('&');
    }

    // Find the marked form on the current page and take a fresh baseline.
    function snapshot() {
        trackedForm = document.querySelector('form[data-warn-unsaved]');
        isDirty = false;
        submitting = false;
        baseline = trackedForm ? serialize(trackedForm) : '';
    }

    // Re-serialize and compare against the baseline to update the dirty flag.
    function refreshDirty() {
        if (!trackedForm) { isDirty = false; return; }
        // If the tracked form was removed from the DOM (Turbo swap between two
        // marked pages before re-snapshot), bail out safely.
        if (!document.contains(trackedForm)) { isDirty = false; return; }
        isDirty = serialize(trackedForm) !== baseline;
    }

    function attachGlobalListeners() {
        // Mark dirty on any input/change bubbling up from the tracked form.
        document.addEventListener('input', function (e) {
            if (submitting || !trackedForm) return;
            if (trackedForm.contains(e.target)) refreshDirty();
        });
        document.addEventListener('change', function (e) {
            if (submitting || !trackedForm) return;
            if (trackedForm.contains(e.target)) refreshDirty();
        });

        // Submitting the tracked form is a save — never warn for it. Clear the
        // flag so neither beforeunload nor turbo:before-visit fires.
        document.addEventListener('submit', function (e) {
            if (trackedForm && e.target === trackedForm) {
                submitting = true;
                isDirty = false;
            }
        }, true);

        // Native tab close / refresh / external navigation.
        window.addEventListener('beforeunload', function (e) {
            if (submitting || !isDirty) return;
            e.preventDefault();
            e.returnValue = '';
            return '';
        });

        // In-app Turbo navigation (link clicks, Turbo.visit). beforeunload does
        // NOT fire for these, so guard here with a confirm() dialog.
        document.addEventListener('turbo:before-visit', function (e) {
            if (submitting || !isDirty) return;
            if (!window.confirm(warningMessage())) {
                e.preventDefault();
            }
        });

        // A Turbo form submission that succeeds triggers a visit; make sure the
        // submit-start clears dirty even for Turbo-driven forms.
        document.addEventListener('turbo:submit-start', function (e) {
            if (trackedForm && e.target === trackedForm) {
                submitting = true;
                isDirty = false;
            }
        });
    }

    var initialized = false;
    function init() {
        if (!initialized) {
            attachGlobalListeners();
            initialized = true;
        }
        snapshot();
    }

    document.addEventListener('DOMContentLoaded', init);
    document.addEventListener('turbo:load', init);
    document.addEventListener('turbo:render', init);
})();

// ─── Media Library (WordPress-style uploader) ──────────────────────────────
// Powers resources/views/admin/media/index.blade.php: grid, Add-Media modal
// (upload / library / from-URL tabs) and the details editor modal.
Alpine.data('mediaLibrary', (config = {}) => ({
    items: config.items ?? [],
    uploadUrl: config.uploadUrl,
    fromUrlUrl: config.fromUrlUrl,
    indexUrl: config.indexUrl,
    csrf: config.csrf,

    // Add-media modal
    addOpen: false,
    tab: 'upload',
    dragging: false,
    queue: [],

    // Library tab
    libraryItems: [],
    libraryLoading: false,
    libraryLoaded: false,

    // From-URL tab
    urlValue: '',
    urlLoading: false,
    urlError: '',

    // Details modal
    detailsOpen: false,
    current: null,
    form: { title: '', alt: '', caption: '', description: '' },
    saving: false,
    saved: false,
    copied: false,

    openAdd() {
        this.addOpen = true;
        this.tab = 'upload';
        this.queue = [];
    },

    fileIcon(item) {
        const mime = item?.mime_type ?? '';
        if (mime === 'application/pdf') return 'fa-regular fa-file-pdf text-red-500';
        if (mime.startsWith('video/')) return 'fa-regular fa-file-video';
        if (mime.startsWith('image/')) return 'fa-regular fa-file-image';
        return 'fa-regular fa-file';
    },

    humanSize(bytes) {
        if (!bytes && bytes !== 0) return '-';
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1024 / 1024).toFixed(2) + ' MB';
    },

    // ── Upload from computer ──
    handleDrop(e) {
        this.dragging = false;
        this.handleFiles(e.dataTransfer.files);
    },

    handleFiles(fileList) {
        const files = Array.from(fileList || []);
        files.forEach((file) => this.uploadOne(file));
    },

    uploadOne(file) {
        const entry = { name: file.name, progress: 0, status: 'uploading' };
        this.queue.push(entry);

        const fd = new FormData();
        fd.append('files[]', file);
        fd.append('_token', this.csrf);

        const xhr = new XMLHttpRequest();
        xhr.open('POST', this.uploadUrl, true);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        xhr.upload.onprogress = (ev) => {
            if (ev.lengthComputable) {
                entry.progress = Math.round((ev.loaded / ev.total) * 100);
            }
        };

        xhr.onload = () => {
            if (xhr.status >= 200 && xhr.status < 300) {
                entry.progress = 100;
                entry.status = 'done';
                try {
                    const res = JSON.parse(xhr.responseText);
                    (res.uploaded || []).forEach((m) => this.items.unshift(m));
                } catch (_) { /* ignore parse errors */ }
            } else {
                entry.status = 'error';
            }
        };
        xhr.onerror = () => { entry.status = 'error'; };
        xhr.send(fd);
    },

    // ── Media Library tab ──
    loadLibrary() {
        if (this.libraryLoaded) return;
        this.libraryLoading = true;
        fetch(this.indexUrl + '?json=1', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then((r) => r.json())
            .then((res) => {
                this.libraryItems = res.data || [];
                this.libraryLoaded = true;
            })
            .catch(() => {})
            .finally(() => { this.libraryLoading = false; });
    },

    // ── From URL tab ──
    importFromUrl() {
        if (!this.urlValue) return;
        this.urlLoading = true;
        this.urlError = '';

        fetch(this.fromUrlUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': this.csrf,
            },
            body: JSON.stringify({ url: this.urlValue }),
        })
            .then(async (r) => {
                const data = await r.json().catch(() => ({}));
                if (!r.ok) throw new Error(data.message || 'Import failed');
                return data;
            })
            .then((data) => {
                if (data.media) {
                    this.items.unshift(data.media);
                    this.libraryItems.unshift(data.media);
                }
                this.urlValue = '';
                this.addOpen = false;
            })
            .catch((err) => { this.urlError = err.message; })
            .finally(() => { this.urlLoading = false; });
    },

    // ── Details modal ──
    openDetails(item) {
        this.current = item;
        this.form = {
            title: item.title || '',
            alt: item.alt || '',
            caption: item.caption || '',
            description: item.description || '',
        };
        this.saved = false;
        this.copied = false;
        this.detailsOpen = true;
    },

    saveDetails() {
        if (!this.current) return;
        this.saving = true;
        this.saved = false;

        fetch(this.current.update_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': this.csrf,
            },
            body: JSON.stringify({ _method: 'PUT', ...this.form }),
        })
            .then(async (r) => {
                const data = await r.json().catch(() => ({}));
                if (!r.ok) throw new Error(data.message || 'Save failed');
                return data;
            })
            .then((data) => {
                this.saved = true;
                if (data.media) this.syncItem(data.media);
                setTimeout(() => { this.saved = false; }, 2000);
            })
            .catch(() => {})
            .finally(() => { this.saving = false; });
    },

    syncItem(media) {
        const patch = (arr) => {
            const idx = arr.findIndex((m) => m.id === media.id);
            if (idx !== -1) arr[idx] = media;
        };
        patch(this.items);
        patch(this.libraryItems);
        this.current = media;
    },

    deleteCurrent() {
        if (!this.current) return;
        if (!window.confirm('{{ __("media.delete_confirm") }}')) return;

        fetch(this.current.destroy_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': this.csrf,
            },
            body: JSON.stringify({ _method: 'DELETE' }),
        })
            .then((r) => {
                if (!r.ok) throw new Error('Delete failed');
                const id = this.current.id;
                this.items = this.items.filter((m) => m.id !== id);
                this.libraryItems = this.libraryItems.filter((m) => m.id !== id);
                this.detailsOpen = false;
                this.current = null;
            })
            .catch(() => {});
    },

    copyUrl(url) {
        if (!url) return;
        navigator.clipboard.writeText(url).then(() => {
            this.copied = true;
            setTimeout(() => { this.copied = false; }, 2000);
        });
    },
}));

// ponytail: Alpine.start() one-time app-level, sengaja dibiarkan di sini — TIDAK di `turbo:load`.
// Aman: Alpine memakai MutationObserver pada document (lifecycle.js, startObservingMutations),
// sehingga node baru dari Turbo body-swap ter-init otomatis via onElAdded -> initTree.
// Tidak ada DOMContentLoaded/jQuery/editor init di resources/js yang perlu di-rebind.
Alpine.start();
