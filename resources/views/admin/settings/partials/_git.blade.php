{{--
    _git.blade.php — Version Control settings partial
    Displays current git status and provides Update / Check for Updates actions.
    Uses Alpine.js for reactivity; calls JSON endpoints on the SettingsController.
--}}
<div
    x-data="gitVersionControl()"
    x-init="loadStatus()"
    class="space-y-6"
>
    {{-- ===== HEADER ===== --}}
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-gray-900 flex items-center justify-center shrink-0">
            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M2.6 10.59L8.38 4.8l1.69 1.7c-.24.85.15 1.78.93 2.23v5.54c-.6.34-1 .99-1 1.73a2 2 0 002 2 2 2 0 002-2c0-.74-.4-1.39-1-1.73V9.41l2.07 2.09c-.07.15-.07.33-.07.5a2 2 0 002 2 2 2 0 002-2 2 2 0 00-2-2c-.2 0-.37.04-.55.1L13.1 7.47c.14-.32.23-.67.23-1.05A2.5 2.5 0 0010.83 4a2.5 2.5 0 00-2.5 2.42L6.64 8.11 4.21 5.68A1.5 1.5 0 002 6.5v11A1.5 1.5 0 003.5 19h17a1.5 1.5 0 001.5-1.5v-11A1.5 1.5 0 0020.5 6H9.46"/>
            </svg>
        </div>
        <div>
            <h3 class="text-base font-semibold text-gray-900">Version Control</h3>
            <p class="text-sm text-gray-500">View git status and pull updates from remote origin</p>
        </div>
    </div>

    {{-- ===== STATUS CARD ===== --}}
    <div class="rounded-xl border border-gray-200 bg-gray-50 overflow-hidden">
        {{-- Loading skeleton --}}
        <div x-show="loading" class="p-6 space-y-3" aria-live="polite" aria-label="Loading git status">
            <div class="flex items-center gap-2 text-sm text-gray-500">
                <svg class="w-4 h-4 animate-spin text-blue-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                Fetching git status…
            </div>
        </div>

        {{-- Error state --}}
        <div x-show="!loading && error" class="p-6" x-cloak>
            <div class="flex items-start gap-3 text-red-700 bg-red-50 border border-red-200 rounded-lg p-4">
                <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <p class="text-sm font-medium">Failed to load git status</p>
                    <p class="text-xs mt-1 text-red-600" x-text="error"></p>
                </div>
            </div>
        </div>

        {{-- Status info --}}
        <div x-show="!loading && !error && status" x-cloak>
            {{-- Branch + commit row --}}
            <div class="px-6 pt-5 pb-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Branch</p>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-blue-100 text-blue-800 text-xs font-semibold">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            <span x-text="status.branch ?? '—'"></span>
                        </span>
                    </div>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Current Commit</p>
                    <code class="text-xs font-mono bg-white border border-gray-200 px-2 py-1 rounded text-gray-800" x-text="status.current_commit ? status.current_commit.substring(0,12) : '—'"></code>
                </div>
                <div class="sm:col-span-2">
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Commit Message</p>
                    <p class="text-sm text-gray-700 font-medium" x-text="status.current_message ?? '—'"></p>
                </div>
            </div>

            {{-- Update status banner --}}
            <div class="border-t border-gray-200 px-6 py-3">
                <template x-if="status.commits_behind === 0">
                    <div class="flex items-center gap-2 text-green-700 text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Your repository is <strong class="ml-0.5">up to date</strong>.
                    </div>
                </template>
                <template x-if="status.commits_behind > 0">
                    <div class="flex items-center gap-2 text-amber-700 text-sm font-medium">
                        <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        </svg>
                        <span x-text="status.commits_behind + ' commit(s) behind remote'"></span>
                    </div>
                </template>
            </div>

            {{-- Upcoming commits list --}}
            <template x-if="status.upcoming_commits && status.upcoming_commits.length > 0">
                <div class="border-t border-gray-200 px-6 py-4">
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-3">Upcoming Commits</p>
                    <ul class="space-y-2">
                        <template x-for="commit in status.upcoming_commits" :key="commit.hash">
                            <li class="flex items-start gap-2.5 text-sm">
                                <code class="shrink-0 mt-0.5 text-xs font-mono bg-white border border-gray-200 px-1.5 py-0.5 rounded text-gray-500" x-text="commit.hash"></code>
                                <span class="text-gray-700" x-text="commit.message"></span>
                            </li>
                        </template>
                    </ul>
                </div>
            </template>
        </div>
    </div>

    {{-- ===== PULL OUTPUT ===== --}}
    <div x-show="pullOutput" x-cloak class="rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-4 py-2.5 bg-gray-800 flex items-center justify-between">
            <span class="text-xs font-medium text-gray-300">git pull output</span>
            <button type="button" @click="pullOutput = ''" class="text-gray-400 hover:text-gray-200 transition text-xs">✕ Close</button>
        </div>
        <pre class="p-4 text-xs font-mono text-green-400 bg-gray-900 whitespace-pre-wrap overflow-x-auto max-h-48 overflow-y-auto" x-text="pullOutput"></pre>
    </div>

    {{-- ===== ACTION BUTTONS ===== --}}
    <div class="flex flex-wrap items-center gap-3">
        {{-- Check for Updates --}}
        <button
            type="button"
            @click="fetchRemote()"
            :disabled="fetching || pulling"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition shadow-sm"
        >
            <svg class="w-4 h-4" :class="fetching ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            <span x-text="fetching ? 'Fetching…' : 'Check for Updates'"></span>
        </button>

        {{-- Update / Pull --}}
        <button
            type="button"
            @click="pullUpdates()"
            :disabled="pulling || fetching || (status && status.commits_behind === 0)"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-green-600 text-sm font-semibold text-white hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition shadow-sm"
        >
            <svg class="w-4 h-4" :class="pulling ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
            </svg>
            <span x-text="pulling ? 'Pulling…' : 'Update / Pull'"></span>
        </button>

        {{-- Last refreshed --}}
        <span class="text-xs text-gray-400 ml-auto" x-show="lastChecked" x-text="'Last checked: ' + lastChecked" x-cloak></span>
    </div>
</div>

<script>
function gitVersionControl() {
    return {
        loading: true,
        fetching: false,
        pulling: false,
        status: null,
        error: null,
        pullOutput: '',
        lastChecked: '',

        async loadStatus() {
            this.loading = true;
            this.error = null;
            try {
                const res = await fetch('{{ route('admin.settings.git-status') }}', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const json = await res.json();
                if (!res.ok) throw new Error(json.error ?? 'Server error');
                this.status = json;
                this.lastChecked = new Date().toLocaleTimeString();
            } catch (e) {
                this.error = e.message;
            } finally {
                this.loading = false;
            }
        },

        async fetchRemote() {
            this.fetching = true;
            this.error = null;
            try {
                const res = await fetch('{{ route('admin.settings.git-fetch') }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    }
                });
                const json = await res.json();
                if (!res.ok || !json.success) throw new Error(json.error ?? 'Fetch failed');
                if (window.toast) window.toast('Remote fetched successfully', 'success');
                await this.loadStatus();
            } catch (e) {
                this.error = e.message;
                if (window.toast) window.toast(e.message, 'error');
            } finally {
                this.fetching = false;
            }
        },

        async pullUpdates() {
            this.pulling = true;
            this.pullOutput = '';
            this.error = null;
            try {
                const res = await fetch('{{ route('admin.settings.git-pull') }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    }
                });
                const json = await res.json();
                if (!res.ok || !json.success) throw new Error(json.error ?? 'Pull failed');
                this.pullOutput = json.output ?? '';
                if (window.toast) window.toast('Git pull completed successfully', 'success');
                await this.loadStatus();
            } catch (e) {
                this.error = e.message;
                if (window.toast) window.toast(e.message, 'error');
            } finally {
                this.pulling = false;
            }
        },
    };
}
</script>
