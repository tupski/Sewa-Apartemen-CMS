@php
    // Shared amenity picker for the admin property create/edit forms.
    //
    // Options are streamed from `admin.amenities.options` (server-side search,
    // 20-per-page load-more) so the browser never receives the full amenity
    // dataset. Selection is kept in the Alpine component and rendered back as
    // hidden `amenities[]` inputs — the same plain array POST the form always
    // sent, so PropertyRequest validation and sync() are unchanged.
    //
    // $amenitiesSelected — collection of already-selected Amenity models
    // (from the property pivot or old() input); resolved server-side because
    // their ids must survive a validation-failure redirect.
    $amenitiesSelected = $amenitiesSelected ?? collect();
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-100"
     x-data="amenityPicker({
         url: @js(route('admin.amenities.options')),
         selected: @js($amenitiesSelected->mapWithKeys(fn ($a) => [(string) $a->id => [
             'id' => $a->id,
             'name' => $a->name,
             'icon' => $a->icon_class,
             'category' => $a->category,
         ]])),
     })">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-3">
        <div class="w-1 h-5 bg-purple-500 rounded-full"></div>
        <h3 class="text-sm font-semibold text-gray-900">Fasilitas (Amenities)</h3>
        <span class="ml-auto text-xs text-gray-500" x-show="selectedIds.length > 0">
            <span x-text="selectedIds.length"></span> dipilih
        </span>
    </div>
    <div class="p-5 space-y-3">

        {{-- Hidden form inputs — re-rendered from the selection Map. --}}
        <template x-for="item in selectedItems" :key="'am-hidden-'+item.id">
            <input type="hidden" name="amenities[]" :value="item.id">
        </template>

        {{-- Search (server-side, debounced in the component). --}}
        <div class="relative">
            <input type="text"
                   x-model="q"
                   @input="onSearch()"
                   placeholder="Cari fasilitas..."
                   autocomplete="off"
                   aria-label="Cari fasilitas"
                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
            {{-- Spinner in the input while a replace-fetch is in flight. --}}
            <svg x-show="loading" class="animate-spin absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
        </div>

        {{-- Selected chips (removable). --}}
        <div x-show="selectedIds.length > 0" x-cloak class="flex flex-wrap gap-1.5">
            <template x-for="item in selectedItems" :key="'am-chip-'+item.id">
                <span class="inline-flex items-center gap-1.5 pl-2 pr-1 py-1 bg-blue-50 border border-blue-200 text-blue-800 rounded-full text-xs">
                    <i x-show="item.icon" :class="item.icon"></i>
                    <span x-text="item.name"></span>
                    <button type="button"
                            @click="unselect(item.id)"
                            class="w-4 h-4 flex items-center justify-center rounded-full hover:bg-blue-200 text-blue-600"
                            :aria-label="'Hapus ' + item.name">&times;</button>
                </span>
            </template>
        </div>

        {{-- Error (initial/search load) + retry. --}}
        <div x-show="error && items.length === 0" x-cloak class="py-6 text-center">
            <p class="text-sm text-red-600" x-text="error"></p>
            <button type="button" @click="fetchPage(1)"
                    class="mt-2 text-sm font-medium text-blue-600 hover:text-blue-800 underline">Coba lagi</button>
        </div>

        {{-- Options list — fixed max height so search never jumps the layout. --}}
        <div x-show="!error || items.length > 0" class="max-h-80 overflow-y-auto rounded-lg border border-gray-100 divide-y divide-gray-100">

            {{-- Loading skeletons (initial load only; keep height to avoid jump). --}}
            <div x-show="loading && items.length === 0" class="p-2 space-y-2">
                <template x-for="i in 4" :key="'am-sk-'+i">
                    <div class="h-9 bg-gray-100 rounded-lg animate-pulse"></div>
                </template>
            </div>

            {{-- No search results vs. empty dataset. --}}
            <div x-show="!loading && !error && initialized && items.length === 0" x-cloak class="py-6 px-4 text-center text-sm text-gray-500">
                <span x-show="q.trim() !== ''">
                    Tidak ada fasilitas yang cocok dengan &ldquo;<span class="font-medium" x-text="q"></span>&rdquo;.
                </span>
                <span x-show="q.trim() === ''">
                    Belum ada fasilitas aktif.<br>
                    <a href="{{ route('admin.amenities.create') }}" class="text-blue-600 underline text-xs">Tambah fasilitas</a>
                </span>
            </div>

            {{-- Option rows. --}}
            <template x-for="item in items" :key="'am-'+item.id">
                <label class="flex items-center gap-2 px-3 py-2.5 hover:bg-gray-50 cursor-pointer transition"
                       :class="isSelected(item) ? 'bg-blue-50' : 'bg-white'">
                    <input type="checkbox"
                           :value="item.id"
                           :checked="isSelected(item)"
                           @change="toggle(item)"
                           class="h-4 w-4 text-blue-600 rounded focus:ring-blue-500 shrink-0">
                    <i x-show="item.icon" :class="item.icon + ' w-4 text-center text-gray-500 shrink-0'"></i>
                    <span class="text-xs text-gray-700 leading-tight" x-text="item.name"></span>
                    <span class="ml-auto text-[10px] uppercase tracking-wide text-gray-400" x-text="item.category"></span>
                </label>
            </template>

            {{-- Load more / appending / append error (page + search preserved by the component). --}}
            <div x-show="hasMore && !loadingMore" x-cloak class="p-2">
                <button type="button" @click="loadMore()"
                        class="w-full py-2 text-sm font-medium text-blue-600 hover:bg-blue-50 rounded-lg transition">
                    Muat lebih banyak
                </button>
            </div>
            <div x-show="loadingMore" class="py-3 text-center text-xs text-gray-500">Memuat&hellip;</div>
            <div x-show="error && items.length > 0" x-cloak class="p-2 text-center">
                <span class="text-xs text-red-600" x-text="error"></span>
                <button type="button" @click="loadMore()"
                        class="ml-1 text-xs font-medium text-blue-600 underline">Coba lagi</button>
            </div>
        </div>

        {{-- Footer count. --}}
        <p x-show="items.length > 0" x-cloak class="text-xs text-gray-400">
            <span x-text="items.length"></span> fasilitas ditampilkan<span x-show="hasMore"> — masih ada lagi, gunakan pencarian atau muat lebih banyak</span>.
        </p>
    </div>
</div>
