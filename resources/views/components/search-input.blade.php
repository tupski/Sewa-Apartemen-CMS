{{-- Pencarian publik dengan autocomplete (Alpine + endpoint /search/suggest).
     Input ini BERPARTISIPASI dalam submit form GET sekitarnya — autocomplete hanya
     pelengkap navigasi cepat; Enter (tanpa pilihan aktif) submit form normal.
     Argumen: $label, $placeholder, $additionalClasses, $inputClasses, $value, $action. --}}
@php
    $inputClasses = $inputClasses ?? 'w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-4 py-2.5 text-sm';
@endphp
<div class="relative {{ $additionalClasses ?? 'flex-1' }}"
     role="combobox"
     aria-haspopup="listbox"
     :aria-expanded="open"
     aria-owns="search-results-list"
     :aria-activedescendant="highlighted !== -1 ? 'search-result-' + highlighted : null"
     x-data="searchAutocomplete({ label: @js($label ?? ''), placeholder: @js($placeholder ?? ''), action: @js($action ?? route('search.suggest')), fieldName: @js($fieldName ?? 'search'), value: @js($value ?? ''), inputClasses: @js($inputClasses) })">
    <label :for="fieldName" class="sr-only" x-text="label"></label>
    <input :id="fieldName" type="text" :name="fieldName" autocomplete="off" x-model="query" x-on:input="search()" x-on:keydown="onKeydown" x-on:blur="close()"
           x-ref="input" :placeholder="placeholder" :aria-busy="loading.toString()"
           :aria-label="label || 'Search'"
           :class="inputClasses" />
    <span x-cloak x-show="loading" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400" aria-hidden="true">
        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
    </span>

    <div x-cloak x-show="open && hasResults && !loading"
         class="absolute z-30 mt-2 w-full bg-white dark:bg-gray-800 rounded-xl shadow-xl ring-1 ring-black/5 border border-gray-200 dark:border-gray-700 overflow-hidden"
         x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1">
        <ul id="search-results-list" role="listbox" x-on:mouseenter="open = true" class="max-h-80 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-700">
            <template x-for="(r, i) in results" :key="r.url">
                <li role="option" :id="'search-result-' + i" :aria-selected="highlighted === i">
                    <a x-on:click.prevent="go(r)" x-on:mousedown.prevent
                       x-on:mouseenter="highlighted = i"
                       href="#" :class="highlighted === i ? 'bg-gray-50 dark:bg-gray-700/60' : ''"
                       class="flex items-start justify-between gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/60 transition">
                        <span class="text-sm text-gray-800 dark:text-gray-100" x-html="highlight(r.title)"></span>
                        <span class="shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wide"
                              :class="r.type === 'property' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/60 dark:text-blue-300' : (r.type === 'post' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-300' : 'bg-purple-100 text-purple-700 dark:bg-purple-900/60 dark:text-purple-300')"
                              x-text="r.type"></span>
                    </a>
                </li>
            </template>
        </ul>
    </div>
    <div x-cloak x-show="loading"
         class="absolute z-30 mt-2 w-full bg-white dark:bg-gray-800 rounded-xl shadow-xl ring-1 ring-black/5 border border-gray-200 dark:border-gray-700 px-4 py-4 space-y-3">
        <div class="h-3 w-3/4 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
        <div class="h-3 w-2/3 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
        <div class="h-3 w-1/2 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
    </div>
    <div x-cloak x-show="open && !hasResults && !loading && query.length >= 2"
         class="absolute z-30 mt-2 w-full bg-white dark:bg-gray-800 rounded-xl shadow-xl ring-1 ring-black/5 border border-gray-200 dark:border-gray-700 px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
        No results found
    </div>
</div>
