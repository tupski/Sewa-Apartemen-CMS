{{--
    _photos.blade.php
    ─────────────────────────────────────────────────────────────────────────────
    Redesigned photo upload section for the property edit form.

    HOW IT WORKS
    ─────────────────────────────────────────────────────────────────────────────
    • Drag-and-drop / click-to-browse dropzone (vanilla JS, no npm packages).
    • Files are previewed immediately via FileReader (no server round-trip until
      the main property form is submitted).
    • On submit, each pending file is injected as a real <input type="file">
      named gallery_uploads[{catIndex}][] so the existing saveGallery() method
      in PropertyController picks them up unchanged.
    • Existing photos (already saved) display in the same grid. Their category
      can be changed via a <select>; the choice is persisted in a hidden input
      photo_categories_update[{photo_id}] that the controller can consume.
    • The ★ / ☆ star marks the primary photo. It writes the media_id of the
      chosen photo into the existing hidden input #featured-image-id (present
      in edit.blade.php) so the controller saves it as featured_image_id on
      the Property model.
    • Deleting a saved photo appends its ID to deleted_photo_ids (existing field).
    • Deleting a pending (not-yet-uploaded) photo just removes it from the queue.

    FIELDS SUBMITTED
    ─────────────────────────────────────────────────────────────────────────────
    photo_categories            JSON array – category names (existing field)
    gallery_uploads[N][]        files for category at index N (existing field)
    deleted_photo_ids           comma-separated IDs (existing field)
    photo_categories_update[id] new category string per saved photo (new field)
    featured_image_id           media_id of the primary photo (existing field)
--}}

@php
    /** @var \App\Models\Property|null $property */
    $photoCategories = $property?->photoCategories() ?? \App\Models\Property::DEFAULT_PHOTO_CATEGORIES;

    // Fixed category options shown in the per-photo dropdown
    $categoryOptions = [
        'Exterior', 'Building', 'Lobby', 'Lift',
        'Bedroom', 'Toilet', 'Swimming Pool', 'Others',
    ];

    // Existing saved photos – flat list with all the info JS needs
    $savedPhotos = $property
        ? $property->photos->map(fn ($p) => [
            'id'       => $p->id,
            'media_id' => $p->media_id,
            'url'      => $p->media?->url ?? '',
            'category' => $p->category ?? '',
        ])->values()->toArray()
        : [];

    $featuredMediaId = $property?->featured_image_id;
@endphp

{{-- ── Inline styles (scoped to this component only) ── --}}
@push('head')
<style>
    /* Dropzone states */
    #photo-dropzone.dz-active  { border-color: #3b82f6; background-color: #eff6ff; }
    #photo-dropzone.dz-over    { border-color: #2563eb; background-color: #dbeafe; transform: scale(1.01); }

    /* Photo card star button */
    .photo-card .btn-star       { transition: color .15s, transform .15s; }
    .photo-card .btn-star:hover { transform: scale(1.25); }
    .photo-card .btn-star.starred { color: #f59e0b; }

    /* Upload progress overlay */
    .photo-card .upload-overlay {
        position: absolute; inset: 0;
        display: flex; align-items: center; justify-content: center;
        background: rgba(0,0,0,.45); border-radius: .5rem;
    }
    .photo-card .upload-overlay .spinner {
        width: 2rem; height: 2rem;
        border: 3px solid rgba(255,255,255,.4);
        border-top-color: #fff;
        border-radius: 50%;
        animation: spin .7s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* Category select inside card */
    .photo-card select {
        font-size: .72rem;
        padding: .2rem .35rem;
        line-height: 1.3;
        max-width: 100%;
    }
</style>
@endpush

<div class="border-b border-gray-200 pb-8" id="photo-section">
    <h3 class="text-lg font-semibold text-gray-800 mb-1">Foto Properti</h3>
    <p class="text-sm text-gray-500 mb-5">
        Drag &amp; drop atau klik area di bawah untuk menambah foto. Atur kategori dan tandai foto utama (★).
    </p>

    {{-- ── Hidden fields consumed by PropertyController::saveGallery() ── --}}
    <input type="hidden" name="photo_categories"  id="photo-categories-input"
           value="{{ json_encode($photoCategories) }}">
    <input type="hidden" name="deleted_photo_ids" id="deleted-photo-ids" value="">

    {{-- ── Dropzone ── --}}
    <div id="photo-dropzone"
         role="button" tabindex="0" aria-label="Upload area: drag and drop photos or click to browse"
         class="relative flex flex-col items-center justify-center gap-3
                border-2 border-dashed border-gray-300 rounded-xl
                bg-gray-50 hover:bg-blue-50 hover:border-blue-400
                transition-all duration-200 cursor-pointer
                px-6 py-10 mb-6 select-none">

        {{-- cloud-upload icon --}}
        <svg class="w-12 h-12 text-gray-400 pointer-events-none" fill="none" stroke="currentColor"
             stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M12 16.5V9.75m0 0-3 3m3-3 3 3M6.75 19.5a4.5 4.5 0 0 1-1.632-8.683
                     1.942 1.942 0 0 1 .023-.095A5.25 5.25 0 0 1 17.25 9a5.25 5.25 0
                     0 1 .9 10.413" />
        </svg>

        <div class="text-center pointer-events-none">
            <p class="text-sm font-medium text-gray-700">
                Drag &amp; drop foto di sini atau
                <span class="text-blue-600 font-semibold">klik untuk memilih</span>
            </p>
            <p class="text-xs text-gray-400 mt-1">JPEG, PNG, WebP — maks. 10 MB per file</p>
        </div>

        {{-- Progress bar shown during batch upload (while form processes) --}}
        <div id="dz-progress-bar"
             class="hidden absolute bottom-0 left-0 h-1 rounded-b-xl bg-blue-500 transition-all duration-300"
             style="width: 0%"></div>

        {{-- Hidden real file input --}}
        <input type="file" id="photo-file-input"
               accept="image/jpeg,image/png,image/webp,image/gif"
               multiple class="sr-only" aria-hidden="true" tabindex="-1">
    </div>

    {{-- ── Photo grid ── --}}
    <div id="photo-grid"
         class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4"
         aria-label="Photo gallery">
        {{-- Existing saved photos rendered server-side, JS will also manage them --}}
        @foreach ($savedPhotos as $photo)
        <div class="photo-card relative rounded-xl overflow-hidden border border-gray-200 shadow-sm bg-white"
             data-photo-id="{{ $photo['id'] }}"
             data-media-id="{{ $photo['media_id'] }}"
             data-saved="1">

            {{-- Thumbnail --}}
            <div class="aspect-square bg-gray-100">
                <img src="{{ $photo['url'] }}"
                     alt="Property photo"
                     class="w-full h-full object-cover"
                     loading="lazy">
            </div>

            {{-- Star button (top-right) --}}
            <button type="button"
                    class="btn-star absolute top-1.5 right-1.5 z-10
                           w-7 h-7 flex items-center justify-center
                           bg-black/40 hover:bg-black/60 rounded-full
                           text-white text-base leading-none
                           {{ $photo['media_id'] == $featuredMediaId ? 'starred' : '' }}"
                    aria-label="Set as primary photo"
                    title="Jadikan foto utama">
                {{ $photo['media_id'] == $featuredMediaId ? '★' : '☆' }}
            </button>

            {{-- Delete button (top-left) --}}
            <button type="button"
                    class="btn-delete absolute top-1.5 left-1.5 z-10
                           w-7 h-7 flex items-center justify-center
                           bg-red-500/80 hover:bg-red-600 rounded-full
                           text-white text-sm leading-none font-bold"
                    aria-label="Delete photo"
                    title="Hapus foto">
                &times;
            </button>

            {{-- Category select (below image) --}}
            <div class="px-2 py-1.5 bg-white border-t border-gray-100">
                <select class="cat-select w-full rounded border-gray-300 text-gray-700
                               focus:ring-blue-500 focus:border-blue-500"
                        aria-label="Photo category">
                    @foreach ($categoryOptions as $opt)
                        <option value="{{ $opt }}"
                            {{ $photo['category'] === $opt ? 'selected' : '' }}>
                            {{ $opt }}
                        </option>
                    @endforeach
                </select>
                {{-- Hidden input carrying the (possibly updated) category for this saved photo --}}
                <input type="hidden"
                       name="photo_categories_update[{{ $photo['id'] }}]"
                       class="cat-hidden"
                       value="{{ $photo['category'] }}">
            </div>
        </div>
        @endforeach
        {{-- Pending (not-yet-uploaded) photo cards are inserted here by JS --}}
    </div>

    {{-- Empty state message --}}
    @if (empty($savedPhotos))
    <p id="photo-empty-msg" class="text-sm text-gray-400 text-center mt-4">
        Belum ada foto. Upload foto di atas.
    </p>
    @else
    <p id="photo-empty-msg" class="hidden text-sm text-gray-400 text-center mt-4">
        Belum ada foto. Upload foto di atas.
    </p>
    @endif
</div>

@push('scripts')
<script>
(function () {
    'use strict';

    /* ── Config ─────────────────────────────────────────────────────────────── */
    var MAX_FILE_SIZE_MB = 10;
    var ALLOWED_TYPES    = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    var CATEGORY_OPTIONS = @json($categoryOptions);
    var DEFAULT_CATEGORY = 'Others';

    /* ── DOM refs ────────────────────────────────────────────────────────────── */
    var dropzone      = document.getElementById('photo-dropzone');
    var fileInput     = document.getElementById('photo-file-input');
    var grid          = document.getElementById('photo-grid');
    var emptyMsg      = document.getElementById('photo-empty-msg');
    var deletedInput  = document.getElementById('deleted-photo-ids');

    // Featured image hidden input is rendered by edit.blade.php as #featured-image-id
    // Fall back to a local hidden input if the section is used standalone.
    var featuredInput = document.getElementById('featured-image-id');
    if (!featuredInput) {
        featuredInput = document.createElement('input');
        featuredInput.type = 'hidden';
        featuredInput.name = 'featured_image_id';
        featuredInput.id   = 'featured-image-id';
        dropzone.parentNode.insertBefore(featuredInput, dropzone);
    }

    /* ── State ───────────────────────────────────────────────────────────────── */
    // Pending files: array of { file: File, objectUrl: string, category: string, inputEl: HTMLInputElement }
    var pendingFiles = [];

    /* ─────────────────────────────────────────────────────────────────────────
       DROPZONE INTERACTIONS
    ───────────────────────────────────────────────────────────────────────── */
    dropzone.addEventListener('click', function (e) {
        if (e.target === fileInput) return;
        fileInput.click();
    });

    dropzone.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); fileInput.click(); }
    });

    dropzone.addEventListener('dragover', function (e) {
        e.preventDefault();
        dropzone.classList.add('dz-over');
    });

    dropzone.addEventListener('dragleave', function (e) {
        if (!dropzone.contains(e.relatedTarget)) {
            dropzone.classList.remove('dz-over');
        }
    });

    dropzone.addEventListener('drop', function (e) {
        e.preventDefault();
        dropzone.classList.remove('dz-over');
        handleFiles(e.dataTransfer.files);
    });

    fileInput.addEventListener('change', function () {
        handleFiles(fileInput.files);
        // Reset so the same file can be re-selected if removed
        fileInput.value = '';
    });

    /* ─────────────────────────────────────────────────────────────────────────
       FILE HANDLING
    ───────────────────────────────────────────────────────────────────────── */
    function handleFiles(fileList) {
        var files = Array.from(fileList);
        var errors = [];

        files.forEach(function (file) {
            if (!ALLOWED_TYPES.includes(file.type)) {
                errors.push(file.name + ': tipe file tidak didukung');
                return;
            }
            if (file.size > MAX_FILE_SIZE_MB * 1024 * 1024) {
                errors.push(file.name + ': ukuran melebihi ' + MAX_FILE_SIZE_MB + ' MB');
                return;
            }
            addPendingFile(file);
        });

        if (errors.length) {
            alert('Beberapa file dilewati:\n' + errors.join('\n'));
        }

        updateEmptyState();
    }

    function addPendingFile(file) {
        var objectUrl = URL.createObjectURL(file);

        // Create a real <input type="file"> that will be submitted with the form.
        // The name is set when the card's category is chosen (or defaults to 'Others').
        // We inject into the form's hidden container so it submits properly.
        var hiddenFileInput = createFileInput(file, DEFAULT_CATEGORY);

        var entry = {
            file:      file,
            objectUrl: objectUrl,
            category:  DEFAULT_CATEGORY,
            inputEl:   hiddenFileInput,
        };
        pendingFiles.push(entry);

        var card = buildPendingCard(entry);
        entry.cardEl = card;
        grid.appendChild(card);

        // Animate in
        requestAnimationFrame(function () {
            card.classList.remove('opacity-0', 'scale-95');
        });
    }

    /**
     * Create a hidden file input whose DataTransfer carries the given File.
     * This is the only reliable way to programmatically place a File object
     * into a named <input type="file"> for form submission.
     */
    function createFileInput(file, category) {
        var catIndex = getCategoryIndex(category);
        var input = document.createElement('input');
        input.type    = 'file';
        input.name    = 'gallery_uploads[' + catIndex + '][]';
        input.classList.add('sr-only');
        input.setAttribute('aria-hidden', 'true');

        // Use DataTransfer to assign the file to the input
        var dt = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;

        // Append near the dropzone so it's inside the form
        dropzone.parentNode.appendChild(input);
        return input;
    }

    /**
     * Returns the index of the category in photo_categories array,
     * appending it if it doesn't exist (so the controller can map index→category).
     */
    function getCategoryIndex(category) {
        var categories = getCategories();
        var idx = categories.indexOf(category);
        if (idx === -1) {
            categories.push(category);
            saveCategories(categories);
            idx = categories.length - 1;
        }
        return idx;
    }

    function getCategories() {
        var raw = document.getElementById('photo-categories-input').value;
        try { return JSON.parse(raw) || []; } catch (e) { return []; }
    }

    function saveCategories(cats) {
        document.getElementById('photo-categories-input').value = JSON.stringify(cats);
    }

    /* ─────────────────────────────────────────────────────────────────────────
       CARD BUILDERS
    ───────────────────────────────────────────────────────────────────────── */
    function buildPendingCard(entry) {
        var card = document.createElement('div');
        card.className = [
            'photo-card relative rounded-xl overflow-hidden',
            'border border-blue-200 shadow-sm bg-white',
            'opacity-0 scale-95 transition-all duration-200',
        ].join(' ');

        // Thumbnail
        var imgWrap = document.createElement('div');
        imgWrap.className = 'aspect-square bg-gray-100 relative';

        var img = document.createElement('img');
        img.src       = entry.objectUrl;
        img.alt       = 'Preview';
        img.className = 'w-full h-full object-cover';
        imgWrap.appendChild(img);

        // Pending badge
        var badge = document.createElement('span');
        badge.className = 'absolute bottom-1 left-1 text-[9px] bg-blue-600 text-white px-1.5 py-0.5 rounded-full pointer-events-none';
        badge.textContent = 'Pending';
        imgWrap.appendChild(badge);

        card.appendChild(imgWrap);

        // Star button (top-right)
        var star = document.createElement('button');
        star.type      = 'button';
        star.className = 'btn-star absolute top-1.5 right-1.5 z-10 w-7 h-7 flex items-center justify-center bg-black/40 hover:bg-black/60 rounded-full text-white text-base leading-none';
        star.setAttribute('aria-label', 'Set as primary photo');
        star.title     = 'Jadikan foto utama';
        star.textContent = '☆';
        card.appendChild(star);

        // Delete button (top-left)
        var del = document.createElement('button');
        del.type      = 'button';
        del.className = 'btn-delete absolute top-1.5 left-1.5 z-10 w-7 h-7 flex items-center justify-center bg-red-500/80 hover:bg-red-600 rounded-full text-white text-sm leading-none font-bold';
        del.setAttribute('aria-label', 'Delete photo');
        del.title     = 'Hapus foto';
        del.innerHTML = '&times;';
        card.appendChild(del);

        // Category select + hidden input (below image)
        var footer = document.createElement('div');
        footer.className = 'px-2 py-1.5 bg-white border-t border-gray-100';

        var select = buildCategorySelect(entry.category);
        select.addEventListener('change', function () {
            // Re-wire the hidden file input to the new category index
            entry.category = select.value;
            var newCatIdx = getCategoryIndex(select.value);
            entry.inputEl.name = 'gallery_uploads[' + newCatIdx + '][]';
        });
        footer.appendChild(select);
        card.appendChild(footer);

        // Wire delete
        del.addEventListener('click', function () {
            deletePendingCard(card, entry);
        });

        // Wire star — pending photos don't have a DB media_id yet;
        // we store a temporary blob URL reference so it can be visually starred,
        // but the real featured_image_id will only stick if you star a saved photo.
        star.addEventListener('click', function () {
            handleStarClick(card, null);
        });

        return card;
    }

    function buildCategorySelect(selectedValue) {
        var select = document.createElement('select');
        select.className = 'cat-select w-full rounded border-gray-300 text-gray-700 focus:ring-blue-500 focus:border-blue-500';
        select.setAttribute('aria-label', 'Photo category');

        CATEGORY_OPTIONS.forEach(function (opt) {
            var option = document.createElement('option');
            option.value       = opt;
            option.textContent = opt;
            if (opt === selectedValue) option.selected = true;
            select.appendChild(option);
        });
        return select;
    }

    /* ─────────────────────────────────────────────────────────────────────────
       STAR / PRIMARY LOGIC
    ───────────────────────────────────────────────────────────────────────── */
    function handleStarClick(clickedCard, mediaId) {
        // Un-star all cards
        grid.querySelectorAll('.photo-card .btn-star').forEach(function (btn) {
            btn.classList.remove('starred');
            btn.textContent = '☆';
        });

        // Star this card
        var btn = clickedCard.querySelector('.btn-star');
        btn.classList.add('starred');
        btn.textContent = '★';

        // Write the media_id into the featured_image_id field
        featuredInput.value = mediaId !== null ? mediaId : '';
    }

    /* ─────────────────────────────────────────────────────────────────────────
       DELETE LOGIC
    ───────────────────────────────────────────────────────────────────────── */
    function deleteSavedCard(card) {
        var photoId = card.dataset.photoId;
        var mediaId = card.dataset.mediaId;

        // Append to deleted_photo_ids
        var existing = deletedInput.value
            ? deletedInput.value.split(',').map(function (s) { return s.trim(); })
            : [];
        existing.push(String(photoId));
        deletedInput.value = existing.join(',');

        // If this was the primary photo, clear featured_image_id
        if (String(mediaId) === String(featuredInput.value)) {
            featuredInput.value = '';
        }

        removeCard(card);
    }

    function deletePendingCard(card, entry) {
        // If it was starred, clear featured_image_id
        if (card.querySelector('.btn-star.starred')) {
            featuredInput.value = '';
        }

        // Remove hidden file input from DOM
        if (entry.inputEl && entry.inputEl.parentNode) {
            entry.inputEl.parentNode.removeChild(entry.inputEl);
        }

        // Revoke object URL
        URL.revokeObjectURL(entry.objectUrl);

        // Remove from pendingFiles array
        pendingFiles = pendingFiles.filter(function (e) { return e !== entry; });

        removeCard(card);
    }

    function removeCard(card) {
        card.style.transition = 'opacity .15s, transform .15s';
        card.style.opacity    = '0';
        card.style.transform  = 'scale(0.9)';
        setTimeout(function () {
            if (card.parentNode) card.parentNode.removeChild(card);
            updateEmptyState();
        }, 160);
    }

    /* ─────────────────────────────────────────────────────────────────────────
       EVENT DELEGATION FOR SERVER-RENDERED SAVED CARDS
    ───────────────────────────────────────────────────────────────────────── */
    grid.addEventListener('click', function (e) {
        var card = e.target.closest('.photo-card[data-saved="1"]');
        if (!card) return;

        // Star
        if (e.target.closest('.btn-star')) {
            var mediaId = card.dataset.mediaId;
            handleStarClick(card, mediaId);
            return;
        }

        // Delete
        if (e.target.closest('.btn-delete')) {
            deleteSavedCard(card);
            return;
        }
    });

    // Category change on saved cards → update the hidden input
    grid.addEventListener('change', function (e) {
        if (!e.target.classList.contains('cat-select')) return;
        var card = e.target.closest('.photo-card[data-saved="1"]');
        if (!card) return;
        var hidden = card.querySelector('.cat-hidden');
        if (hidden) hidden.value = e.target.value;
    });

    /* ─────────────────────────────────────────────────────────────────────────
       UTILITIES
    ───────────────────────────────────────────────────────────────────────── */
    function updateEmptyState() {
        var hasCards = grid.querySelector('.photo-card') !== null;
        emptyMsg.classList.toggle('hidden', hasCards);
    }

    updateEmptyState();
})();
</script>
@endpush
