@php
    // Geoapify POI block — rendered directly beneath the manual "Nearby Places"
    // list inside the same card, so both halves of the nearby-places feature sit
    // together (create + edit screens share this partial).
    //
    // $property       — the Property model, or null on the create screen
    // $propertyPlaces — collection of PropertyPlace (with `place` eager loaded),
    //                   ordered nearest-first. Falls back to an empty collection.
    $propertyPlaces = $propertyPlaces ?? collect();
    $property = $property ?? null;
    $exists = (bool) $property?->exists;
    $hasCoords = $exists && $property->latitude !== null && $property->longitude !== null;
    $hasApiKey = \App\Services\GeoapifyService::isConfigured();
    $geoapifyKey = \App\Services\GeoapifyService::apiKey();
    $geoapifyMapKey = \App\Services\GeoapifyService::mapKey();
    $settingsUrl = route('admin.settings.index', ['group' => 'integrations']);
    $canSync = $exists && $hasCoords && $hasApiKey;
    // Reachability budgets shown to the admin (routed travel times, not radii).
    [$walkSeconds, $walkMetres] = \App\Services\GeoapifyService::MODE_BUDGETS['walk'];
    [$driveSeconds, $driveMetres] = \App\Services\GeoapifyService::MODE_BUDGETS['drive'];
    [$motoSeconds, $motoMetres] = \App\Services\GeoapifyService::MODE_BUDGETS['motorcycle'];
    // SEC-003: the browser map key falls back to the server Places key. When they
    // are identical the Places key is shipped to every browser that loads a
    // property page, so surface that to the operator.
    $sharesMapKey = $hasApiKey && $geoapifyMapKey === $geoapifyKey;
@endphp

{{-- ── Geoapify POI ──────────────────────────────────────────────────────── --}}
<div class="border-t border-gray-200 mt-6 pt-6" id="geoapify-poi">
    <div class="flex flex-wrap items-start justify-between gap-3 mb-2">
        <div>
            <h4 class="text-base font-semibold text-gray-800">{{ __('Geoapify POI') }}</h4>
            <p class="text-sm text-gray-500 mt-1">
                {{ __('Automatically find nearby places using Geoapify, grouped by your configured categories.') }}
            </p>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            {{-- API key status — the key itself is never rendered --}}
            @if($hasApiKey)
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    <span class="w-1.5 h-1.5 rounded-full bg-green-600"></span>
                    {{ __('Geoapify API: configured') }}
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                    <span class="w-1.5 h-1.5 rounded-full bg-yellow-600"></span>
                    {{ __('Geoapify API: not configured') }}
                </span>
            @endif

            {{-- Sync trigger.
                 NOT a <form>: this partial is included INSIDE the main property
                 <form>, and nested forms are invalid HTML — the browser discards
                 the inner form, so the button submitted the OUTER property-update
                 form instead and navigated away to the property list (no POI sync
                 ever ran). It is a plain button that POSTs via fetch() and swaps
                 the table in place, so the page never navigates. --}}
            @if($exists)
                <x-secondary-button type="button"
                                    id="poi-resync-btn"
                                    onclick="window.propertyPoiResync(this)"
                                    data-url="{{ route('admin.properties.resync-nearby-places', $property) }}"
                                    data-persisted-lat="{{ $property->latitude }}"
                                    data-persisted-lng="{{ $property->longitude }}"
                                    :disabled="! $canSync"
                                    class="shrink-0">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" data-poi-spinner>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span data-poi-label>{{ __('Sync Nearby POI') }}</span>
                </x-secondary-button>
            @else
                {{-- Create screen: there is no property id yet, so no route to call. --}}
                <x-secondary-button type="button" disabled class="shrink-0">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span>{{ __('Sync Nearby POI') }}</span>
                </x-secondary-button>
            @endif
        </div>
    </div>

    {{-- Create screen: explain the ordering instead of pretending it works. --}}
    @unless($exists)
        <div class="mt-3 rounded-md bg-blue-50 border border-blue-200 px-4 py-3 text-sm text-blue-800">
            {{ __('Save the property first (with the map location), then open it again to sync POIs from Geoapify.') }}
        </div>
    @endunless

    {{-- Coordinates present: warn when the map pin was moved but not saved yet --}}
    @if($exists && $hasCoords)
        <div id="poi-save-before-sync" class="mt-3 rounded-md bg-blue-50 border border-blue-200 px-4 py-3 text-sm text-blue-800">
            {{ __('Save the property after changing the map pin, then resync POIs.') }}
        </div>
    @endif

    {{-- Coordinates missing: sync cannot run, say why --}}
    @if($exists && ! $hasCoords)
        <div class="mt-3 rounded-md bg-yellow-50 border border-yellow-200 px-4 py-3 text-sm text-yellow-800">
            {{ __('Coordinates are required to find nearby places. Set the property location on the map, save, then sync.') }}
        </div>
    @endif

    {{-- API key missing: sync cannot run, link straight to the setting --}}
    @unless($hasApiKey)
        <div class="mt-3 rounded-md bg-yellow-50 border border-yellow-200 px-4 py-3 text-sm text-yellow-800">
            {{ __('Geoapify API key is not configured.') }}
            <a href="{{ $settingsUrl }}" class="font-medium underline hover:no-underline">
                {{ __('Configure it in Settings → Integrations.') }}
            </a>
        </div>
    @endunless

    {{-- SEC-003: browser map key is the same as the server Places key --}}
    @if($sharesMapKey)
        <div class="mt-3 rounded-md bg-yellow-50 border border-yellow-200 px-4 py-3 text-sm text-yellow-800">
            GEOAPIFY_MAP_KEY belum diatur, sehingga kunci Places API di server ikut dikirim ke browser pengunjung pada halaman properti. Setel Map Key terpisah yang dibatasi per domain/referrer di Settings → Integrations agar kunci Places API tidak terekspos.
        </div>
    @endif

    {{-- Live progress / result banner, filled by the sync fetch (no navigation) --}}
    <div id="poi-resync-message" class="mt-3 hidden rounded-md px-4 py-3 text-sm" role="status" aria-live="polite"></div>

    <p class="text-xs text-gray-400 mt-3">
        {{ __('Only places reachable within the travel budgets are kept (routed travel time, not straight-line distance): walk up to :walk_minutes min / :walk_meters m, car up to :drive_minutes min / :drive_meters m, motorcycle up to :moto_minutes min / :moto_meters m.', [
            'walk_minutes' => (int) ceil($walkSeconds / 60),
            'walk_meters' => number_format($walkMetres, 0, ',', '.'),
            'drive_minutes' => (int) ceil($driveSeconds / 60),
            'drive_meters' => number_format($driveMetres, 0, ',', '.'),
            'moto_minutes' => (int) ceil($motoSeconds / 60),
            'moto_meters' => number_format($motoMetres, 0, ',', '.'),
        ]) }}
    </p>

    {{-- Synchronized POIs — replaced wholesale by the sync response --}}
    <div id="poi-table-wrap" class="mt-4">
        @include('admin.properties._nearby-table', ['propertyPlaces' => $propertyPlaces])
    </div>

    {{-- ── Category management ───────────────────────────────────────── --}}
    {{-- The Artivo-managed catalogue the sync iterates. Rows are edited
         client-side and saved in bulk; the slug (Geoapify key) is immutable
         for existing rows so the sync match keys never drift. --}}
    @php
        $managedCategories = \App\Models\PlaceCategory::query()
            ->orderBy('sort_order')->orderBy('id')->get()
            ->map(fn ($c) => [
                'id' => $c->id, 'slug' => $c->slug, 'name_id' => $c->name_id,
                'name_en' => $c->name_en, 'icon' => $c->icon, 'color' => $c->color,
                'is_active' => $c->is_active, 'sort_order' => $c->sort_order,
            ])->values()->all();
    @endphp
    <div id="place-category-manager"
         class="mt-4 border border-gray-200 rounded-md"
         x-data="placeCategoryManager(@json($managedCategories), '{{ route('admin.place-categories.update') }}', '{{ route('admin.place-categories.destroy', '__ID__') }}')"
         x-cloak>
        <button type="button" class="w-full flex items-center justify-between px-4 py-3 text-left text-sm font-semibold text-gray-700 hover:bg-gray-50 rounded-t-md"
                @click="open = !open" :aria-expanded="open.toString()">
            <span class="flex items-center gap-2">
                <i class="fa-solid fa-layer-group text-gray-400" aria-hidden="true"></i>
                {{ __('Manage categories') }}
                <span class="font-normal text-gray-400" x-text="'(' + rows.length + ')'"></span>
            </span>
            <svg :class="open ? 'rotate-180' : ''" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <div x-show="open" class="px-4 pb-4 border-t border-gray-100">
            <p class="text-xs text-gray-400 mt-3">
                {{ __('Slug is the Geoapify category key used for the sync — it cannot be changed after creation. Labels are shown to guests per language.') }}
            </p>

            <div class="hidden md:grid grid-cols-12 gap-2 mt-3 text-xs font-medium text-gray-500 uppercase tracking-wide px-1">
                <span class="col-span-3">{{ __('Geoapify key (slug)') }}</span>
                <span class="col-span-2">{{ __('Indonesian name') }}</span>
                <span class="col-span-2">{{ __('English name') }}</span>
                <span class="col-span-2">{{ __('Icon class') }}</span>
                <span class="col-span-1">{{ __('Color') }}</span>
                <span class="col-span-1">{{ __('Active') }}</span>
                <span class="col-span-1"></span>
            </div>

            <div class="space-y-2 mt-2">
                <template x-for="(cat, index) in rows" :key="cat.id ?? 'new-' + index">
                    <div class="grid grid-cols-2 md:grid-cols-12 gap-2 items-center bg-gray-50/60 rounded-md p-2">
                        <input type="text" x-model="cat.slug" :disabled="!!cat.id" maxlength="64"
                               class="col-span-2 md:col-span-3 rounded-md border-gray-300 text-sm disabled:bg-gray-100 disabled:text-gray-500"
                               :aria-label="'{{ __('Geoapify key (slug)') }} ' + (index + 1)" placeholder="catering.cafe">
                        <input type="text" x-model="cat.name_id" maxlength="100" required
                               class="col-span-1 md:col-span-2 rounded-md border-gray-300 text-sm"
                               :aria-label="'{{ __('Indonesian name') }} ' + (index + 1)" placeholder="Kafe">
                        <input type="text" x-model="cat.name_en" maxlength="100" required
                               class="col-span-1 md:col-span-2 rounded-md border-gray-300 text-sm"
                               :aria-label="'{{ __('English name') }} ' + (index + 1)" placeholder="Cafe">
                        <input type="text" x-model="cat.icon" maxlength="100"
                               class="col-span-1 md:col-span-2 rounded-md border-gray-300 text-sm"
                               :aria-label="'{{ __('Icon class') }} ' + (index + 1)" placeholder="fa-solid fa-mug-hot">
                        <input type="color" x-model="cat.color"
                               class="col-span-1 md:col-span-1 h-[38px] w-full rounded-md border-gray-300 p-0.5"
                               :aria-label="'{{ __('Color') }} ' + (index + 1)">
                        <label class="col-span-1 md:col-span-1 flex items-center justify-center gap-1.5 text-xs text-gray-600">
                            <input type="checkbox" x-model="cat.is_active" class="rounded border-gray-300"
                                   :aria-label="'{{ __('Active') }} ' + (index + 1)">
                        </label>
                        <button type="button" @click="removeRow(index)"
                                class="col-span-2 md:col-span-1 justify-self-end text-gray-400 hover:text-red-600"
                                :aria-label="'{{ __('Remove') }} ' + (cat.slug || index + 1)">
                            <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                        </button>
                    </div>
                </template>
            </div>

            <div class="flex flex-wrap items-center gap-2 mt-3">
                <button type="button" @click="addRow"
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50">
                    <i class="fa-solid fa-plus text-xs" aria-hidden="true"></i>
                    {{ __('Add category') }}
                </button>
                <button type="button" @click="save" :disabled="saving"
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-md bg-gray-800 text-white hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg x-show="saving" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    {{ __('Save categories') }}
                </button>
                <span x-show="message" x-text="message" class="text-xs"
                      :class="ok ? 'text-green-700' : 'text-red-700'" role="status" aria-live="polite"></span>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Sync nearby POIs without leaving the page.
//
// Defined as a single global (idempotent across Turbo body-swaps, since the
// script re-runs and simply reassigns the same function) and wired through an
// inline onclick, so no listener is ever stacked twice.
window.propertyPoiResync = function (btn) {
    'use strict';

    if (!btn) return;

    var latInput = document.getElementById('latitude');
    var lngInput = document.getElementById('longitude');
    var coordinatesDiffer = function (first, second) {
        if (!first && !second) return false;
        var firstNumber = Number(first);
        var secondNumber = Number(second);
        return !Number.isFinite(firstNumber) || !Number.isFinite(secondNumber)
            || Math.abs(firstNumber - secondNumber) > 0.0000001;
    };
    var hasUnsavedCoordinates = latInput && lngInput
        && (coordinatesDiffer(latInput.value, btn.dataset.persistedLat)
            || coordinatesDiffer(lngInput.value, btn.dataset.persistedLng));
    var saveNotice = document.getElementById('poi-save-before-sync');

    if (hasUnsavedCoordinates) {
        if (saveNotice) saveNotice.classList.add('ring-2', 'ring-blue-300');
        return;
    }

    // Duplicate-request guard: the button is disabled for the whole request, and
    // a second click while a sync is in flight is ignored.
    if (btn.disabled || btn.dataset.syncing === '1') return;

    var url     = btn.dataset.url;
    var label   = btn.querySelector('[data-poi-label]');
    var spinner = btn.querySelector('[data-poi-spinner]');
    var banner  = document.getElementById('poi-resync-message');
    var wrap    = document.getElementById('poi-table-wrap');
    var tokenEl = document.querySelector('meta[name="csrf-token"]');

    if (!url || !tokenEl) return;

    var original = label ? label.textContent : '';
    var phaseTimer = null;
    var phaseIndex = 0;

    // Staged progress text while the request is in flight. The server performs
    // these steps in order (Places search -> walking times -> persist), so the
    // labels describe what is actually happening.
    var phases = [
        '{{ __('Searching for nearby places…') }}',
        '{{ __('Checking walking distance…') }}',
        '{{ __('Saving nearby places…') }}'
    ];

    function showBanner(message, tone) {
        if (!banner) return;
        banner.textContent = message;
        banner.className = 'mt-3 rounded-md px-4 py-3 text-sm border ' + (tone === 'success'
            ? 'bg-green-50 border-green-200 text-green-800'
            : (tone === 'warning'
                ? 'bg-yellow-50 border-yellow-200 text-yellow-800'
                : 'bg-red-50 border-red-200 text-red-800'));
    }

    function stopPhases() {
        if (phaseTimer) {
            window.clearInterval(phaseTimer);
            phaseTimer = null;
        }
    }

    btn.disabled = true;
    btn.dataset.syncing = '1';
    if (label) label.textContent = phases[0];
    if (spinner) spinner.classList.add('animate-spin');
    showBanner(phases[0], 'warning');

    phaseTimer = window.setInterval(function () {
        phaseIndex = Math.min(phaseIndex + 1, phases.length - 1);
        if (label) label.textContent = phases[phaseIndex];
        showBanner(phases[phaseIndex], 'warning');
    }, 1500);

    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': tokenEl.content,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        credentials: 'same-origin'
    })
    .then(function (res) {
        return res.json().then(function (data) {
            return { ok: res.ok, status: res.status, data: data };
        }).catch(function () {
            return { ok: false, status: res.status, data: {} };
        });
    })
    .then(function (result) {
        var data = result.data || {};
        var ok = !!(result.ok && data.success);
        var message = data.message
            || (result.status === 429
                ? '{{ __('Too many sync requests. Please try again in a few minutes.') }}'
                : '{{ __('Unable to synchronize nearby places. Please check your Geoapify API key and try again.') }}');

        stopPhases();

        if (ok && typeof data.html === 'string' && wrap) {
            wrap.innerHTML = data.html;
        }

        showBanner(message, ok ? (data.partial ? 'warning' : 'success') : 'error');

        if (typeof window.toast === 'function') {
            window.toast(message, ok ? (data.partial ? 'warning' : 'success') : 'error');
        }
    })
    .catch(function () {
        stopPhases();
        var message = '{{ __('Could not reach the server. Check your connection and try again.') }}';
        showBanner(message, 'error');
        if (typeof window.toast === 'function') window.toast(message, 'error');
    })
    .finally(function () {
        stopPhases();
        btn.disabled = false;
        btn.dataset.syncing = '';
        if (label) label.textContent = original;
        if (spinner) spinner.classList.remove('animate-spin');
    });
};

(function () {
    var btn = document.getElementById('poi-resync-btn');
    var latInput = document.getElementById('latitude');
    var lngInput = document.getElementById('longitude');
    var saveNotice = document.getElementById('poi-save-before-sync');

    if (!btn || !latInput || !lngInput) return;

    function coordinatesDiffer(first, second) {
        if (!first && !second) return false;
        var firstNumber = Number(first);
        var secondNumber = Number(second);
        return !Number.isFinite(firstNumber) || !Number.isFinite(secondNumber)
            || Math.abs(firstNumber - secondNumber) > 0.0000001;
    }

    function updateSyncState() {
        var changed = coordinatesDiffer(latInput.value, btn.dataset.persistedLat)
            || coordinatesDiffer(lngInput.value, btn.dataset.persistedLng);

        if (changed) {
            btn.disabled = true;
            if (saveNotice) saveNotice.classList.add('ring-2', 'ring-blue-300');
        } else if ({{ $canSync ? 'true' : 'false' }}) {
            btn.disabled = false;
            if (saveNotice) saveNotice.classList.remove('ring-2', 'ring-blue-300');
        }
    }

    latInput.addEventListener('input', updateSyncState);
    lngInput.addEventListener('input', updateSyncState);
    updateSyncState();
})();

// Category manager — bulk save + safe delete of the POI category catalogue.
// A single global (idempotent across Turbo body-swaps, like propertyPoiResync).
window.placeCategoryManager = function (initialRows, updateUrl, destroyUrlTemplate) {
    'use strict';

    return {
        open: false,
        saving: false,
        message: '',
        ok: true,
        rows: initialRows,

        headers: function () {
            return {
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            };
        },

        applyResponse: function (data) {
            if (data && Array.isArray(data.categories)) {
                this.rows = data.categories;
            }
            this.message = data.message || '';
            this.ok = !!data.success;
        },

        addRow: function () {
            this.rows.push({
                id: null,
                slug: '',
                name_id: '',
                name_en: '',
                icon: '',
                color: '#7c3aed',
                is_active: true,
                sort_order: this.rows.length,
            });
        },

        removeRow: function (index) {
            var self = this;
            var row = this.rows[index];

            if (!row) return;

            // Unsaved rows are removed client-side only.
            if (!row.id) {
                this.rows.splice(index, 1);
                return;
            }

            var url = destroyUrlTemplate.replace('__ID__', row.id);

            fetch(url, {
                method: 'DELETE',
                headers: self.headers(),
                credentials: 'same-origin',
            })
                .then(function (res) {
                    return res.json().then(function (data) {
                        return { ok: res.ok, data: data };
                    }).catch(function () {
                        return { ok: false, data: {} };
                    });
                })
                .then(function (result) {
                    self.applyResponse(result.data);

                    if (result.ok) {
                        self.rows = result.data.categories || self.rows;
                    }
                })
                .catch(function () {
                    self.ok = false;
                    self.message = '{{ __('place_category.delete_failed') }}';
                });
        },

        save: function () {
            var self = this;

            if (this.saving) return;

            for (var i = 0; i < this.rows.length; i++) {
                if (!String(this.rows[i].slug || '').trim()
                    || !String(this.rows[i].name_id || '').trim()
                    || !String(this.rows[i].name_en || '').trim()) {
                    this.ok = false;
                    this.message = '{{ __('place_category.row_incomplete') }}';
                    return;
                }
            }

            this.saving = true;
            this.message = '';

            fetch(updateUrl, {
                method: 'POST',
                headers: self.headers(),
                credentials: 'same-origin',
                body: JSON.stringify({ categories: this.rows }),
            })
                .then(function (res) {
                    return res.json().then(function (data) {
                        return { ok: res.ok, data: data };
                    }).catch(function () {
                        return { ok: false, data: {} };
                    });
                })
                .then(function (result) {
                    self.applyResponse(result.data);
                })
                .catch(function () {
                    self.ok = false;
                    self.message = '{{ __('place_category.save_failed') }}';
                })
                .finally(function () {
                    self.saving = false;
                });
        },
    };
};
</script>
@endpush
