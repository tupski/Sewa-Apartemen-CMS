{{--
    _photos.blade.php
    ─────────────────────────────────────────────────────────────────────────────
    Photo upload / gallery section for the property form (create + edit).

    WORKS FOR BOTH CREATE AND EDIT
    ─────────────────────────────────────────────────────────────────────────────
    • Dropzone: drag-and-drop or click-to-select, multiple files, images only.
    • Client-side preview grid is shown immediately — no server round-trip until
      the main property form is submitted.
    • Each photo card has:
        - Thumbnail (object-cover)
        - ★/☆ star overlay (top-right) — marks the featured/primary image
        - ✕ delete button overlay (top-left)
        - Category <select> below the image
    • On form submit an @submit.prevent handler on the wrapping Alpine component
      (hoisted to the parent form via a custom event) injects the hidden inputs
      needed by saveGallery():

    FORM FIELDS SUBMITTED
    ─────────────────────────────────────────────────────────────────────────────
    photo_categories            JSON-encoded array of unique category names
                                (one entry per distinct category present)
    gallery_uploads[N][]        actual <input type="file"> for new photos,
                                N = index into photo_categories array
    photo_categories_update[id] new category for an already-saved photo
    deleted_photo_ids           comma-separated PropertyPhoto IDs to delete
    featured_image_id           media_id of the chosen featured photo
                                (only set when an existing photo is starred)

    NOTES
    ─────────────────────────────────────────────────────────────────────────────
    • Alpine.js is loaded globally via resources/js/app.js — no CDN needed.
    • No external dropzone library — native HTML5 drag-and-drop + FileReader.
    • Max file size: 10 MB. Accepted: jpeg, png, webp, gif.
    • For NEW photos (not yet saved): star selection is client-side only; since
      there is no media_id yet, featured_image_id is NOT sent for new photos.
      The user can re-star after the first save.
--}}

@php
    /** @var \App\Models\Property|null $property */
    $categoryOptions = ['Exterior', 'Building', 'Lobby', 'Lift', 'Bedroom', 'Toilet', 'Swimming Pool', 'Others'];

    // Existing saved photos serialised for Alpine initial state
    $savedPhotos = isset($property) && $property
        ? $property->photos->map(fn ($p) => [
            'id'       => $p->id,
            'media_id' => $p->media_id,
            'url'      => $p->media?->url ?? asset('images/placeholder.jpg'),
            'category' => $p->category ?? 'Others',
        ])->values()->toArray()
        : [];

    $featuredMediaId = isset($property) && $property ? ($property->featured_image_id ?? null) : null;
@endphp

{{-- ─── Alpine component root ─────────────────────────────────────────── --}}
<div
    id="photo-section"
    class="border-b border-gray-200 pb-8"
    x-data="photoGallery({
        existing: @json($savedPhotos),
        initialFeatured: @json($featuredMediaId),
        categories: @json($categoryOptions)
    })"
    x-init="init()"
>
    <h3 class="text-lg font-semibold text-gray-800 mb-1">Foto Properti</h3>
    <p class="text-sm text-gray-500 mb-4">
        Upload foto properti. Klik bintang (★) untuk menetapkan foto utama. Maksimal 10 MB per file.
    </p>

    {{-- ─── Dropzone ───────────────────────────────────────────────────── --}}
    <div
        id="photo-dropzone"
        class="relative flex flex-col items-center justify-center gap-3 rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 px-6 py-10 text-center transition-colors duration-150 cursor-pointer hover:border-blue-400 hover:bg-blue-50"
        :class="{
            'border-blue-500 bg-blue-50': isDragging,
            'border-blue-600 bg-blue-100 scale-[1.01]': isDragOver
        }"
        @click="$refs.fileInput.click()"
        @dragenter.prevent="isDragging = true; isDragOver = false"
        @dragover.prevent="isDragging = true; isDragOver = true"
        @dragleave.prevent="isDragging = false; isDragOver = false"
        @drop.prevent="isDragging = false; isDragOver = false; handleDrop($event)"
    >
        {{-- Upload icon --}}
        <div class="pointer-events-none flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 text-blue-500">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
            </svg>
        </div>
        <div class="pointer-events-none">
            <p class="text-sm font-medium text-gray-700">Drop photos here or <span class="text-blue-600 underline">click to select</span></p>
            <p class="mt-1 text-xs text-gray-400">JPEG, PNG, WebP, GIF · max 10 MB each</p>
        </div>
        {{-- Hidden real file input --}}
        <input
            type="file"
            multiple
            accept="image/jpeg,image/png,image/webp,image/gif"
            class="sr-only"
            x-ref="fileInput"
            @change="handleFileInput($event)"
        >
    </div>

    {{-- ─── Error banner ───────────────────────────────────────────────── --}}
    <div
        x-show="errors.length > 0"
        x-transition
        class="mt-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3"
    >
        <ul class="space-y-0.5">
            <template x-for="(err, i) in errors" :key="i">
                <li class="flex items-start gap-2 text-sm text-red-700">
                    <svg class="mt-0.5 h-4 w-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm-.75-4.75a.75.75 0 001.5 0v-4.5a.75.75 0 00-1.5 0v4.5zm.75-7a.75.75 0 100-1.5.75.75 0 000 1.5z" clip-rule="evenodd"/>
                    </svg>
                    <span x-text="err"></span>
                </li>
            </template>
        </ul>
        <button type="button" @click="errors = []" class="mt-1 text-xs text-red-500 hover:text-red-700 underline">Dismiss</button>
    </div>

    {{-- ─── Photo grid ─────────────────────────────────────────────────── --}}
    <div
        class="mt-5 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4"
        x-show="existingPhotos.length > 0 || newPhotos.length > 0"
        x-transition
    >

        {{-- ── Existing (saved) photos ── --}}
        <template x-for="photo in existingPhotos" :key="'ex-' + photo.id">
            <div class="group relative flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

                {{-- Image wrapper --}}
                <div class="relative aspect-[4/3] overflow-hidden bg-gray-100">
                    <img
                        :src="photo.url"
                        :alt="photo.category"
                        class="h-full w-full object-cover transition-transform duration-200 group-hover:scale-105"
                        loading="lazy"
                    >

                    {{-- ✕ Delete button — top-left --}}
                    <button
                        type="button"
                        @click.stop="removeExisting(photo)"
                        class="absolute left-1.5 top-1.5 flex h-6 w-6 items-center justify-center rounded-full bg-black/50 text-white opacity-0 transition-opacity group-hover:opacity-100 hover:bg-red-600 focus:opacity-100 focus:outline-none"
                        title="Hapus foto"
                        aria-label="Hapus foto"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>

                    {{-- ★ Star button — top-right --}}
                    <button
                        type="button"
                        @click.stop="setFeatured(photo)"
                        class="absolute right-1.5 top-1.5 flex h-7 w-7 items-center justify-center rounded-full bg-black/40 text-lg leading-none transition-all hover:scale-110 focus:outline-none focus:ring-2 focus:ring-yellow-400"
                        :class="featuredMediaId === photo.media_id ? 'text-yellow-400' : 'text-white/70 hover:text-yellow-300'"
                        :title="featuredMediaId === photo.media_id ? 'Foto utama' : 'Jadikan foto utama'"
                        :aria-label="featuredMediaId === photo.media_id ? 'Foto utama aktif' : 'Jadikan foto utama'"
                        :aria-pressed="featuredMediaId === photo.media_id"
                    >
                        <span x-text="featuredMediaId === photo.media_id ? '★' : '☆'"></span>
                    </button>

                    {{-- Featured badge --}}
                    <span
                        x-show="featuredMediaId === photo.media_id"
                        class="absolute bottom-1.5 left-1.5 rounded bg-yellow-400 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-yellow-900"
                    >Utama</span>
                </div>

                {{-- Category select --}}
                <div class="px-2 py-2">
                    <select
                        class="w-full rounded-md border border-gray-200 bg-white py-1.5 pl-2 pr-6 text-xs text-gray-700 shadow-sm focus:border-blue-400 focus:outline-none focus:ring-1 focus:ring-blue-400"
                        x-model="photo.category"
                        :aria-label="'Kategori untuk foto ' + photo.id"
                    >
                        <template x-for="opt in categories" :key="opt">
                            <option :value="opt" x-text="opt" :selected="photo.category === opt"></option>
                        </template>
                    </select>
                </div>

                {{-- Hidden: category update for this photo --}}
                <input type="hidden" :name="'photo_categories_update[' + photo.id + ']'" :value="photo.category">
            </div>
        </template>

        {{-- ── New (pending) photos ── --}}
        <template x-for="photo in newPhotos" :key="'new-' + photo.uid">
            <div class="group relative flex flex-col overflow-hidden rounded-xl border border-blue-200 bg-white shadow-sm ring-1 ring-blue-100">

                {{-- Image wrapper --}}
                <div class="relative aspect-[4/3] overflow-hidden bg-gray-100">
                    <img
                        :src="photo.preview"
                        :alt="photo.category"
                        class="h-full w-full object-cover transition-transform duration-200 group-hover:scale-105"
                    >

                    {{-- ✕ Delete button — top-left --}}
                    <button
                        type="button"
                        @click.stop="removeNew(photo)"
                        class="absolute left-1.5 top-1.5 flex h-6 w-6 items-center justify-center rounded-full bg-black/50 text-white opacity-0 transition-opacity group-hover:opacity-100 hover:bg-red-600 focus:opacity-100 focus:outline-none"
                        title="Hapus foto"
                        aria-label="Hapus foto baru"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>

                    {{-- ★ Star button — top-right (visual only, no media_id yet) --}}
                    <button
                        type="button"
                        @click.stop="setFeaturedNew(photo)"
                        class="absolute right-1.5 top-1.5 flex h-7 w-7 items-center justify-center rounded-full bg-black/40 text-lg leading-none transition-all hover:scale-110 focus:outline-none focus:ring-2 focus:ring-yellow-400"
                        :class="featuredNewUid === photo.uid ? 'text-yellow-400' : 'text-white/70 hover:text-yellow-300'"
                        :title="featuredNewUid === photo.uid ? 'Tandai sebagai foto utama (simpan dulu untuk menyimpan pilihan ini)' : 'Tandai sebagai foto utama'"
                        :aria-pressed="featuredNewUid === photo.uid"
                    >
                        <span x-text="featuredNewUid === photo.uid ? '★' : '☆'"></span>
                    </button>

                    {{-- "New" badge --}}
                    <span class="absolute bottom-1.5 left-1.5 rounded bg-blue-500 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-white">Baru</span>
                </div>

                {{-- Category select --}}
                <div class="px-2 py-2">
                    <select
                        class="w-full rounded-md border border-gray-200 bg-white py-1.5 pl-2 pr-6 text-xs text-gray-700 shadow-sm focus:border-blue-400 focus:outline-none focus:ring-1 focus:ring-blue-400"
                        x-model="photo.category"
                        :aria-label="'Kategori untuk foto baru'"
                    >
                        <template x-for="opt in categories" :key="opt">
                            <option :value="opt" x-text="opt" :selected="photo.category === opt"></option>
                        </template>
                    </select>
                </div>
            </div>
        </template>
    </div>

    {{-- ─── Empty state ─────────────────────────────────────────────────── --}}
    <p
        x-show="existingPhotos.length === 0 && newPhotos.length === 0"
        x-transition
        class="mt-4 text-center text-sm text-gray-400"
    >Belum ada foto. Upload foto di atas.</p>

    {{-- ─── Hidden submit-time inputs (injected by prepareSubmit) ─────── --}}
    {{--
        These are populated by prepareSubmit() right before the form submits.
        They live here so they are inside the same <form> element.
    --}}
    <div id="photo-submit-container" aria-hidden="true"></div>

</div>{{-- end x-data --}}


@push('scripts')
<script>
/**
 * photoGallery Alpine component
 *
 * Config object:
 *   existing        – array of {id, media_id, url, category} from server
 *   initialFeatured – current featured_image_id (media ID) or null
 *   categories      – ordered array of category option strings
 */
document.addEventListener('alpine:init', function () {
    Alpine.data('photoGallery', function (config) {
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
            /**
             * Called in the form's capture-phase submit listener.
             * Injects all photo-related hidden inputs + real file inputs into
             * #photo-submit-container so the browser includes them in the
             * multipart/form-data body.
             *
             * Field mapping:
             *   photo_categories            JSON array of distinct categories
             *   gallery_uploads[N][]        file input per category index N
             *   deleted_photo_ids           comma-separated existing photo IDs
             *   photo_categories_update[id] already handled by x-model hidden inputs in the DOM
             *   featured_image_id           media_id of chosen photo (if existing)
             */
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
});
</script>
@endpush
