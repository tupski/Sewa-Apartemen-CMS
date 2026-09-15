@php
    // Unit-type metadata manager — one card per canonical unit type the
    // property offers (properties.unit_types is the availability source; the
    // pricing checkboxes above own it). Metadata rows are loaded from
    // property_unit_types and edited inline via fetch(); no external requests.
    $property ??= null;
    $exists = (bool) $property?->exists;
    $selectedTypes = old('unit_types', $property?->unit_types ?? []);
    $metadataRows = $exists
        ? $property->unitTypeMetadata->map(fn ($row) => [
            'id' => $row->id,
            'unit_type' => $row->unit_type,
            'label' => \App\Models\Property::typeLabel($row->unit_type),
            'name' => $row->name,
            'description' => $row->description,
            'max_guests' => $row->max_guests,
            'bed_configuration' => $row->bed_configuration,
            'size' => $row->size,
            'size_unit' => $row->size_unit,
            'is_active' => $row->is_active,
            'sort_order' => $row->sort_order,
        ])->all()
        : [];
@endphp

<div class="border-b border-gray-200 pb-8" id="unit-types-section">
    <h3 class="text-lg font-semibold text-gray-800 mb-1">{{ __('Detail Tipe Kamar') }}</h3>
    <p class="text-sm text-gray-500 mb-5">
        {{ __('Kelola informasi tiap tipe kamar: nama tampilan, kapasitas, kamar tidur, dan luas. Tipe tersedia dicentang di bagian Tipe Kamar & Harga.') }}
    </p>

    @unless($exists)
        <div class="rounded-md bg-blue-50 border border-blue-200 px-4 py-3 text-sm text-blue-800 mb-4">
            {{ __('Simpan properti terlebih dahulu, lalu atur detail tiap tipe kamar di sini.') }}
        </div>
    @endunless

    @if($exists)
        <div x-data="unitTypeManager(@json($metadataRows), '{{ route('admin.properties.unit-types.store', $property) }}', '{{ route('admin.properties.unit-types.reorder', $property) }}', '{{ route('admin.properties.unit-types.destroy', ['property' => $property, 'unitType' => '__ID__']) }}')"
             x-cloak>
            <div class="grid gap-3">
                <template x-for="(row, index) in rows" :key="row.id ?? 'row-' + index">
                    <div class="border border-gray-200 rounded-lg p-4 bg-gray-50/50">
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-mono px-2 py-0.5 rounded bg-gray-200 text-gray-600"
                                      x-text="row.unit_type"></span>
                                <span class="text-sm font-semibold text-gray-800"
                                      x-text="row.display_name"></span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                      :class="row.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'"
                                      x-text="row.is_active ? '{{ __('Active') }}' : '{{ __('Inactive') }}'"></span>
                            </div>
                            <div class="flex items-center gap-1">
                                <button type="button" @click="move(index, -1)" :disabled="index === 0 || row.saving"
                                        class="p-1.5 text-gray-400 hover:text-gray-700 disabled:opacity-30"
                                        :aria-label="'{{ __('Move up') }} ' + row.display_name">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                </button>
                                <button type="button" @click="move(index, 1)" :disabled="index === rows.length - 1 || row.saving"
                                        class="p-1.5 text-gray-400 hover:text-gray-700 disabled:opacity-30"
                                        :aria-label="'{{ __('Move down') }} ' + row.display_name">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <button type="button" @click="row.editing = !row.editing"
                                        class="p-1.5 text-gray-400 hover:text-blue-600"
                                        :aria-label="'{{ __('Edit') }} ' + row.display_name"
                                        :aria-expanded="row.editing ? 'true' : 'false'">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <button type="button" @click="removeRow(index)" :disabled="row.saving"
                                        class="p-1.5 text-gray-400 hover:text-red-600 disabled:opacity-30"
                                        :aria-label="'{{ __('Delete metadata for') }} ' + row.display_name">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>

                        {{-- Summary line (visible when collapsed) --}}
                        <p class="text-xs text-gray-500" x-show="!row.editing"
                           x-text="summary(row) || '{{ __('Belum ada detail — klik ikon pensil untuk mengisi.') }}'"></p>

                        {{-- Edit form --}}
                        <div x-show="row.editing" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1"
                                       :for="'ut-name-' + row.id">{{ __('Nama tampilan') }}</label>
                                <input type="text" x-model="row.name" maxlength="100" :id="'ut-name-' + row.id"
                                       class="w-full rounded-md border-gray-300 text-sm"
                                       :placeholder="'{{ __('Default:') }} ' + row.label">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1"
                                       :for="'ut-guests-' + row.id">{{ __('Kapasitas tamu') }}</label>
                                <input type="number" x-model="row.max_guests" min="1" max="30" :id="'ut-guests-' + row.id"
                                       class="w-full rounded-md border-gray-300 text-sm">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-gray-600 mb-1"
                                       :for="'ut-desc-' + row.id">{{ __('Deskripsi') }}</label>
                                <textarea x-model="row.description" rows="2" maxlength="2000" :id="'ut-desc-' + row.id"
                                          class="w-full rounded-md border-gray-300 text-sm"></textarea>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-gray-600 mb-1"
                                       :for="'ut-bed-' + row.id">{{ __('Konfigurasi tempat tidur') }}</label>
                                <input type="text" x-model="row.bed_configuration" maxlength="255" :id="'ut-bed-' + row.id"
                                       class="w-full rounded-md border-gray-300 text-sm"
                                       placeholder="{{ __('mis. 1 King Bed + 1 Sofa Bed') }}">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1"
                                       :for="'ut-size-' + row.id">{{ __('Luas') }}</label>
                                <div class="flex gap-2">
                                    <input type="number" x-model="row.size" min="0" step="0.01" :id="'ut-size-' + row.id"
                                           class="w-full rounded-md border-gray-300 text-sm">
                                    <select x-model="row.size_unit" class="rounded-md border-gray-300 text-sm w-24"
                                            :aria-label="'{{ __('Size unit for') }} ' + row.display_name">
                                        <option value="sqm">m²</option>
                                        <option value="sqft">ft²</option>
                                    </select>
                                </div>
                            </div>
                            <div class="flex items-end gap-3">
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                    <input type="checkbox" x-model="row.is_active" class="rounded border-gray-300"
                                           :aria-label="'{{ __('Active') }} ' + row.display_name">
                                    {{ __('Aktif') }}
                                </label>
                            </div>
                            <div class="md:col-span-2 flex items-center gap-3">
                                <button type="button" @click="save(row)" :disabled="row.saving"
                                        class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-md bg-gray-800 text-white hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <svg x-show="row.saving" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                    {{ __('Simpan') }}
                                </button>
                                <button type="button" @click="row.editing = false" class="px-3 py-2 text-sm text-gray-600 hover:text-gray-800">
                                    {{ __('Batal') }}
                                </button>
                                <span x-show="row.error" class="text-xs text-red-600" x-text="row.error" role="status" aria-live="polite"></span>
                                <span x-show="row.saved" class="text-xs text-green-700" x-text="'✓ ' + '{{ __('Tersimpan') }}'"></span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <p x-show="rows.length === 0" class="text-sm text-gray-400 py-4">
                {{ __('Belum ada tipe kamar terpilih. Centang tipe di bagian Tipe Kamar & Harga, simpan properti, lalu detailnya bisa diatur di sini.') }}
            </p>
        </div>
    @endif
</div>

@once
@push('scripts')
<script>
// Unit-type metadata manager — inline edit/reorder/delete via fetch().
// Single global, idempotent across Turbo body-swaps.
window.unitTypeManager = function (initialRows, storeUrl, reorderUrl, destroyUrlTemplate) {
    'use strict';

    return {
        rows: initialRows.map(function (row) {
            return Object.assign({}, row, {
                editing: false, saving: false, error: '', saved: false,
            });
        }),

        summary: function (row) {
            var parts = [];
            if (row.max_guests) parts.push(row.max_guests + ' ' + '{{ __('guests') }}');
            if (row.bed_configuration) parts.push(row.bed_configuration);
            if (row.size) parts.push(row.size_formatted || row.size);
            return parts.join(' · ');
        },

        headers: function () {
            return {
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            };
        },

        persistOrder: function () {
            fetch(reorderUrl, {
                method: 'POST',
                headers: this.headers(),
                credentials: 'same-origin',
                body: JSON.stringify({ order: this.rows.map(function (row) { return row.id; }) }),
            }).catch(function () { /* ordering is cosmetic; next save re-syncs it */ });
        },

        move: function (index, direction) {
            var target = index + direction;
            if (target < 0 || target >= this.rows.length) return;

            var swapped = this.rows.slice();
            var tmp = swapped[index];
            swapped[index] = swapped[target];
            swapped[target] = tmp;
            this.rows = swapped;
            this.persistOrder();
        },

        save: function (row) {
            var self = this;
            row.saving = true;
            row.error = '';
            row.saved = false;

            fetch(storeUrl, {
                method: 'POST',
                headers: self.headers(),
                credentials: 'same-origin',
                body: JSON.stringify({
                    unit_type: row.unit_type,
                    name: row.name || '',
                    description: row.description || '',
                    max_guests: row.max_guests || null,
                    bed_configuration: row.bed_configuration || '',
                    size: row.size || null,
                    size_unit: row.size_unit || 'sqm',
                    is_active: !!row.is_active,
                    sort_order: row.sort_order || 0,
                }),
            })
                .then(function (res) {
                    return res.json().then(function (data) {
                        return { ok: res.ok, data: data };
                    }).catch(function () {
                        return { ok: false, data: {} };
                    });
                })
                .then(function (result) {
                    if (!result.ok || !result.data.success) {
                        row.error = (result.data && result.data.message) || '{{ __('unit_type.save_failed') }}';
                        return;
                    }

                    var updated = result.data.unit_type || {};
                    Object.assign(row, updated, { error: '', saved: true, editing: false });
                    setTimeout(function () { row.saved = false; }, 2500);
                })
                .catch(function () {
                    row.error = '{{ __('unit_type.save_failed') }}';
                })
                .finally(function () {
                    row.saving = false;
                });
        },

        removeRow: function (index) {
            var self = this;
            var row = this.rows[index];

            if (!row || row.saving) return;

            fetch(destroyUrlTemplate.replace('__ID__', row.id), {
                method: 'DELETE',
                headers: self.headers(),
                credentials: 'same-origin',
            })
                .then(function (res) {
                    if (res.ok) {
                        self.rows.splice(index, 1);
                    }
                })
                .catch(function () { /* row stays; error surfaces on retry */ });
        },
    };
};
</script>
@endpush
@endonce
