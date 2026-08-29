{{--
    _git.blade.php — Version Control settings partial
    Displays current git status and provides Update / Check for Updates actions.
    After a successful pull, shows only the post-update action buttons that are
    actually needed (derived from the changed file list server-side).
    Uses Alpine.js for reactivity; calls JSON endpoints on the SettingsController.
--}}
<div
    x-data="gitVersionControl()"
    x-init="loadStatus(); loadRemote(); loadCommits();"
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
            <h3 class="text-base font-semibold text-gray-900">{{ __('Git Version Control') }}</h3>
            <p class="text-sm text-gray-500">{{ __('View git status and pull updates from remote origin') }}</p>
        </div>
    </div>

    {{-- ===== STATUS CARD ===== --}}
    <div class="rounded-xl border border-gray-200 bg-gray-50 overflow-hidden">
        {{-- Loading skeleton --}}
        <div x-show="loading" class="p-6 space-y-3" aria-live="polite" aria-label="{{ __('Loading git status') }}">
            <div class="flex items-center gap-2 text-sm text-gray-500">
                <svg class="w-4 h-4 animate-spin text-blue-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                {{ __('Fetching git status…') }}
            </div>
        </div>

        {{-- Error state --}}
        <div x-show="!loading && error" class="p-6" x-cloak>
            <div class="flex items-start gap-3 text-red-700 bg-red-50 border border-red-200 rounded-lg p-4">
                <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <p class="text-sm font-medium">{{ __('Gagal load git status') }}</p>
                    <p class="text-xs mt-1 text-red-600" x-text="error"></p>
                </div>
            </div>
        </div>

        {{-- Status info --}}
        <div x-show="!loading && !error && status" x-cloak>
            {{-- Branch + commit row --}}
            <div class="px-6 pt-5 pb-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">{{ __('Branch') }}</p>
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
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">{{ __('Commit Terakhir') }}</p>
                    <code class="text-xs font-mono bg-white border border-gray-200 px-2 py-1 rounded text-gray-800" x-text="status.current_commit ? status.current_commit.substring(0,12) : '—'"></code>
                </div>
                <div class="sm:col-span-2">
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">{{ __('Pesan Commit') }}</p>
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
                        {{ __('Your repository is') }} <strong class="ml-0.5">{{ __('sudah terbaru') }}</strong>.
                    </div>
                </template>
                <template x-if="status.commits_behind > 0">
                    <div class="flex items-center gap-2 text-amber-700 text-sm font-medium">
                        <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        </svg>
                        <span x-text="status.commits_behind + ' {{ __('commit tertinggal dari remote') }}'"></span>
                    </div>
                </template>
            </div>

            {{-- Upcoming commits list --}}
            <template x-if="status.upcoming_commits && status.upcoming_commits.length > 0">
                <div class="border-t border-gray-200 px-6 py-4">
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-3">{{ __('Commit yang akan datang') }}</p>
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

    {{-- ===== ON-DEMAND UPDATE CHECK ===== --}}
    {{-- Admin can trigger a fresh check without waiting for 01:00 WIB scheduler. --}}
    <div
        x-data="{
            checking: false,
            checkMsg: '',
            checkError: '',
            async triggerCheck() {
                this.checking = true;
                this.checkMsg = '';
                this.checkError = '';
                try {
                    const res = await fetch('{{ route('admin.settings.git-check-updates') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            'Accept': 'application/json',
                        },
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.checkMsg = data.message;
                        // Reload git status panel so it reflects the fresh check
                        this.\$dispatch('git-update-checked');
                    } else {
                        this.checkError = data.error || '{{ __('git.check_updates_failed') }}';
                    }
                } catch (e) {
                    this.checkError = '{{ __('git.check_updates_failed') }}';
                } finally {
                    this.checking = false;
                }
            }
        }"
        class="flex flex-col sm:flex-row sm:items-center gap-3"
    >
        <button
            type="button"
            @click="triggerCheck()"
            :disabled="checking"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed transition focus:outline-none focus:ring-2 focus:ring-blue-500"
            aria-label="{{ __('git.check_now_aria') }}"
        >
            <svg class="w-4 h-4" :class="checking ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            <span x-text="checking ? '{{ __('git.checking') }}' : '{{ __('git.check_now_label') }}'"></span>
        </button>

        <p x-show="checkMsg !== ''" class="text-sm text-green-700 font-medium" x-text="checkMsg" x-cloak></p>
        <p x-show="checkError !== ''" class="text-sm text-red-600" x-text="checkError" x-cloak></p>
    </div>

    {{-- ===== PULL OUTPUT (reused as the single terminal output panel) ===== --}}
    <div x-show="pullOutput" x-cloak class="rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-4 py-2.5 bg-gray-800 flex items-center justify-between">
            <span class="text-xs font-medium text-gray-300" x-text="outputLabel"></span>
            <button type="button" @click="pullOutput = ''; outputLabel = ''" class="text-gray-400 hover:text-gray-200 transition text-xs">✕ {{ __('Close') }}</button>
        </div>
        <pre class="p-4 text-xs font-mono text-green-400 bg-gray-900 whitespace-pre-wrap overflow-x-auto max-h-64 overflow-y-auto" x-text="pullOutput"></pre>
    </div>

    {{-- ===== POST-UPDATE ACTION BUTTONS ===== --}}
    {{-- Shown only after a pull returns needed_actions. Hidden when empty (nothing changed). --}}
    <div x-show="neededActions.length > 0" x-cloak class="rounded-xl border border-amber-200 bg-amber-50 p-5 space-y-4">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            <div>
                <p class="text-sm font-semibold text-amber-800">{{ __('Post-update actions required') }}</p>
                <p class="text-xs text-amber-700 mt-0.5">{{ __('The pull introduced changes that require the following actions to be run.') }}</p>
            </div>
        </div>

        <div class="flex flex-wrap gap-3">
            {{-- Composer dependencies --}}
            <template x-if="neededActions.includes('composer')">
                <button
                    type="button"
                    @click="runAction('composer')"
                    :disabled="runningAction !== null"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-gray-800 text-white text-sm font-medium hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition shadow-sm"
                >
                    <svg class="w-4 h-4" :class="runningAction === 'composer' ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span x-text="runningAction === 'composer' ? '{{ __('Installing…') }}' : '{{ __('Install Composer Dependencies') }}'"></span>
                </button>
            </template>

            {{-- Assets with lockfile (npm ci) --}}
            <template x-if="neededActions.includes('assets_ci')">
                <button
                    type="button"
                    @click="runAction('assets_ci')"
                    :disabled="runningAction !== null"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition shadow-sm"
                >
                    <svg class="w-4 h-4" :class="runningAction === 'assets_ci' ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    <span x-text="runningAction === 'assets_ci' ? '{{ __('Building…') }}' : '{{ __('Rebuild Assets') }}'"></span>
                </button>
            </template>

            {{-- Assets without lockfile change (npm install) --}}
            <template x-if="neededActions.includes('assets')">
                <button
                    type="button"
                    @click="runAction('assets')"
                    :disabled="runningAction !== null"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition shadow-sm"
                >
                    <svg class="w-4 h-4" :class="runningAction === 'assets' ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    <span x-text="runningAction === 'assets' ? '{{ __('Building…') }}' : '{{ __('Rebuild Assets') }}'"></span>
                </button>
            </template>

            {{-- Migrations — confirm step required --}}
            <template x-if="neededActions.includes('migrate')">
                <button
                    type="button"
                    @click="confirmMigrate()"
                    :disabled="runningAction !== null"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition shadow-sm"
                >
                    <svg class="w-4 h-4" :class="runningAction === 'migrate' ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>
                    </svg>
                    <span x-text="runningAction === 'migrate' ? '{{ __('Migrating…') }}' : '{{ __('Run Migrations') }}'"></span>
                </button>
            </template>

            {{-- Clear & rebuild caches --}}
            <template x-if="neededActions.includes('caches')">
                <button
                    type="button"
                    @click="runAction('caches')"
                    :disabled="runningAction !== null"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition shadow-sm"
                >
                    <svg class="w-4 h-4" :class="runningAction === 'caches' ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                    </svg>
                    <span x-text="runningAction === 'caches' ? '{{ __('Clearing…') }}' : '{{ __('Clear & Rebuild Caches') }}'"></span>
                </button>
            </template>
        </div>

        {{-- Action error --}}
        <div x-show="actionError" x-cloak>
            <p class="text-xs text-red-600 font-medium" x-text="actionError"></p>
        </div>
    </div>

    {{-- ===== NO FURTHER ACTION NOTE (shown after a pull that changed nothing relevant) ===== --}}
    <div x-show="showNoActionNote" x-cloak class="rounded-xl border border-green-200 bg-green-50 px-5 py-4 flex items-center gap-3">
        <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        <p class="text-sm text-green-800">{{ __('Pull complete — no further action required.') }}</p>
        <button type="button" @click="showNoActionNote = false" class="ml-auto text-green-600 hover:text-green-800 text-xs">✕</button>
    </div>

    {{-- ===== GIT OPERATION BUTTONS ===== --}}
    <div class="flex flex-wrap items-center gap-3">
        {{-- Check for Updates --}}
        <button
            type="button"
            @click="fetchRemote()"
            :disabled="fetching || pulling || runningAction !== null"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition shadow-sm"
        >
            <svg class="w-4 h-4" :class="fetching ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            <span x-text="fetching ? '{{ __('Fetching…') }}' : '{{ __('Check for Updates') }}'"></span>
        </button>

        {{-- Update / Pull --}}
        <button
            type="button"
            @click="pullUpdates()"
            :disabled="pulling || fetching || runningAction !== null || (status && status.commits_behind === 0)"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-green-600 text-sm font-semibold text-white hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition shadow-sm"
        >
            <svg class="w-4 h-4" :class="pulling ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
            </svg>
            <span x-text="pulling ? '{{ __('Pulling…') }}' : '{{ __('Update / Pull') }}'"></span>
        </button>

        {{-- Last refreshed --}}
        <span class="text-xs text-gray-400 ml-auto" x-show="lastChecked" x-text="'{{ __('Last checked') }}: ' + lastChecked" x-cloak></span>
    </div>

    {{-- ===== REMOTE ORIGIN INFO ===== --}}
    <div x-show="!loading && !error && remote" x-cloak class="rounded-xl border border-gray-200 bg-gray-50 overflow-hidden">
        <div class="px-5 py-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="sm:col-span-3">
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">{{ __('Remote Origin') }}</p>
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101M10.172 13.828a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                    </svg>
                    <code class="text-sm font-mono text-gray-800 break-all" x-text="remote.remote_url || '—'"></code>
                </div>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">{{ __('Branch') }}</p>
                <template x-if="remote.is_detached">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-100 text-amber-800 text-xs font-semibold">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                            </svg>
                            <span>{{ __('Detached HEAD') }} · <span class="font-mono" x-text="remote.detached_short"></span></span>
                        </span>
                        <button
                            type="button"
                            @click="returnToBranch()"
                            :disabled="returningBranch || runningAction !== null"
                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-blue-600 text-white text-xs font-medium hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
                        >
                            <svg class="w-3 h-3" :class="returningBranch ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            <span x-text="returningBranch ? '{{ __('Returning…') }}' : '{{ __('Return to branch tip') }}'"></span>
                        </button>
                    </div>
                </template>
                <template x-if="!remote.is_detached">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-blue-100 text-blue-800 text-xs font-semibold">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        <span x-text="remote.branch || '—'"></span>
                    </span>
                </template>
            </div>
            <div class="sm:col-span-2">
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">{{ __('Upstream') }}</p>
                <code class="text-xs font-mono bg-white border border-gray-200 px-2 py-1 rounded text-gray-700" x-text="remote.upstream || '—'"></code>
            </div>
        </div>
    </div>

    {{-- ===== COMMIT HISTORY TABLE ===== --}}
    <div x-show="!loading && !error" x-cloak class="rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between gap-3">
            <div>
                <h4 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                    </svg>
                    {{ __('Riwayat Commit') }}
                </h4>
                <p class="text-xs text-gray-500 mt-0.5">{{ __('Daftar commit terbaru dari remote. Rollback memindahkan kode ke commit yang dipilih.') }}</p>
            </div>
            <button
                type="button"
                @click="loadCommits()"
                :disabled="loadingCommits || runningAction !== null"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-300 bg-white text-xs font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition"
            >
                <svg class="w-3.5 h-3.5" :class="loadingCommits ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <span x-text="loadingCommits ? '{{ __('Loading…') }}' : '{{ __('Refresh') }}'"></span>
            </button>
        </div>

        {{-- Commits table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Waktu Commit') }}</th>
                        <th scope="col" class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Commit Message') }}</th>
                        <th scope="col" class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Author') }}</th>
                        <th scope="col" class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Commit ID') }}</th>
                        <th scope="col" class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Branch') }}</th>
                        <th scope="col" class="px-4 py-2.5 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    <template x-for="commit in commits" :key="commit.full_hash">
                        <tr :class="commit.is_head ? 'bg-blue-50/50' : ''">
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500" x-text="formatTime(commit.date_iso)"></td>
                            <td class="px-4 py-3 text-sm text-gray-800">
                                <div class="flex items-center gap-2">
                                    <template x-if="commit.is_head">
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-blue-100 text-blue-700 text-[10px] font-bold uppercase tracking-wide">{{ __('HEAD') }}</span>
                                    </template>
                                    <span class="font-medium" x-text="commit.subject"></span>
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                                <span class="inline-flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    <span x-text="commit.author"></span>
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <code class="text-xs font-mono bg-gray-100 border border-gray-200 px-1.5 py-0.5 rounded text-gray-600" :title="commit.full_hash" x-text="commit.short_hash"></code>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-500">
                                <template x-if="commit.branches.length > 0">
                                    <span class="inline-flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                        </svg>
                                        <span class="font-mono" x-text="commit.branches.join(', ')"></span>
                                    </span>
                                </template>
                                <template x-if="commit.branches.length === 0">
                                    <span class="text-gray-400">—</span>
                                </template>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right">
                                <button
                                    type="button"
                                    @click="openRollback(commit)"
                                    :disabled="commit.is_head || runningAction !== null"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-red-600 text-white text-xs font-medium hover:bg-red-700 disabled:opacity-40 disabled:cursor-not-allowed transition shadow-sm"
                                    :title="commit.is_head ? '{{ __('Cannot roll back to the current HEAD') }}' : ''"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                    </svg>
                                    {{ __('Rollback') }}
                                </button>
                            </td>
                        </tr>
                    </template>
                    <template x-if="!loadingCommits && commits.length === 0">
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">{{ __('No commits found.') }}</td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        {{-- Show more --}}
        <div x-show="commits.length > 0" class="px-5 py-3 border-t border-gray-200 bg-gray-50 flex items-center justify-center">
            <button
                type="button"
                @click="showMoreCommits()"
                :disabled="loadingCommits || runningAction !== null"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition"
            >
                <svg class="w-4 h-4" :class="loadingCommits ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
                {{ __('Show more') }}
            </button>
        </div>
    </div>

    {{-- ===== ROLLBACK WARNING MODAL ===== --}}
    <div
        x-show="rollbackCommit !== null"
        x-cloak
        class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div class="fixed inset-0 bg-gray-500 opacity-75" @click="rollbackCommit = null"></div>
        <div class="mb-6 bg-white rounded-lg overflow-hidden shadow-xl transform transition-all sm:w-full sm:max-w-2xl sm:mx-auto">
            <div class="px-6 pt-6 pb-4 border-b border-gray-200 flex items-start gap-3">
                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-gray-900">{{ __('Rollback ke Commit Ini?') }}</h3>
                    <p class="text-sm text-gray-500 mt-0.5">
                        {{ __('Rollback akan memindahkan kode ke') }}
                        <code class="font-mono text-xs bg-gray-100 px-1.5 py-0.5 rounded" x-text="rollbackCommit ? rollbackCommit.short_hash : ''"></code>
                        <span class="text-gray-400" x-text="rollbackCommit ? ' — ' + rollbackCommit.subject : ''"></span>
                    </p>
                </div>
            </div>

            <div class="px-6 py-5 space-y-4">
                {{-- Danger warning --}}
                <div class="rounded-lg bg-red-50 border border-red-200 p-4 text-sm text-red-800">
                    <p class="font-semibold">{{ __('Peringatan: Database TIDAK ikut di-rollback') }}</p>
                    <p class="mt-1.5 text-xs leading-relaxed text-red-700">
                        {{ __('Rollback kode TIDAK mengembalikan skema database. Jika commit tujuan dibuat SEBELUM migrasi terbaru, skema database akan lebih baru daripada kode. Migrasi yang sudah dijalankan TIDAK otomatis dibatalkan.') }}
                    </p>
                    <p class="mt-1.5 text-xs text-red-700">
                        {{ __('Sangat disarankan untuk membuat backup database terlebih dahulu sebelum melanjutkan.') }}
                    </p>
                </div>

                {{-- Detached HEAD notice --}}
                <div class="rounded-lg bg-amber-50 border border-amber-200 p-4 text-sm text-amber-800">
                    <p class="font-semibold">{{ __('Hasil: Detached HEAD') }}</p>
                    <p class="mt-1 text-xs leading-relaxed text-amber-700">
                        {{ __('Rollback menggunakan git checkout <commit>, sehingga repo masuk ke mode detached HEAD. Anda dapat kembali ke ujung branch kapan saja lewat tombol "Kembali ke ujung branch". Riwayat commit tidak diubah.') }}
                    </p>
                </div>

                {{-- Progress steps --}}
                <div x-show="rollbackSteps.length > 0" class="space-y-2">
                    <template x-for="step in rollbackSteps" :key="step.label">
                        <div class="flex items-start gap-2.5 text-sm">
                            <template x-if="step.status === 'done'">
                                <svg class="w-4 h-4 text-green-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </template>
                            <template x-if="step.status === 'warning'">
                                <svg class="w-4 h-4 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                                </svg>
                            </template>
                            <template x-if="step.status === 'failed'">
                                <svg class="w-4 h-4 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </template>
                            <template x-if="step.status === 'info' || step.status === 'pending'">
                                <svg class="w-4 h-4 text-blue-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </template>
                            <div>
                                <p class="font-medium text-gray-800" x-text="step.label"></p>
                                <p class="text-xs text-gray-500 mt-0.5" x-text="step.detail"></p>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Backup output --}}
                <div x-show="backupOutput" x-cloak class="rounded-lg bg-gray-50 border border-gray-200 p-3">
                    <p class="text-xs font-medium text-gray-700 mb-1" x-text="backupOutputLabel"></p>
                    <p class="text-xs font-mono text-gray-600 break-all" x-text="backupOutput"></p>
                </div>

                {{-- Rollback error --}}
                <div x-show="rollbackError" x-cloak class="rounded-lg bg-red-50 border border-red-200 p-3">
                    <p class="text-xs text-red-700 font-medium" x-text="rollbackError"></p>
                </div>

                {{-- Completion --}}
                <div x-show="rollbackDone" x-cloak class="rounded-lg bg-green-50 border border-green-200 p-4 flex items-center gap-3">
                    <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-green-800">{{ __('Rollback selesai') }}</p>
                        <p class="text-xs text-green-700 mt-0.5">{{ __('Kode sekarang berada di commit yang dipilih. Jangan lupa membersihkan cache.') }}</p>
                    </div>
                    <div class="flex gap-2 shrink-0">
                        <button
                            type="button"
                            @click="clearCacheNow()"
                            :disabled="clearingCache"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-600 text-white text-xs font-medium hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
                        >
                            <svg class="w-3.5 h-3.5" :class="clearingCache ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                            </svg>
                            <span x-text="clearingCache ? '{{ __('Clearing…') }}' : '{{ __('Clear Cache') }}'"></span>
                        </button>
                        <button
                            type="button"
                            @click="closeRollback()"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-xs font-medium hover:bg-gray-50 transition"
                        >
                            {{ __('Tutup') }}
                        </button>
                    </div>
                </div>
            </div>

            {{-- Footer actions --}}
            <div x-show="!rollbackDone" class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex flex-wrap items-center justify-end gap-3">
                <button
                    type="button"
                    @click="backupDatabase()"
                    :disabled="backingUp || rollingBack"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition"
                >
                    <svg class="w-4 h-4" :class="backingUp ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    <span x-text="backingUp ? '{{ __('Membuat backup…') }}' : '{{ __('Backup Database') }}'"></span>
                </button>
                <button
                    type="button"
                    @click="confirmRollback()"
                    :disabled="rollingBack || backingUp"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-semibold hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition shadow-sm"
                >
                    <svg class="w-4 h-4" :class="rollingBack ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                    </svg>
                    <span x-text="rollingBack ? '{{ __('Menggulung kembali…') }}' : '{{ __('Saya sudah backup, lanjutkan') }}'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function gitVersionControl() {
    return {
        loading: true,
        fetching: false,
        pulling: false,
        // The action key currently executing (null = idle).
        runningAction: null,
        status: {
            branch: null,
            current_commit: null,
            current_message: null,
            commits_behind: 0,
            upcoming_commits: [],
            needed_actions: [],
        },
        error: null,
        // Single output panel shared by git pull + all post-update actions.
        pullOutput: '',
        outputLabel: '',
        // Populated from the server response after a pull.
        neededActions: [],
        // Shown after a pull that changed nothing configuration-relevant.
        showNoActionNote: false,
        // Error from a post-update action.
        actionError: '',
        lastChecked: '',

        // Remote origin info (loaded from git-remote-info).
        remote: null,
        // Commit history table state.
        commits: [],
        loadingCommits: false,
        commitDisplayLimit: 5,
        commitIncrement: 20,
        // Return-to-branch-tip state.
        returningBranch: false,
        // Rollback modal state.
        rollbackCommit: null,
        rollbackSteps: [],
        rollbackError: '',
        rollbackDone: false,
        rollingBack: false,
        backingUp: false,
        backupOutput: '',
        backupOutputLabel: '',
        clearingCache: false,

        // Parse a fetch Response as JSON, but fail with a clear message when the
        // server returned an HTML page instead (expired session -> 302 /login,
        // 419 CSRF, 403, or a 500 error page). Without this guard res.json()
        // throws "Unexpected token '<'" on the <!DOCTYPE of those HTML pages.
        async parseJson(res) {
            const ct = res.headers.get('content-type') || '';
            if (res.redirected || !ct.includes('application/json')) {
                if (res.status === 401 || res.status === 419 || res.redirected) {
                    throw new Error('{{ __('Session expired. Reload the page and log in again.') }}');
                }
                if (res.status === 403) {
                    throw new Error('{{ __('Access denied. Your account does not have admin rights.') }}');
                }
                throw new Error(`{{ __('Invalid server response') }} (HTTP ${res.status}).`);
            }
            return res.json();
        },

        async loadStatus() {
            this.loading = true;
            this.error = null;
            try {
                const res = await fetch('{{ route('admin.settings.git-status') }}', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const json = await this.parseJson(res);
                if (!res.ok) throw new Error(json.error ?? '{{ __('Server error') }}');
                this.status = json;
                // If the status endpoint already knows what would be needed (commits
                // are pending), surface that so the admin can see it before pulling.
                if (Array.isArray(json.needed_actions) && json.needed_actions.length > 0) {
                    this.neededActions = json.needed_actions;
                }
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
                const json = await this.parseJson(res);
                if (!res.ok || !json.success) throw new Error(json.error ?? '{{ __('Fetch failed') }}');
                if (window.toast) window.toast('{{ __('Remote fetched successfully') }}', 'success');
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
            this.outputLabel = '{{ __('git pull output') }}';
            this.error = null;
            this.neededActions = [];
            this.showNoActionNote = false;
            this.actionError = '';
            try {
                const res = await fetch('{{ route('admin.settings.git-pull') }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    }
                });
                const json = await this.parseJson(res);
                if (!res.ok || !json.success) throw new Error(json.error ?? '{{ __('Pull failed') }}');
                this.pullOutput = json.output ?? '';
                if (window.toast) window.toast('{{ __('Git pull completed successfully') }}', 'success');
                // Surface post-update actions derived from the changed file list.
                const actions = Array.isArray(json.needed_actions) ? json.needed_actions : [];
                this.neededActions = actions;
                if (actions.length === 0) {
                    this.showNoActionNote = true;
                }
                await this.loadStatus();
            } catch (e) {
                this.error = e.message;
                if (window.toast) window.toast(e.message, 'error');
            } finally {
                this.pulling = false;
            }
        },

        // Confirm guard for the migrate action only (destructive-adjacent).
        confirmMigrate() {
            if (!confirm('{{ __('Run database migrations? This will apply all pending migrations to the production database and cannot be easily undone. Continue?') }}')) {
                return;
            }
            this.runAction('migrate');
        },

        // Load remote origin info (credential-redacted URL, branch, upstream).
        async loadRemote() {
            try {
                const res = await fetch('{{ route('admin.settings.git-remote-info') }}', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const json = await this.parseJson(res);
                if (!res.ok || !json.success) throw new Error(json.error ?? '{{ __('Server error') }}');
                this.remote = json.remote;
            } catch (e) {
                // Non-fatal: the commit table still renders without remote info.
                this.remote = null;
            }
        },

        // Format a commit ISO-8601 timestamp. Less than 1 day old → relative
        // string; otherwise DD/MM/YYYY HH:mm in Asia/Jakarta (WIB). The server
        // sends the raw %cI timestamp; formatting happens here so the 1-day
        // threshold and date layout are always consistent.
        formatTime(iso) {
            if (!iso) return '—';
            const commit = new Date(iso);
            if (isNaN(commit.getTime())) return '—';
            const now = new Date();
            const diffMs = now - commit;
            const DAY = 24 * 60 * 60 * 1000;
            if (diffMs >= 0 && diffMs < DAY) {
                const mins = Math.floor(diffMs / 60000);
                if (mins >= 60) {
                    const hours = Math.floor(mins / 60);
                    return '{{ __(':count jam yang lalu', ['count' => '']) }}'.replace(':count', hours) || (hours + ' ' + '{{ __('jam yang lalu') }}');
                }
                if (mins >= 1) {
                    return '{{ __(':count menit yang lalu', ['count' => '']) }}'.replace(':count', mins) || (mins + ' ' + '{{ __('menit yang lalu') }}');
                }
                return '{{ __('Baru saja') }}';
            }
            // Convert to Asia/Jakarta (WIB, UTC+7) and format DD/MM/YYYY HH:mm.
            const wib = new Date(commit.getTime() + 7 * 60 * 60 * 1000);
            const pad = (n) => String(n).padStart(2, '0');
            return pad(wib.getUTCDate()) + '/' + pad(wib.getUTCMonth() + 1) + '/' + wib.getUTCFullYear() + ' ' + pad(wib.getUTCHours()) + ':' + pad(wib.getUTCMinutes());
        },

        // Load the commit history table (initial page or refresh).
        async loadCommits() {
            this.loadingCommits = true;
            try {
                const res = await fetch(
                    '{{ route('admin.settings.git-commit-history') }}?limit=' + this.commitDisplayLimit + '&skip=0',
                    { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }
                );
                const json = await this.parseJson(res);
                if (!res.ok || !json.success) throw new Error(json.error ?? '{{ __('Server error') }}');
                this.commits = json.commits ?? [];
                if (json.display_limit) this.commitDisplayLimit = json.display_limit;
                if (json.increment) this.commitIncrement = json.increment;
            } catch (e) {
                if (window.toast) window.toast(e.message, 'error');
            } finally {
                this.loadingCommits = false;
            }
        },

        // "Show more" — append the next increment of commits.
        async showMoreCommits() {
            this.loadingCommits = true;
            try {
                const skip = this.commits.length;
                const res = await fetch(
                    '{{ route('admin.settings.git-commit-history') }}?limit=' + this.commitIncrement + '&skip=' + skip,
                    { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }
                );
                const json = await this.parseJson(res);
                if (!res.ok || !json.success) throw new Error(json.error ?? '{{ __('Server error') }}');
                const more = json.commits ?? [];
                // Append only commits we don't already have (dedupe by full hash).
                const existing = new Set(this.commits.map(c => c.full_hash));
                this.commits.push(...more.filter(c => !existing.has(c.full_hash)));
            } catch (e) {
                if (window.toast) window.toast(e.message, 'error');
            } finally {
                this.loadingCommits = false;
            }
        },

        // Open the rollback warning modal for a specific commit.
        openRollback(commit) {
            if (commit.is_head) return;
            this.rollbackCommit = commit;
            this.rollbackSteps = [];
            this.rollbackError = '';
            this.rollbackDone = false;
            this.backupOutput = '';
        },

        closeRollback() {
            this.rollbackCommit = null;
            this.rollbackSteps = [];
            this.rollbackError = '';
            this.rollbackDone = false;
            this.backupOutput = '';
        },

        // Create a full .sql backup via the existing backup system.
        async backupDatabase() {
            this.backingUp = true;
            this.backupOutput = '';
            this.rollbackError = '';
            try {
                const res = await fetch('{{ route('admin.settings.git-backup-database') }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    }
                });
                const json = await this.parseJson(res);
                if (!res.ok || !json.success) throw new Error(json.error ?? '{{ __('Action failed') }}');
                this.backupOutputLabel = '{{ __('Backup Database') }}';
                this.backupOutput = json.message + (json.path ? ' — ' + json.path : '');
                if (window.toast) window.toast('{{ __('Database backup created successfully') }}', 'success');
            } catch (e) {
                this.rollbackError = e.message;
                if (window.toast) window.toast(e.message, 'error');
            } finally {
                this.backingUp = false;
            }
        },

        // Proceed with the rollback (assumes the user has already backed up).
        async confirmRollback() {
            if (!this.rollbackCommit) return;
            this.rollingBack = true;
            this.rollbackError = '';
            this.rollbackSteps = [];
            let responseJson = null;
            try {
                const res = await fetch('{{ route('admin.settings.git-rollback') }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ sha: this.rollbackCommit.full_hash })
                });
                responseJson = await this.parseJson(res);
                if (!res.ok || !responseJson.success) throw new Error(responseJson.error ?? '{{ __('Action failed') }}');
                this.rollbackSteps = responseJson.steps ?? [];
                this.rollbackDone = true;
                if (window.toast) window.toast('{{ __('Rollback selesai') }}', 'success');
                // Refresh status + remote + commits so the UI reflects the new HEAD.
                this.loadStatus();
                this.loadRemote();
                this.loadCommits();
            } catch (e) {
                this.rollbackError = e.message;
                if (responseJson && responseJson.steps) this.rollbackSteps = responseJson.steps;
                if (window.toast) window.toast(e.message, 'error');
            } finally {
                this.rollingBack = false;
            }
        },

        // Return to the branch tip from detached HEAD.
        async returnToBranch() {
            this.returningBranch = true;
            try {
                const res = await fetch('{{ route('admin.settings.git-return-to-branch') }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    }
                });
                const json = await this.parseJson(res);
                if (!res.ok || !json.success) throw new Error(json.error ?? '{{ __('Action failed') }}');
                if (window.toast) window.toast(json.message ?? '{{ __('Returned to branch tip') }}', 'success');
                this.loadRemote();
                this.loadStatus();
                this.loadCommits();
            } catch (e) {
                if (window.toast) window.toast(e.message, 'error');
            } finally {
                this.returningBranch = false;
            }
        },

        // Clear the application caches after a rollback.
        async clearCacheNow() {
            this.clearingCache = true;
            try {
                const res = await fetch('{{ route('admin.clear-cache') }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    }
                });
                const json = await this.parseJson(res);
                if (!res.ok || !json.success) throw new Error(json.error ?? '{{ __('Action failed') }}');
                if (window.toast) window.toast(json.message ?? '{{ __('Cache cleared successfully') }}', 'success');
            } catch (e) {
                if (window.toast) window.toast(e.message, 'error');
            } finally {
                this.clearingCache = false;
            }
        },

        // Execute one allowlisted post-update action.
        // Only the key is sent; the server maps it to a hardcoded argv array (SEC-03).
        async runAction(key) {
            this.runningAction = key;
            this.pullOutput = '';
            this.outputLabel = key;
            this.actionError = '';
            try {
                const res = await fetch(
                    '{{ url(route('admin.settings.post-update', ['action' => '__KEY__'], false)) }}'.replace('__KEY__', key),
                    {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        }
                    }
                );
                const json = await this.parseJson(res);
                if (!res.ok || !json.success) throw new Error(json.error ?? '{{ __('Action failed') }}');
                this.pullOutput = json.output ?? '';
                // Remove the completed action from the list.
                this.neededActions = this.neededActions.filter(a => a !== key);
                if (window.toast) window.toast('{{ __('Action completed successfully') }}', 'success');
            } catch (e) {
                this.actionError = e.message;
                if (window.toast) window.toast(e.message, 'error');
            } finally {
                this.runningAction = null;
            }
        },
    };
}
</script>
