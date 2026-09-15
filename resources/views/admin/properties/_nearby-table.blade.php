@php
    // Persistent Geoapify POI rows for one property — the admin management table.
    //
    // Rendered server-side and then handed to an Alpine component that does
    // client-side search/filter (no external API ever involved: everything the
    // filter operates on was already loaded from the database) and inline
    // show/hide + custom-name edits via admin.properties.places.update.
    //
    // $propertyPlaces — collection of PropertyPlace with `place` eager loaded.
    // Data shape per row: every field the JS needs, JSON-encoded once.
    $propertyPlaces = $propertyPlaces ?? collect();

    $orderedCategories = \App\Models\PlaceCategory::query()
        ->orderBy('sort_order')->orderBy('id')->get();

    $rows = [];

    foreach ($propertyPlaces as $propertyPlace) {
        $raw = $propertyPlace->place->category ?? null;
        $slug = null;

        if (is_string($raw) && $raw !== '') {
            foreach ($orderedCategories as $category) {
                if ($raw === $category->slug || str_starts_with($raw, $category->slug.'.')) {
                    $slug = $category->slug;
                    break;
                }
            }

            if ($slug === null) {
                $top = strtok($raw, '.');
                foreach ($orderedCategories as $category) {
                    if ($category->slug === $top) {
                        $slug = $top;
                        break;
                    }
                }
            }
        }

        $rows[] = [
            'id' => $propertyPlace->id,
            'provider_name' => (string) ($propertyPlace->place->name ?? ''),
            'custom_name' => (string) ($propertyPlace->custom_name ?? ''),
            'display_name' => $propertyPlace->display_name,
            'category_slug' => $slug,
            'category_label' => $slug !== null ? $slug : __('Others'),
            'category_display' => $slug !== null ? \App\Models\PlaceCategory::labelForSlug($slug) : __('Others'),
            'show_on_frontend' => (bool) $propertyPlace->show_on_frontend,
            'source' => (string) $propertyPlace->source,
            'walking' => $propertyPlace->walking_duration_formatted,
            'walking_distance' => $propertyPlace->walking_distance_formatted ?? $propertyPlace->distance_formatted,
            'address' => \Illuminate\Support\Str::limit((string) ($propertyPlace->place->address ?? ''), 48),
        ];
    }
@endphp

<div class="overflow-x-auto"
     x-data="poiTable(@json($rows), '{{ route('admin.properties.places.update', ['property' => $property->id ?? 0, 'place' => '__ID__']) }}')"
     x-cloak>
    @if($propertyPlaces->isEmpty())
        <p class="text-sm text-gray-400 py-4">{{ __('No nearby places have been synchronized yet.') }}</p>
    @else
        {{-- Search / filter bar — operates on the already-loaded DB rows only. --}}
        <div class="flex flex-wrap items-center gap-2 mb-3">
            <input type="search" x-model="search" maxlength="100"
                   class="rounded-md border-gray-300 text-sm max-w-xs"
                   placeholder="{{ __('Search place…') }}"
                   aria-label="{{ __('Search place…') }}">
            <select x-model="category" class="rounded-md border-gray-300 text-sm"
                    aria-label="{{ __('Filter by category') }}">
                <option value="">{{ __('All categories') }}</option>
                <template x-for="cat in categories" :key="cat.value">
                    <option :value="cat.value" x-text="cat.label"></option>
                </template>
            </select>
            <select x-model="visibility" class="rounded-md border-gray-300 text-sm"
                    aria-label="{{ __('Filter by visibility') }}">
                <option value="">{{ __('All') }}</option>
                <option value="visible">{{ __('Visible on frontend') }}</option>
                <option value="hidden">{{ __('Hidden') }}</option>
            </select>
            <span class="text-xs text-gray-400" x-text="filtered.length + ' / ' + rows.length"></span>
        </div>

        <p x-show="filtered.length === 0" class="text-sm text-gray-400 py-4">
            {{ __('No places match the current filter.') }}
        </p>

        <table class="min-w-full divide-y divide-gray-200 text-sm" x-show="filtered.length > 0">
            <thead>
                <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <th class="px-4 py-2">{{ __('Show') }}</th>
                    <th class="px-4 py-2">{{ __('Name') }}</th>
                    <th class="px-4 py-2">{{ __('Category') }}</th>
                    <th class="px-4 py-2">{{ __('Walking time') }}</th>
                    <th class="px-4 py-2">{{ __('Address') }}</th>
                    <th class="px-4 py-2">{{ __('Source') }}</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100">
                <template x-for="row in filtered" :key="row.id">
                    <tr class="hover:bg-gray-50" :class="!row.show_on_frontend ? 'opacity-60' : ''">
                        {{-- Visibility toggle — inline PATCH, disabled while saving --}}
                        <td class="px-4 py-2">
                            <button type="button" @click="toggle(row)"
                                    :disabled="row.saving"
                                    class="relative inline-flex h-5 w-9 shrink-0 rounded-full transition-colors"
                                    :class="row.show_on_frontend ? 'bg-green-600' : 'bg-gray-300'"
                                    role="switch"
                                    :aria-checked="row.show_on_frontend ? 'true' : 'false'"
                                    :aria-label="(row.show_on_frontend ? '{{ __('Hide') }}' : '{{ __('Show') }}') + ' ' + row.display_name">
                                <span class="inline-block h-4 w-4 translate-x-0.5 transform rounded-full bg-white shadow transition-transform"
                                      :class="row.show_on_frontend ? 'translate-x-[18px]' : ''"></span>
                            </button>
                        </td>

                        {{-- Display name (custom if set) + editable custom name --}}
                        <td class="px-4 py-2">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-gray-900" x-text="row.display_name"></span>
                                <span x-show="row.custom_name"
                                      class="text-xs text-gray-400"
                                      x-text="'(' + row.provider_name + ')'"></span>
                            </div>
                            <input type="text" x-model="row.custom_name" maxlength="255"
                                   @keydown.enter.prevent="saveName(row)"
                                   @blur="saveName(row)"
                                   :disabled="row.saving"
                                   class="mt-1 w-full max-w-xs rounded border-gray-200 text-xs text-gray-600 placeholder-gray-300 focus:border-blue-300"
                                   :placeholder="'{{ __('Custom name (blank =') }} ' + row.provider_name + ')'"
                                   :aria-label="'{{ __('Custom name for') }} ' + row.display_name">
                            <p x-show="row.error" class="text-xs text-red-600 mt-1" x-text="row.error"></p>
                        </td>

                        <td class="px-4 py-2 text-gray-600" x-text="row.category_display"></td>
                        <td class="px-4 py-2 text-gray-600 tabular-nums whitespace-nowrap"
                            x-text="(row.walking ?? '—')"></td>
                        <td class="px-4 py-2 text-gray-500" x-text="row.address"></td>
                        <td class="px-4 py-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                  :class="row.source === 'geoapify' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-700'"
                                  x-text="row.source"></span>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    @endif
</div>

@once
@push('scripts')
<script>
// POI management table: client-side search/filter (DB data only — no network
// request is made for filtering) + inline show/hide and custom-name PATCHes.
// Single global, idempotent across Turbo body-swaps (like propertyPoiResync).
window.poiTable = function (initialRows, updateUrlTemplate) {
    'use strict';

    return {
        rows: initialRows.map(function (row) {
            return Object.assign({}, row, { saving: false, error: '' });
        }),
        search: '',
        category: '',
        visibility: '',

        get categories() {
            // Derived from the loaded rows' DB-backed labels — no hardcoded names.
            var seen = {};
            var list = [];
            this.rows.forEach(function (row) {
                if (!seen[row.category_label]) {
                    seen[row.category_label] = true;
                    list.push({ value: row.category_label, label: row.category_display });
                }
            });
            return list;
        },

        get filtered() {
            var q = this.search.trim().toLowerCase();
            return this.rows.filter(function (row) {
                if (q && row.display_name.toLowerCase().indexOf(q) === -1
                    && row.provider_name.toLowerCase().indexOf(q) === -1
                    && row.custom_name.toLowerCase().indexOf(q) === -1) {
                    return false;
                }
                if (this.category && row.category_label !== this.category) {
                    return false;
                }
                if (this.visibility === 'visible' && !row.show_on_frontend) return false;
                if (this.visibility === 'hidden' && row.show_on_frontend) return false;
                return true;
            }, this);
        },

        patchUrl: function (row) {
            return updateUrlTemplate.replace('__ID__', row.id);
        },

        patch: function (row, payload) {
            var self = this;
            row.saving = true;
            row.error = '';

            fetch(this.patchUrl(row), {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify(payload),
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
                        row.error = result.data.message || '{{ __('poi_table.update_failed') }}';
                    }
                })
                .catch(function () {
                    row.error = '{{ __('poi_table.update_failed') }}';
                })
                .finally(function () {
                    row.saving = false;
                });
        },

        toggle: function (row) {
            var next = !row.show_on_frontend;
            // Optimistic UI: flip immediately; a failed PATCH reverts it.
            row.show_on_frontend = next;
            this.patch(row, {
                show_on_frontend: next,
                custom_name: row.custom_name,
            });
        },

        saveName: function (row) {
            var next = String(row.custom_name || '').trim();
            if (next === row.display_name && next !== '') return;

            this.patch(row, {
                show_on_frontend: row.show_on_frontend,
                custom_name: next,
            });
        },
    };
};
</script>
@endpush
@endonce
