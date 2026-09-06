@props([
    'id',
    'title',
    'message' => null,
    'confirmLabel' => 'Hapus',
    'cancelLabel' => 'Batal',
    'confirmFormId',
])

<div x-data="{
        open: false,
        previousFocus: null,
        close() {
            this.open = false;
            this.$nextTick(() => {
                if (this.previousFocus && document.contains(this.previousFocus)) {
                    this.previousFocus.focus();
                }
            });
        },
        confirm() {
            const form = document.getElementById(@js($confirmFormId));
            this.close();
            if (form) {
                form.requestSubmit();
            }
        }
     }"
     x-on:open-confirm.window="if ($event.detail.id === @js($id)) { previousFocus = $event.detail.trigger || document.activeElement; open = true; $nextTick(() => $refs.cancelBtn.focus()); }"
     x-on:keydown.escape.window="if (open) close()"
     x-show="open"
     x-cloak
     class="fixed inset-0 z-[9999] flex items-center justify-center"
     style="display: none;"
     aria-hidden="true"
     x-bind:aria-hidden="(!open).toString()">
    <div class="absolute inset-0 bg-gray-900/50" x-on:click="close()" aria-hidden="true"></div>

    <div class="relative mx-4 w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800"
         role="alertdialog"
         aria-modal="true"
         aria-labelledby="{{ $id }}-title"
         @if($message) aria-describedby="{{ $id }}-desc" @endif
         tabindex="-1">
        <h2 id="{{ $id }}-title" class="mb-2 text-lg font-semibold text-gray-900 dark:text-white">{{ $title }}</h2>
        @if($message)
            <p id="{{ $id }}-desc" class="mb-6 text-sm text-gray-600 dark:text-gray-400">{{ $message }}</p>
        @endif
        <div class="flex items-center justify-end gap-3">
            <button type="button"
                    x-ref="cancelBtn"
                    x-on:click="close()"
                    class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-400 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                {{ $cancelLabel }}
            </button>
            <button type="button"
                    x-on:click="confirm()"
                    class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                {{ $confirmLabel }}
            </button>
        </div>
    </div>
</div>
