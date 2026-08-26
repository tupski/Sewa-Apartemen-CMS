{{--
    Global Share Modal (Alpine.js)

    A single, app-wide share modal. Any "Share" trigger anywhere on the page
    dispatches a `open-share-modal` window event carrying the target URL + title:

        window.dispatchEvent(new CustomEvent('open-share-modal', {
            detail: { url: 'https://…', title: 'Property name' }
        }))

    This keeps state global (one modal instance) while still letting each card /
    button share its own target — the payload is passed on open, so cards in a
    loop don't each need their own Alpine component.

    Share targets: WhatsApp, SMS, Telegram, X (Twitter), Facebook,
    Instagram (copy-link hint), and Copy Link (with "Copied!" state).
--}}
<div
    x-data="shareModal()"
    x-on:open-share-modal.window="open($event.detail)"
    x-on:keydown.escape.window="close()"
    x-show="isOpen"
    x-cloak
    class="fixed inset-0 z-[70] flex items-end sm:items-center justify-center"
    role="dialog"
    aria-modal="true"
    aria-labelledby="share-modal-title"
    style="display: none;"
>
    {{-- Backdrop --}}
    <div
        x-show="isOpen"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="absolute inset-0 bg-black/60"
        x-on:click="close()"
        aria-hidden="true"
    ></div>

    {{-- Panel --}}
    <div
        x-show="isOpen"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-6 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-6 sm:translate-y-0 sm:scale-95"
        class="relative w-full sm:max-w-md bg-white dark:bg-gray-800 rounded-t-2xl sm:rounded-2xl shadow-2xl overflow-hidden"
    >
        {{-- Header --}}
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-700">
            <h3 id="share-modal-title" class="text-base font-bold text-gray-900 dark:text-white">
                {{ __('share.title') }}
            </h3>
            <button
                type="button"
                x-on:click="close()"
                class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:text-gray-200 dark:hover:bg-gray-700 transition"
                aria-label="{{ __('share.close') }}"
            >
                <i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i>
            </button>
        </div>

        {{-- Share targets --}}
        <div class="p-5">
            <div class="grid grid-cols-4 gap-3">
                {{-- WhatsApp --}}
                <a :href="waUrl" target="_blank" rel="noopener"
                   class="flex flex-col items-center gap-2 group focus:outline-none"
                   aria-label="{{ __('share.whatsapp') }}">
                    <span class="w-12 h-12 flex items-center justify-center rounded-full bg-[#25D366] text-white group-hover:scale-110 transition-transform">
                        <i class="fa-brands fa-whatsapp text-xl" aria-hidden="true"></i>
                    </span>
                    <span class="text-[11px] text-gray-600 dark:text-gray-400 text-center leading-tight">{{ __('share.whatsapp') }}</span>
                </a>

                {{-- SMS --}}
                <a :href="smsUrl"
                   class="flex flex-col items-center gap-2 group focus:outline-none"
                   aria-label="{{ __('share.sms') }}">
                    <span class="w-12 h-12 flex items-center justify-center rounded-full bg-green-600 text-white group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-comment-sms text-xl" aria-hidden="true"></i>
                    </span>
                    <span class="text-[11px] text-gray-600 dark:text-gray-400 text-center leading-tight">{{ __('share.sms') }}</span>
                </a>

                {{-- Telegram --}}
                <a :href="telegramUrl" target="_blank" rel="noopener"
                   class="flex flex-col items-center gap-2 group focus:outline-none"
                   aria-label="{{ __('share.telegram') }}">
                    <span class="w-12 h-12 flex items-center justify-center rounded-full bg-[#229ED9] text-white group-hover:scale-110 transition-transform">
                        <i class="fa-brands fa-telegram text-xl" aria-hidden="true"></i>
                    </span>
                    <span class="text-[11px] text-gray-600 dark:text-gray-400 text-center leading-tight">{{ __('share.telegram') }}</span>
                </a>

                {{-- X (Twitter) --}}
                <a :href="xUrl" target="_blank" rel="noopener"
                   class="flex flex-col items-center gap-2 group focus:outline-none"
                   aria-label="{{ __('share.x') }}">
                    <span class="w-12 h-12 flex items-center justify-center rounded-full bg-black text-white group-hover:scale-110 transition-transform">
                        <i class="fa-brands fa-x-twitter text-xl" aria-hidden="true"></i>
                    </span>
                    <span class="text-[11px] text-gray-600 dark:text-gray-400 text-center leading-tight">{{ __('share.x') }}</span>
                </a>

                {{-- Facebook --}}
                <a :href="facebookUrl" target="_blank" rel="noopener"
                   class="flex flex-col items-center gap-2 group focus:outline-none"
                   aria-label="{{ __('share.facebook') }}">
                    <span class="w-12 h-12 flex items-center justify-center rounded-full bg-[#1877F2] text-white group-hover:scale-110 transition-transform">
                        <i class="fa-brands fa-facebook text-xl" aria-hidden="true"></i>
                    </span>
                    <span class="text-[11px] text-gray-600 dark:text-gray-400 text-center leading-tight">{{ __('share.facebook') }}</span>
                </a>

                {{-- Instagram (copy link hint) --}}
                <button type="button" x-on:click="copyForInstagram()"
                        class="flex flex-col items-center gap-2 group focus:outline-none"
                        aria-label="{{ __('share.instagram') }}">
                    <span class="w-12 h-12 flex items-center justify-center rounded-full text-white group-hover:scale-110 transition-transform"
                          style="background: radial-gradient(circle at 30% 107%, #fdf497 0%, #fdf497 5%, #fd5949 45%, #d6249f 60%, #285AEB 90%);">
                        <i class="fa-brands fa-instagram text-xl" aria-hidden="true"></i>
                    </span>
                    <span class="text-[11px] text-gray-600 dark:text-gray-400 text-center leading-tight">{{ __('share.instagram') }}</span>
                </button>

                {{-- Copy Link --}}
                <button type="button" x-on:click="copyLink()"
                        class="flex flex-col items-center gap-2 group focus:outline-none"
                        aria-label="{{ __('share.copy_link') }}">
                    <span class="w-12 h-12 flex items-center justify-center rounded-full bg-gray-600 dark:bg-gray-500 text-white group-hover:scale-110 transition-transform">
                        <i class="text-xl" :class="copied ? 'fa-solid fa-check' : 'fa-solid fa-link'" aria-hidden="true"></i>
                    </span>
                    <span class="text-[11px] text-gray-600 dark:text-gray-400 text-center leading-tight"
                          x-text="copied ? @js(__('share.copied')) : @js(__('share.copy_link'))"></span>
                </button>
            </div>

            {{-- URL preview + inline copy --}}
            <div class="mt-5 flex items-center gap-2 p-2 rounded-xl bg-gray-50 dark:bg-gray-900/60 border border-gray-100 dark:border-gray-700">
                <span class="flex-1 min-w-0 truncate text-xs text-gray-500 dark:text-gray-400 px-2" x-text="url"></span>
                <button type="button" x-on:click="copyLink()"
                        class="shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-white transition hover:opacity-90"
                        :class="copied ? 'bg-green-600' : 'bg-gray-800 dark:bg-gray-600'"
                        aria-label="{{ __('share.copy_link') }}">
                    <i class="text-sm" :class="copied ? 'fa-solid fa-check' : 'fa-solid fa-copy'" aria-hidden="true"></i>
                    <span x-text="copied ? @js(__('share.copied')) : @js(__('share.copy_link'))"></span>
                </button>
            </div>

            {{-- Instagram hint --}}
            <p x-show="igHint" x-cloak x-transition
               class="mt-3 text-xs text-center text-gray-500 dark:text-gray-400">
                {{ __('share.instagram_hint') }}
            </p>
        </div>
    </div>
</div>

@once
    @push('scripts')
    <script>
        function shareModal() {
            return {
                isOpen: false,
                url: '',
                title: '',
                copied: false,
                igHint: false,
                _copyTimer: null,

                open(detail) {
                    this.url = (detail && detail.url) || window.location.href;
                    this.title = (detail && detail.title) || document.title;
                    this.copied = false;
                    this.igHint = false;
                    this.isOpen = true;
                    document.body.classList.add('overflow-y-hidden');
                },

                close() {
                    this.isOpen = false;
                    this.igHint = false;
                    document.body.classList.remove('overflow-y-hidden');
                },

                // Encoded getters for each share target
                get eUrl() { return encodeURIComponent(this.url); },
                get eTitle() { return encodeURIComponent(this.title); },
                get eText() { return encodeURIComponent(this.title + ' ' + this.url); },

                get waUrl() { return 'https://wa.me/?text=' + this.eText; },
                get smsUrl() { return 'sms:?body=' + this.eText; },
                get telegramUrl() { return 'https://t.me/share/url?url=' + this.eUrl + '&text=' + this.eTitle; },
                get xUrl() { return 'https://twitter.com/intent/tweet?url=' + this.eUrl + '&text=' + this.eTitle; },
                get facebookUrl() { return 'https://www.facebook.com/sharer/sharer.php?u=' + this.eUrl; },

                _flagCopied() {
                    this.copied = true;
                    if (this._copyTimer) clearTimeout(this._copyTimer);
                    this._copyTimer = setTimeout(() => { this.copied = false; }, 2000);
                },

                copyToClipboard() {
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        return navigator.clipboard.writeText(this.url);
                    }
                    // Fallback for non-secure contexts / older browsers
                    return new Promise((resolve, reject) => {
                        try {
                            var ta = document.createElement('textarea');
                            ta.value = this.url;
                            ta.style.position = 'fixed';
                            ta.style.opacity = '0';
                            document.body.appendChild(ta);
                            ta.focus();
                            ta.select();
                            document.execCommand('copy');
                            document.body.removeChild(ta);
                            resolve();
                        } catch (e) { reject(e); }
                    });
                },

                copyLink() {
                    this.copyToClipboard().then(() => this._flagCopied()).catch(() => {});
                },

                copyForInstagram() {
                    this.copyToClipboard().then(() => {
                        this._flagCopied();
                        this.igHint = true;
                    }).catch(() => {});
                },
            };
        }
    </script>
    @endpush
@endonce
