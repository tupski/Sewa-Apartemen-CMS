@extends('layouts.admin')
@section('page-title', __('lang.edit_translations') . ' — ' . $language->name)

@section('content')
<div class="max-w-5xl mx-auto"
     x-data="translationEditor()"
     x-init="init()">

    <div class="mb-5">
        <a href="{{ route('admin.languages.index') }}" class="text-sm text-blue-600 hover:underline">← {{ __('Kembali') }}</a>
        <h1 class="text-xl font-bold text-gray-800 dark:text-white mt-1">
            {{ __('lang.edit_translations') }}:
            <span class="font-mono uppercase">{{ $language->code }}</span>
            <span class="text-gray-500 font-normal">— {{ $language->name }}</span>
        </h1>
    </div>

    @if(session('success'))
        <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">{{ session('error') }}</div>
    @endif

    <form method="POST"
          action="{{ route('admin.languages.translations.update', $language) }}"
          @submit="serialize()">
        @csrf @method('PUT')
        <input type="hidden" name="payload" x-ref="payload">

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 mb-4 sticky top-0 z-10">
            <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                <div class="relative flex-1">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input type="text"
                           x-model="search"
                           placeholder="{{ __('lang.search_keys') }}"
                           class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="text-xs text-gray-500 whitespace-nowrap">
                    <span x-text="visibleCount()"></span> / {{ $totalKeys }} {{ __('lang.key') }}
                </div>
                <button type="submit"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition whitespace-nowrap">
                    <i class="fa-solid fa-floppy-disk"></i> {{ __('lang.save_translations') }}
                </button>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($translations as $t)
                <div class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/40"
                     data-key="{{ strtolower($t['key']) }}"
                     data-ref="{{ strtolower($t['reference'] ?? '') }}"
                     x-show="matches($el)">
                    <label class="block text-xs font-mono font-semibold text-gray-500 dark:text-gray-400 mb-1 break-all">
                        {{ $t['key'] }}
                    </label>
                    @if($baseLanguage && !is_null($t['reference']) && $t['reference'] !== '')
                        <div class="mb-1 text-xs text-gray-400 dark:text-gray-500">
                            <span class="inline-flex items-center gap-1">
                                <i class="fa-solid fa-language"></i>
                                {{ __('lang.reference') }} ({{ strtoupper($baseLanguage->code) }}):
                            </span>
                            <span class="text-gray-500 dark:text-gray-400">{{ $t['reference'] }}</span>
                        </div>
                    @endif
                    <textarea
                        data-tkey="{{ $t['key'] }}"
                        rows="1"
                        class="js-tvalue w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >{{ $t['value'] }}</textarea>
                </div>
                @endforeach
            </div>
        </div>

        @if($totalKeys === 0)
            <div class="px-5 py-10 text-center text-gray-400">{{ __('lang.no_keys') }}</div>
        @endif

        <div class="mt-4 flex justify-end">
            <button type="submit"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition">
                <i class="fa-solid fa-floppy-disk"></i> {{ __('lang.save_translations') }}
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function translationEditor() {
    return {
        search: '',
        init() {
            // Auto-grow textareas to fit content.
            this.$root.querySelectorAll('textarea.js-tvalue').forEach(ta => {
                this.autoGrow(ta);
                ta.addEventListener('input', () => this.autoGrow(ta));
            });
        },
        autoGrow(ta) {
            ta.style.height = 'auto';
            ta.style.height = (ta.scrollHeight) + 'px';
        },
        matches(el) {
            const q = this.search.trim().toLowerCase();
            if (!q) return true;
            const key = el.getAttribute('data-key') || '';
            const ref = el.getAttribute('data-ref') || '';
            const val = (el.querySelector('textarea')?.value || '').toLowerCase();
            return key.includes(q) || ref.includes(q) || val.includes(q);
        },
        visibleCount() {
            const q = this.search.trim().toLowerCase();
            const rows = this.$root.querySelectorAll('[data-key]');
            if (!q) return rows.length;
            let n = 0;
            rows.forEach(el => { if (this.matches(el)) n++; });
            return n;
        },
        serialize() {
            // Build a single JSON object from every textarea and drop it into
            // the hidden payload field. This avoids PHP max_input_vars limits
            // when the language file contains hundreds of keys.
            const map = {};
            this.$root.querySelectorAll('textarea.js-tvalue').forEach(ta => {
                const key = ta.getAttribute('data-tkey');
                if (key) map[key] = ta.value;
            });
            this.$refs.payload.value = JSON.stringify(map);
        }
    };
}
</script>
@endpush
