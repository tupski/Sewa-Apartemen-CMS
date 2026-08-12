@php
    $photoCategories = $property?->photoCategories() ?? \App\Models\Property::DEFAULT_PHOTO_CATEGORIES;
    $existingPhotos = $property
        ? $property->photos->groupBy('category')->map(fn ($group) => $group->map(fn ($p) => [
            'id' => $p->id,
            'media_id' => $p->media_id,
            'url' => $p->media->url,
        ])->values())->toArray()
        : [];
@endphp

<div class="border-b border-gray-200 pb-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-2">Foto Properti</h3>
    <p class="text-sm text-gray-500 mb-4">Kelompokkan foto per kategori (Lobby, Bedroom, dll). Upload langsung dari sini, atau pilih dari Media Library.</p>

    <!-- Category chips -->
    <div class="flex flex-wrap gap-2 items-center mb-5" id="photo-cat-chips">
        @foreach ($photoCategories as $category)
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm bg-blue-50 text-blue-700 border border-blue-200">
                {{ $category }}
                <button type="button" data-remove-cat="{{ $category }}" class="text-blue-400 hover:text-blue-700" title="Hapus kategori">&times;</button>
            </span>
        @endforeach
    </div>
    <div class="flex items-center gap-2 mb-6">
        <input type="text" id="new-photo-cat" placeholder="Kategori baru (mis. Rooftop)"
               class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">
        <button type="button" id="add-photo-cat"
                class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-200 transition">
            Tambah Kategori
        </button>
    </div>

    <input type="hidden" name="photo_categories" id="photo-categories-input" value="{{ json_encode($photoCategories) }}">
    <input type="hidden" name="deleted_photo_ids" id="deleted-photo-ids" value="">

    <!-- Per-category blocks -->
    <div id="photo-cat-blocks" class="space-y-6"></div>

    <!-- Media Library Modal -->
    <div id="media-picker-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60" data-media-close></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[85vh] flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <h4 class="text-lg font-bold text-gray-800">Pilih dari Media Library</h4>
                <button type="button" data-media-close class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
            </div>
            <div class="p-6 overflow-y-auto flex-1">
                <p class="text-sm text-gray-500 mb-4">Klik foto untuk memilih (bisa lebih dari satu). Foto akan masuk ke kategori: <span id="media-picker-cat" class="font-semibold text-gray-800"></span></p>
                @if ($mediaImages->isEmpty())
                    <p class="text-sm text-gray-500 text-center py-10">Belum ada media. Upload dulu di menu Media, atau upload langsung di kategori di atas.</p>
                @else
                    <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3">
                        @foreach ($mediaImages as $media)
                            <button type="button" data-media-id="{{ $media->id }}" data-media-url="{{ $media->url }}"
                                    class="media-option aspect-square rounded-lg overflow-hidden border-2 border-transparent hover:border-blue-500 transition relative">
                                <img src="{{ $media->thumbnailUrl }}" alt="{{ $media->original_filename }}" class="w-full h-full object-cover">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                <span class="text-sm text-gray-500" id="media-picker-count">0 dipilih</span>
                <button type="button" id="media-picker-add"
                        class="px-5 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 transition">
                    Tambah ke Kategori
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        var categories = @json($photoCategories);
        var existing = @json($existingPhotos);
        var activeMediaIndex = null;
        var selectedMedia = {};

        function esc(s) {
            var d = document.createElement('div');
            d.textContent = s;
            return d.innerHTML;
        }

        function saveCategories() {
            document.getElementById('photo-categories-input').value = JSON.stringify(categories);
            var chips = document.getElementById('photo-cat-chips');
            chips.innerHTML = '';
            categories.forEach(function (cat) {
                var span = document.createElement('span');
                span.className = 'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm bg-blue-50 text-blue-700 border border-blue-200';
                span.innerHTML = esc(cat) + ' <button type="button" data-remove-cat="' + esc(cat) + '" class="text-blue-400 hover:text-blue-700" title="Hapus kategori">&times;</button>';
                chips.appendChild(span);
            });
            chips.querySelectorAll('[data-remove-cat]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    if (!confirm('Hapus kategori "' + btn.dataset.removeCat + '" beserta fotonya dari form?')) return;
                    var idx = categories.indexOf(btn.dataset.removeCat);
                    if (idx > -1) categories.splice(idx, 1);
                    delete selectedMedia[idx];
                    var block = document.getElementById('photo-block-' + idx);
                    if (block) block.remove();
                    renderBlocks();
                });
            });
        }

        function photoGrid(cat, idx) {
            var photos = existing[cat] || [];
            var wrap = document.createElement('div');
            wrap.className = 'grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-3';
            photos.forEach(function (p) {
                var el = document.createElement('div');
                el.className = 'relative aspect-square rounded-lg overflow-hidden border border-gray-200 group';
                el.innerHTML = '<img src="' + p.url + '" class="w-full h-full object-cover" alt="">' +
                    '<button type="button" data-del-photo="' + p.id + '" class="absolute top-1 right-1 w-6 h-6 rounded-full bg-red-600 text-white text-xs leading-none flex items-center justify-center opacity-0 group-hover:opacity-100 transition" title="Hapus foto">&times;</button>';
                el.querySelector('[data-del-photo]').addEventListener('click', function () {
                    if (!confirm('Hapus foto ini?')) return;
                    var del = document.getElementById('deleted-photo-ids');
                    var list = del.value ? del.value.split(',').filter(Boolean) : [];
                    if (list.indexOf(String(p.id)) === -1) list.push(String(p.id));
                    del.value = list.join(',');
                    el.remove();
                });
                wrap.appendChild(el);
            });
            if (!photos.length) {
                wrap.className += ' text-sm text-gray-400';
                wrap.innerHTML = 'Belum ada foto di kategori ini.';
            }
            return wrap;
        }

        function renderBlocks() {
            var container = document.getElementById('photo-cat-blocks');
            container.innerHTML = '';
            categories.forEach(function (cat, idx) {
                var block = document.createElement('div');
                block.id = 'photo-block-' + idx;
                block.className = 'border border-gray-200 rounded-lg p-4';
                block.innerHTML =
                    '<div class="flex items-center justify-between mb-3">' +
                        '<h4 class="font-medium text-gray-800">' + esc(cat) + '</h4>' +
                        '<div class="flex items-center gap-2">' +
                            '<label class="cursor-pointer inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-md hover:bg-blue-700 transition">' +
                                '<svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>' +
                                'Upload' +
                                '<input type="file" name="gallery_uploads[' + idx + '][]" multiple accept="image/*" class="hidden" data-upload="' + idx + '">' +
                            '</label>' +
                            '<button type="button" data-pick-media="' + idx + '" class="inline-flex items-center px-3 py-1.5 bg-gray-100 text-gray-700 text-xs font-medium rounded-md hover:bg-gray-200 transition">Pilih dari Media</button>' +
                        '</div>' +
                    '</div>' +
                    '<div class="mb-2 text-xs text-gray-500">File dipilih: <span data-filecount="' + idx + '">0</span></div>';
                block.appendChild(photoGrid(cat, idx));
                container.appendChild(block);
            });

            container.querySelectorAll('[data-upload]').forEach(function (input) {
                input.addEventListener('change', function () {
                    var count = input.files.length;
                    var label = container.querySelector('[data-filecount="' + input.dataset.upload + '"]');
                    if (label) label.textContent = count;
                });
            });

            container.querySelectorAll('[data-pick-media]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    activeMediaIndex = parseInt(btn.dataset.pickMedia, 10);
                    document.getElementById('media-picker-cat').textContent = categories[activeMediaIndex] || '';
                    selectedMedia = {};
                    document.querySelectorAll('.media-option').forEach(function (opt) {
                        opt.classList.remove('border-blue-500', 'bg-blue-50');
                    });
                    document.getElementById('media-picker-count').textContent = '0 dipilih';
                    document.getElementById('media-picker-modal').classList.remove('hidden');
                });
            });
        }

        document.getElementById('add-photo-cat').addEventListener('click', function () {
            var input = document.getElementById('new-photo-cat');
            var name = input.value.trim();
            if (!name) return;
            if (categories.indexOf(name) !== -1) {
                alert('Kategori sudah ada.');
                return;
            }
            categories.push(name);
            input.value = '';
            saveCategories();
            renderBlocks();
        });

        document.querySelectorAll('[data-media-close], [data-media-close]').forEach(function (el) {
            el.addEventListener('click', function () {
                document.getElementById('media-picker-modal').classList.add('hidden');
            });
        });

        document.querySelectorAll('.media-option').forEach(function (opt) {
            opt.addEventListener('click', function () {
                var id = opt.dataset.mediaId;
                var url = opt.dataset.mediaUrl;
                var sel = selectedMedia[id];
                if (sel) {
                    delete selectedMedia[id];
                    opt.classList.remove('border-blue-500', 'bg-blue-50');
                } else {
                    selectedMedia[id] = url;
                    opt.classList.add('border-blue-500', 'bg-blue-50');
                }
                document.getElementById('media-picker-count').textContent = Object.keys(selectedMedia).length + ' dipilih';
            });
        });

        document.getElementById('media-picker-add').addEventListener('click', function () {
            if (activeMediaIndex === null || !Object.keys(selectedMedia).length) {
                document.getElementById('media-picker-modal').classList.add('hidden');
                return;
            }
            var cat = categories[activeMediaIndex];
            if (!cat) return;

            // Persist chosen media ids as hidden inputs
            var container = document.getElementById('photo-cat-blocks');
            Object.keys(selectedMedia).forEach(function (id) {
                var hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'gallery_media[' + activeMediaIndex + '][]';
                hidden.value = id;
                container.appendChild(hidden);
            });

            // Show thumbnails in the block
            var block = document.getElementById('photo-block-' + activeMediaIndex);
            var grid = block.querySelector('.grid');
            Object.keys(selectedMedia).forEach(function (id) {
                var el = document.createElement('div');
                el.className = 'relative aspect-square rounded-lg overflow-hidden border border-green-300';
                el.innerHTML = '<img src="' + selectedMedia[id] + '" class="w-full h-full object-cover" alt="">' +
                    '<span class="absolute bottom-1 right-1 text-[9px] bg-green-600 text-white px-1.5 py-0.5 rounded-full">Media</span>';
                grid.appendChild(el);
            });

            document.getElementById('media-picker-modal').classList.add('hidden');
            activeMediaIndex = null;
        });

        saveCategories();
        renderBlocks();
    })();
</script>
@endpush
