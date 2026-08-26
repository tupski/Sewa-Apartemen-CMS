{{--
    _photos.blade.php
    ─────────────────────────────────────────────────────────────────────────────
    Photo management section for the property form (create + edit).

    WORKS FOR BOTH CREATE AND EDIT
    ─────────────────────────────────────────────────────────────────────────────
    • "Add Photos" button opens a modal with two tabs:
        - Gallery: browse existing media library (AJAX, supports search + pagination)
        - Upload:  drag-and-drop / click-to-select new files (images only)
    • Selected photos appear in a grid with:
        - Thumbnail (object-cover, aspect 4:3)
        - Primary star overlay (★ top-right) — marks the featured/main photo
        - Delete button overlay (✕ top-left)
        - Category <select> below the image
    • On form submit, prepareSubmit() injects hidden inputs:

    FORM FIELDS SUBMITTED
    ─────────────────────────────────────────────────────────────────────────────
    gallery_media_ids[mediaId]      category of a media-library-picked photo
    photo_categories                JSON array of categories for new uploads
    gallery_uploads[N][]            file input(s) for new uploaded photos (N = category index)
    photo_categories_update[id]     updated category for an already-saved photo
    deleted_photo_ids               comma-separated PropertyPhoto IDs to delete
    featured_image_id               media_id of the primary photo

    NOTES
    ─────────────────────────────────────────────────────────────────────────────
    • Alpine.js is loaded globally via resources/js/app.js — no CDN needed.
    • The photoGallery Alpine component is registered in app.js (before Alpine.start()).
    • Max file size: 10 MB. Accepted: jpeg, png, webp, gif.
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
        existing:       @json($savedPhotos),
        initialFeatured: @json($featuredMediaId),
        categories:     @json($categoryOptions),
        mediaIndexUrl:  '{{ route('admin.media.index') }}',
        mediaUploadUrl: '{{ route('admin.media.upload') }}',
        csrf:           '{{ csrf_token() }}',
    })"
    x-init="init()"
>
    {{-- ─── Section header + Add Photos button ────────────────────────── --}}
    <div class="flex items-center justify-between mb-4">
        <div>
            <h3 class="text-lg font-semibold text-gray-800">Foto Properti</h3>
            <p class="text-sm text-gray-500 mt-0.5">
                Klik bintang (★) untuk menetapkan foto utama. Maksimal 10 MB per file.
            </p>
        </div>
        <button
            type="button"
            @click="openPicker()"
            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Tambah Foto
        </button>
    </div>

    {{-- ─── Error banner ───────────────────────────────────────────────── --}}
    <div
        x-show="errors.length > 0"
        x-transition
        class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3"
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
        class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4"
        x-show="existingPhotos.length > 0 || libraryPhotos.length > 0 || newPhotos.length > 0"
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
                        class="absolute left-1.5 top-1.5 flex h-6 w-6 items-center justify-center rounded-full bg-black/50 text-white opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-600"
                        title="Hapus foto"
                        aria-label="Hapus foto"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>

                    {{-- ★ Star / featured button — top-right --}}
                    <button
                        type="button"
                        @click.stop="setFeatured(photo)"
                        :title="featuredMediaId === photo.media_id ? 'Foto utama (klik untuk hapus)' : 'Jadikan foto utama'"
                        :aria-label="featuredMediaId === photo.media_id ? 'Foto utama aktif' : 'Jadikan foto utama'"
                        class="absolute right-1.5 top-1.5 flex h-6 w-6 items-center justify-center rounded-full transition-all"
                        :class="featuredMediaId === photo.media_id
                            ? 'bg-yellow-400 text-white shadow-md'
                            : 'bg-black/50 text-white opacity-0 group-hover:opacity-100 hover:bg-yellow-400'"
                    >
                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                    </button>

                    {{-- Primary badge --}}
                    <div
                        x-show="featuredMediaId === photo.media_id"
                        class="absolute bottom-1.5 left-1.5 rounded-full bg-yellow-400 px-2 py-0.5 text-[10px] font-semibold text-white shadow"
                    >Utama</div>
                </div>

                {{-- Category select + hidden update input --}}
                <div class="px-2.5 py-2">
                    <select
                        :name="'photo_categories_update[' + photo.id + ']'"
                        x-model="photo.category"
                        class="w-full rounded-md border border-gray-200 bg-gray-50 px-2 py-1 text-xs text-gray-700 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                        :aria-label="'Kategori foto ' + (photo.category || '')"
                    >
                        <template x-for="cat in categories" :key="cat">
                            <option :value="cat" x-text="cat"></option>
                        </template>
                    </select>
                </div>
            </div>
        </template>

        {{-- ── Library-picked photos (not yet saved) ── --}}
        <template x-for="photo in libraryPhotos" :key="'lib-' + photo.media_id">
            <div class="group relative flex flex-col overflow-hidden rounded-xl border-2 border-blue-300 bg-white shadow-sm">

                {{-- Image wrapper --}}
                <div class="relative aspect-[4/3] overflow-hidden bg-gray-100">
                    <img
                        :src="photo.url"
                        :alt="photo.filename"
                        class="h-full w-full object-cover transition-transform duration-200 group-hover:scale-105"
                        loading="lazy"
                    >

                    {{-- "New" badge --}}
                    <div class="absolute top-1.5 left-1.5 rounded-full bg-blue-500 px-2 py-0.5 text-[10px] font-semibold text-white shadow">Baru</div>

                    {{-- ✕ Delete button --}}
                    <button
                        type="button"
                        @click.stop="removeLibraryPhoto(photo)"
                        class="absolute right-1.5 top-1.5 flex h-6 w-6 items-center justify-center rounded-full bg-black/50 text-white opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-600"
                        title="Hapus foto"
                        aria-label="Hapus foto dari pilihan"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>

                    {{-- ★ Star button --}}
                    <button
                        type="button"
                        @click.stop="setFeaturedLibrary(photo)"
                        :title="featuredMediaId === photo.media_id ? 'Foto utama (klik untuk hapus)' : 'Jadikan foto utama'"
                        class="absolute bottom-1.5 right-1.5 flex h-6 w-6 items-center justify-center rounded-full transition-all"
                        :class="featuredMediaId === photo.media_id
                            ? 'bg-yellow-400 text-white shadow-md'
                            : 'bg-black/50 text-white opacity-0 group-hover:opacity-100 hover:bg-yellow-400'"
                    >
                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                    </button>

                    {{-- Primary badge --}}
                    <div
                        x-show="featuredMediaId === photo.media_id"
                        class="absolute bottom-1.5 left-1.5 rounded-full bg-yellow-400 px-2 py-0.5 text-[10px] font-semibold text-white shadow"
                    >Utama</div>
                </div>

                {{-- Category select --}}
                <div class="px-2.5 py-2">
                    <select
                        x-model="photo.category"
                        class="w-full rounded-md border border-gray-200 bg-gray-50 px-2 py-1 text-xs text-gray-700 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                        :aria-label="'Kategori foto'"
                    >
                        <template x-for="cat in categories" :key="cat">
                            <option :value="cat" x-text="cat"></option>
                        </template>
                    </select>
                </div>
            </div>
        </template>

        {{-- ── New (uploaded) photos ── --}}
        <template x-for="photo in newPhotos" :key="'new-' + photo.uid">
            <div class="group relative flex flex-col overflow-hidden rounded-xl border-2 border-dashed border-green-300 bg-white shadow-sm">

                {{-- Image wrapper --}}
                <div class="relative aspect-[4/3] overflow-hidden bg-gray-100">
                    <img
                        :src="photo.preview"
                        :alt="photo.file ? photo.file.name : ''"
                        class="h-full w-full object-cover transition-transform duration-200 group-hover:scale-105"
                    >

                    {{-- "Upload" badge --}}
                    <div class="absolute top-1.5 left-1.5 rounded-full bg-green-500 px-2 py-0.5 text-[10px] font-semibold text-white shadow">Upload</div>

                    {{-- ✕ Delete button --}}
                    <button
                        type="button"
                        @click.stop="removeNew(photo)"
                        class="absolute right-1.5 top-1.5 flex h-6 w-6 items-center justify-center rounded-full bg-black/50 text-white opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-600"
                        title="Hapus foto"
                        aria-label="Hapus foto yang diupload"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>

                    {{-- ★ Star button --}}
                    <button
                        type="button"
                        @click.stop="setFeaturedNew(photo)"
                        :title="featuredNewUid === photo.uid ? 'Foto utama (klik untuk hapus)' : 'Jadikan foto utama'"
                        class="absolute bottom-1.5 right-1.5 flex h-6 w-6 items-center justify-center rounded-full transition-all"
                        :class="featuredNewUid === photo.uid
                            ? 'bg-yellow-400 text-white shadow-md'
                            : 'bg-black/50 text-white opacity-0 group-hover:opacity-100 hover:bg-yellow-400'"
                    >
                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                    </button>

                    {{-- Primary badge --}}
                    <div
                        x-show="featuredNewUid === photo.uid"
                        class="absolute bottom-1.5 left-1.5 rounded-full bg-yellow-400 px-2 py-0.5 text-[10px] font-semibold text-white shadow"
                    >Utama</div>
                </div>

                {{-- Category select --}}
                <div class="px-2.5 py-2">
                    <select
                        x-model="photo.category"
                        class="w-full rounded-md border border-gray-200 bg-gray-50 px-2 py-1 text-xs text-gray-700 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                        :aria-label="'Kategori foto yang diupload'"
                    >
                        <template x-for="cat in categories" :key="cat">
                            <option :value="cat" x-text="cat"></option>
                        </template>
                    </select>
                </div>
            </div>
        </template>

    </div>{{-- end photo grid --}}

    {{-- ─── Empty state ─────────────────────────────────────────────────── --}}
    <p
        x-show="existingPhotos.length === 0 && libraryPhotos.length === 0 && newPhotos.length === 0"
        x-transition
        class="mt-4 rounded-xl border-2 border-dashed border-gray-200 bg-gray-50 py-10 text-center text-sm text-gray-400"
    >
        <svg class="mx-auto mb-2 h-8 w-8 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M13.5 12h.008v.008H13.5V12zm-9 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v10.5a1.5 1.5 0 001.5 1.5z"/>
        </svg>
        Belum ada foto. Klik "Tambah Foto" untuk menambahkan foto properti.
    </p>

    {{-- ─── Hidden submit-time inputs (injected by prepareSubmit) ─────── --}}
    <div id="photo-submit-container" aria-hidden="true"></div>

    {{-- ─── Add Photos Modal ──────────────────────────────────────────── --}}
    {{-- Uses x-teleport to render at body level so z-index stacking is clean --}}
    <template x-teleport="body">
        <div
            x-show="pickerOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
            @click.self="closePicker()"
            @keydown.escape.window="closePicker()"
            role="dialog"
            aria-modal="true"
            aria-label="Tambah Foto"
            style="display: none"
        >
            <div
                class="relative w-full max-w-4xl max-h-[90vh] flex flex-col bg-white rounded-2xl shadow-2xl overflow-hidden"
                @click.stop
            >
                {{-- Modal header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <h2 class="text-lg font-semibold text-gray-900">Tambah Foto Properti</h2>
                    <button
                        type="button"
                        @click="closePicker()"
                        class="flex h-8 w-8 items-center justify-center rounded-full text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition"
                        aria-label="Tutup"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Tab switcher --}}
                <div class="flex border-b border-gray-200 flex-shrink-0 px-6">
                    <button
                        type="button"
                        @click="pickerTab = 'gallery'"
                        class="px-4 py-3 text-sm font-medium border-b-2 transition -mb-px focus:outline-none"
                        :class="pickerTab === 'gallery'
                            ? 'border-blue-600 text-blue-600'
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    >
                        <svg class="inline w-4 h-4 mr-1.5 -mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                        Galeri Media
                    </button>
                    <button
                        type="button"
                        @click="pickerTab = 'upload'"
                        class="ml-2 px-4 py-3 text-sm font-medium border-b-2 transition -mb-px focus:outline-none"
                        :class="pickerTab === 'upload'
                            ? 'border-blue-600 text-blue-600'
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    >
                        <svg class="inline w-4 h-4 mr-1.5 -mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                        </svg>
                        Upload Baru
                    </button>
                </div>

                {{-- ─── TAB: Gallery ──────────────────────────────────── --}}
                <div x-show="pickerTab === 'gallery'" class="flex flex-col flex-1 overflow-hidden">

                    {{-- Search bar --}}
                    <div class="px-6 py-3 border-b border-gray-100 flex-shrink-0">
                        <div class="flex gap-2">
                            <div class="relative flex-1">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                                </svg>
                                <input
                                    type="text"
                                    x-model="pickerSearch"
                                    @keydown.enter.prevent="searchPickerMedia()"
                                    placeholder="Cari gambar..."
                                    class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                            </div>
                            <button
                                type="button"
                                @click="searchPickerMedia()"
                                class="px-4 py-2 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition font-medium"
                            >Cari</button>
                        </div>
                        <p class="mt-1.5 text-xs text-gray-400">
                            Pilih satu atau lebih gambar dari library.
                            <span x-show="Object.keys(pickerSelected).length > 0" class="ml-1 font-semibold text-blue-600">
                                <span x-text="Object.keys(pickerSelected).length"></span> dipilih
                            </span>
                        </p>
                    </div>

                    {{-- Media grid --}}
                    <div class="flex-1 overflow-y-auto px-6 py-4">

                        {{-- Loading state --}}
                        <div x-show="pickerLoading && pickerItems.length === 0" class="flex items-center justify-center py-16">
                            <svg class="animate-spin h-8 w-8 text-blue-500" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </div>

                        {{-- Empty state --}}
                        <div x-show="!pickerLoading && pickerLoaded && pickerItems.length === 0" class="py-16 text-center text-sm text-gray-400">
                            Tidak ada gambar ditemukan.
                        </div>

                        {{-- Items grid --}}
                        <div
                            x-show="pickerItems.length > 0"
                            class="grid grid-cols-3 gap-3 sm:grid-cols-4 md:grid-cols-5"
                        >
                            <template x-for="item in pickerItems" :key="item.id">
                                <div
                                    class="group relative aspect-square overflow-hidden rounded-lg cursor-pointer border-2 transition-all"
                                    :class="isPickerSelected(item)
                                        ? 'border-blue-500 ring-2 ring-blue-200'
                                        : alreadyAttachedMediaIds()[String(item.id)]
                                            ? 'border-gray-200 opacity-50 cursor-not-allowed'
                                            : 'border-transparent hover:border-blue-300'"
                                    @click="!alreadyAttachedMediaIds()[String(item.id)] && togglePickerSelect(item)"
                                    :title="alreadyAttachedMediaIds()[String(item.id)] ? 'Sudah ditambahkan' : (item.original_filename || item.filename)"
                                    :aria-pressed="isPickerSelected(item)"
                                    role="checkbox"
                                >
                                    <img
                                        :src="item.thumbnail_url || item.url"
                                        :alt="item.original_filename || item.filename"
                                        class="h-full w-full object-cover"
                                        loading="lazy"
                                    >

                                    {{-- Checkmark overlay --}}
                                    <div
                                        x-show="isPickerSelected(item)"
                                        class="absolute inset-0 bg-blue-500/20 flex items-center justify-center"
                                    >
                                        <div class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-500 shadow">
                                            <svg class="h-3.5 w-3.5 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                            </svg>
                                        </div>
                                    </div>

                                    {{-- Already added overlay --}}
                                    <div
                                        x-show="alreadyAttachedMediaIds()[String(item.id)]"
                                        class="absolute inset-0 bg-gray-400/30 flex items-center justify-center"
                                    >
                                        <span class="rounded bg-gray-700/80 px-1.5 py-0.5 text-[9px] text-white font-medium">Sudah ada</span>
                                    </div>

                                    {{-- Filename tooltip on hover --}}
                                    <div class="absolute bottom-0 left-0 right-0 bg-black/60 px-1.5 py-1 text-[10px] text-white truncate opacity-0 group-hover:opacity-100 transition-opacity">
                                        <span x-text="item.original_filename || item.filename"></span>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Load more --}}
                        <div x-show="pickerPage < pickerLastPage" class="mt-4 text-center">
                            <button
                                type="button"
                                @click="loadMorePickerMedia()"
                                :disabled="pickerLoading"
                                class="inline-flex items-center gap-2 px-4 py-2 text-sm text-blue-600 border border-blue-200 rounded-lg hover:bg-blue-50 disabled:opacity-50 transition"
                            >
                                <svg x-show="pickerLoading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Muat Lebih Banyak
                            </button>
                        </div>
                    </div>

                    {{-- Gallery footer --}}
                    <div class="flex items-center justify-between px-6 py-4 border-t border-gray-100 bg-gray-50 flex-shrink-0">
                        <span class="text-sm text-gray-500" x-show="Object.keys(pickerSelected).length > 0">
                            <span x-text="Object.keys(pickerSelected).length"></span> gambar dipilih
                        </span>
                        <span class="text-sm text-gray-400" x-show="Object.keys(pickerSelected).length === 0">
                            Pilih gambar dari library di atas
                        </span>
                        <div class="flex gap-3">
                            <button
                                type="button"
                                @click="closePicker()"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition"
                            >Batal</button>
                            <button
                                type="button"
                                @click="confirmPickerSelection()"
                                :disabled="Object.keys(pickerSelected).length === 0"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed transition"
                            >
                                Tambahkan
                                <span x-show="Object.keys(pickerSelected).length > 0">
                                    (<span x-text="Object.keys(pickerSelected).length"></span>)
                                </span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- ─── TAB: Upload ────────────────────────────────────── --}}
                <div x-show="pickerTab === 'upload'" class="flex flex-col flex-1 overflow-hidden">

                    <div class="flex-1 flex flex-col items-center justify-center px-6 py-8 overflow-y-auto">
                        {{-- Drag & drop zone --}}
                        <div
                            class="w-full max-w-lg flex flex-col items-center justify-center gap-3 rounded-2xl border-2 border-dashed border-gray-300 bg-gray-50 px-8 py-14 text-center transition-colors duration-150 cursor-pointer hover:border-blue-400 hover:bg-blue-50"
                            :class="{
                                'border-blue-500 bg-blue-50': isDragging,
                                'border-blue-600 bg-blue-100 scale-[1.01]': isDragOver
                            }"
                            @click="$refs.pickerFileInput.click()"
                            @dragenter.prevent="isDragging = true; isDragOver = false"
                            @dragover.prevent="isDragging = true; isDragOver = true"
                            @dragleave.prevent="isDragging = false; isDragOver = false"
                            @drop.prevent="isDragging = false; isDragOver = false; handlePickerDrop($event)"
                            role="button"
                            tabindex="0"
                            @keydown.enter.prevent="$refs.pickerFileInput.click()"
                            @keydown.space.prevent="$refs.pickerFileInput.click()"
                            aria-label="Upload foto baru"
                        >
                            <div class="pointer-events-none flex h-14 w-14 items-center justify-center rounded-full bg-blue-100 text-blue-500">
                                <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                                </svg>
                            </div>
                            <div class="pointer-events-none">
                                <p class="text-sm font-medium text-gray-700">
                                    Seret foto ke sini, atau <span class="text-blue-600 underline">klik untuk memilih</span>
                                </p>
                                <p class="mt-1 text-xs text-gray-400">JPEG, PNG, WebP, GIF • Maks. 10 MB per file</p>
                            </div>
                        </div>

                        {{-- Hidden file input --}}
                        <input
                            type="file"
                            multiple
                            accept="image/jpeg,image/png,image/webp,image/gif"
                            class="sr-only"
                            x-ref="pickerFileInput"
                            @change="handlePickerFileInput($event)"
                        >
                    </div>

                    {{-- Upload tab footer --}}
                    <div class="flex justify-end px-6 py-4 border-t border-gray-100 bg-gray-50 flex-shrink-0">
                        <button
                            type="button"
                            @click="closePicker()"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition"
                        >Tutup</button>
                    </div>
                </div>

            </div>{{-- end modal panel --}}
        </div>{{-- end modal backdrop --}}
    </template>{{-- end x-teleport --}}

</div>{{-- end x-data --}}

{{--
    The photoGallery Alpine component is registered in resources/js/app.js
    (before Alpine.start()), so it is guaranteed to exist when this x-data
    initialises — including after Turbo body-swaps. It must NOT be redefined
    here in a @push('scripts') block, because that block runs AFTER
    Alpine.start() and the registration would never take effect.
--}}
