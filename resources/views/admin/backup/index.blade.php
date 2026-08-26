@extends('layouts.admin')

@section('page-title', __('Backup & Restore'))

@section('content')
<div class="w-full max-w-4xl mx-auto space-y-6">

    {{-- ─── Backup Section ───────────────────────────────────────────────── --}}
    <div class="bg-white rounded-lg shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">{{ __('Backup') }}</h2>
            <p class="text-sm text-gray-500 mt-1">{{ __('Select backup contents') }}</p>
        </div>

        <form method="POST" action="{{ route('admin.backup.download') }}"
              x-data="backupForm()" @submit.prevent="submitDownload($el)">
            @csrf

            <div class="p-6 space-y-4">
                {{-- Full Backup (master toggle) --}}
                <label class="flex items-center gap-3 cursor-pointer group">
                    <input type="checkbox"
                           name="groups[]"
                           value="full"
                           x-model="full"
                           @change="toggleAll"
                           class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <span class="text-sm font-semibold text-gray-800">{{ __('Full Backup') }}</span>
                </label>

                <hr class="border-gray-200">

                {{-- Individual groups grid --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @php
                        $backupGroups = [
                            'settings'   => __('Settings'),
                            'blog'       => __('Blog (Posts, Categories, Tags)'),
                            'properties' => __('Properties'),
                            'bookings'   => __('Bookings'),
                            'vouchers'   => __('Vouchers'),
                            'pages'      => __('Pages & Navigation'),
                            'users'      => __('Users'),
                            'media'      => __('Media'),
                            'seo'        => __('SEO & Redirects'),
                        ];
                    @endphp

                    @foreach($backupGroups as $key => $label)
                        <label class="flex items-center gap-3 cursor-pointer group">
                            <input type="checkbox"
                                   name="groups[]"
                                   value="{{ $key }}"
                                   x-model="selected"
                                   :value="'{{ $key }}'"
                                   @change="syncFull"
                                   class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                   :disabled="full">
                            <span class="text-sm text-gray-700 group-has-[:disabled]:text-gray-400">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="px-6 pb-6">
                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <i class="fa-solid fa-download"></i>
                    {{ __('Download Backup') }}
                </button>
            </div>
        </form>
    </div>

    {{-- ─── Restore Section ──────────────────────────────────────────────── --}}
    <div class="bg-white rounded-lg shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">{{ __('Restore') }}</h2>
        </div>

        {{-- Warning banner --}}
        <div class="mx-6 mt-5 flex items-start gap-3 bg-yellow-50 border border-yellow-300 text-yellow-800 px-4 py-3 rounded-md text-sm">
            <i class="fa-solid fa-triangle-exclamation mt-0.5 shrink-0"></i>
            <span>{{ __('Restore will overwrite existing data. Make a backup first.') }}</span>
        </div>

        <form method="POST"
              action="{{ route('admin.backup.restore') }}"
              enctype="multipart/form-data"
              class="p-6 space-y-4">
            @csrf

            <div>
                <label for="backup_file" class="block text-sm font-medium text-gray-700 mb-2">
                    {{ __('Upload backup file (.json)') }}
                </label>
                <input type="file"
                       id="backup_file"
                       name="backup_file"
                       accept=".json"
                       required
                       class="block w-full text-sm text-gray-700
                              file:mr-4 file:py-2 file:px-4
                              file:rounded-md file:border-0
                              file:text-sm file:font-medium
                              file:bg-blue-50 file:text-blue-700
                              hover:file:bg-blue-100
                              focus:outline-none">
                @error('backup_file')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md transition focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                    <i class="fa-solid fa-rotate-left"></i>
                    {{ __('Restore Backup') }}
                </button>
            </div>
        </form>
    </div>

</div>

<script>
function backupForm() {
    return {
        full: false,
        selected: [],

        allKeys: ['settings', 'blog', 'properties', 'bookings', 'vouchers', 'pages', 'users', 'media', 'seo'],

        toggleAll() {
            if (this.full) {
                this.selected = [...this.allKeys];
            }
            // when unchecking full we leave individual selections as-is
        },

        syncFull() {
            if (this.selected.length === this.allKeys.length) {
                this.full = true;
            } else {
                this.full = false;
            }
        },

        /**
         * Build a plain form POST so the browser triggers a file download.
         * We can't use fetch here because the response is a file download,
         * not JSON — we need a real form submission.
         */
        submitDownload(formEl) {
            // Remove any dynamically-added hidden inputs from previous submits
            formEl.querySelectorAll('input[data-dynamic]').forEach(el => el.remove());

            const groups = this.full ? ['full'] : this.selected;

            if (groups.length === 0) {
                alert('{{ __('Select backup contents') }}');
                return;
            }

            // Temporarily uncheck the rendered checkboxes to avoid duplicates,
            // then append hidden inputs for only the chosen groups.
            formEl.querySelectorAll('input[name="groups[]"]').forEach(cb => cb.disabled = true);

            groups.forEach(g => {
                const h = document.createElement('input');
                h.type = 'hidden';
                h.name = 'groups[]';
                h.value = g;
                h.dataset.dynamic = '1';
                formEl.appendChild(h);
            });

            formEl.submit();

            // Re-enable after submit (for SPA-style navigation if ever needed)
            setTimeout(() => {
                formEl.querySelectorAll('input[name="groups[]"][disabled]').forEach(cb => cb.disabled = false);
                formEl.querySelectorAll('input[data-dynamic]').forEach(el => el.remove());
            }, 500);
        }
    }
}
</script>
@endsection
