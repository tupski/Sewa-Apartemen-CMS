{{-- Integrations Settings Partial --}}
<h3 class="text-lg font-semibold text-gray-800 mb-1">Integrations</h3>
<p class="text-sm text-gray-500 mb-6">Configure external integrations and webhooks.</p>

<div class="space-y-6">
    {{-- Owner Notification Webhook --}}
    <div class="border border-gray-200 rounded-lg p-4">
        <h4 class="text-md font-semibold text-gray-700 mb-4">Owner Notification Webhook</h4>
        <p class="text-sm text-gray-500 mb-4">
            Kirim event booking (created / confirmed / cancelled / completed) ke URL eksternal.
            Kosongkan untuk nonaktif. Cocok untuk Fonnte, Wablas, OneSender, n8n, Make, atau
            endpoint JSON apa pun. Setiap event juga tercatat di log aplikasi.
        </p>

        <div class="space-y-4">
            <div>
                <label for="notification_webhook" class="block text-sm font-medium text-gray-700 mb-2">
                    Webhook URL
                </label>
                <input type="url"
                       name="notification_webhook"
                       id="notification_webhook"
                       value="{{ old('notification_webhook', $settings['notification_webhook'] ?? '') }}"
                       placeholder="https://api.fonnte.com/send"
                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 font-mono text-sm">
                <p class="text-xs text-gray-500 mt-1">POST endpoint yang menerima JSON. Akan dipanggil dengan timeout 5 detik (1 retry pada 5xx).</p>
                @error('notification_webhook')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="notification_webhook_secret" class="block text-sm font-medium text-gray-700 mb-2">
                    Webhook Secret (opsional)
                </label>
                <input type="text"
                       name="notification_webhook_secret"
                       id="notification_webhook_secret"
                       value="{{ old('notification_webhook_secret', $settings['notification_webhook_secret'] ?? '') }}"
                       placeholder="shared-secret-string"
                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 font-mono text-sm">
                <p class="text-xs text-gray-500 mt-1">Jika diisi, payload akan dikirim bersama header <code>X-Webhook-Signature: sha256=...</code> (HMAC-SHA256). Receiver bisa verifikasi integritas.</p>
                @error('notification_webhook_secret')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>
</div>

{{-- Submit Button --}}
<div class="pt-6 border-t border-gray-200 mt-6 flex justify-end">
    <button type="submit"
            class="px-6 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
        Save Settings
    </button>
</div>
