{{-- Mail / Email Settings Partial --}}
<h3 class="text-lg font-semibold text-gray-800 mb-1">Mail / Email Settings</h3>
<p class="text-sm text-gray-500 mb-6">Configure SMTP server and email sending.</p>

<div class="space-y-6">
    {{-- Mail Mailer --}}
    <div>
        <label for="mail_mailer" class="block text-sm font-medium text-gray-700 mb-1">
            Mail Driver
        </label>
        <select name="mail_mailer"
                id="mail_mailer"
                x-data
                @change="$dispatch('mail-mailer-changed', { value: $event.target.value })"
                class="w-full max-w-xs px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm">
            <option value="smtp" {{ ($settings['mail_mailer'] ?? 'smtp') === 'smtp' ? 'selected' : '' }}>SMTP</option>
            <option value="sendmail" {{ ($settings['mail_mailer'] ?? '') === 'sendmail' ? 'selected' : '' }}>Sendmail</option>
            <option value="log" {{ ($settings['mail_mailer'] ?? '') === 'log' ? 'selected' : '' }}>Log (Development)</option>
            <option value="array" {{ ($settings['mail_mailer'] ?? '') === 'array' ? 'selected' : '' }}>Array (Testing)</option>
        </select>
        <p class="text-xs text-gray-400 mt-1">Pilih driver pengiriman email. Gunakan 'log' untuk development.</p>
        @error('mail_mailer')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- SMTP Settings (shown when mailer = smtp) --}}
    <div x-data="{ showSmtp: '{{ old('mail_mailer', $settings['mail_mailer'] ?? 'smtp') }}' === 'smtp' }"
         @mail-mailer-changed.window="showSmtp = $event.detail.value === 'smtp'"
         x-show="showSmtp"
         x-cloak
         class="space-y-6 p-4 bg-blue-50 border border-blue-100 rounded-lg">
        <h4 class="text-md font-semibold text-gray-700 mb-4">SMTP Configuration</h4>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="mail_host" class="block text-sm font-medium text-gray-700 mb-2">
                    SMTP Host <span class="text-red-500">*</span>
                </label>
                <input type="text"
                       name="mail_host"
                       id="mail_host"
                       value="{{ old('mail_host', $settings['mail_host'] ?? '') }}"
                       placeholder="smtp.gmail.com"
                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                @error('mail_host')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="mail_port" class="block text-sm font-medium text-gray-700 mb-2">
                    SMTP Port <span class="text-red-500">*</span>
                </label>
                <input type="number"
                       name="mail_port"
                       id="mail_port"
                       value="{{ old('mail_port', $settings['mail_port'] ?? '587') }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                @error('mail_port')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="mail_username" class="block text-sm font-medium text-gray-700 mb-2">
                    SMTP Username <span class="text-red-500">*</span>
                </label>
                <input type="text"
                       name="mail_username"
                       id="mail_username"
                       value="{{ old('mail_username', $settings['mail_username'] ?? '') }}"
                       placeholder="user@example.com"
                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                @error('mail_username')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="mail_password" class="block text-sm font-medium text-gray-700 mb-2">
                    SMTP Password <span class="text-red-500">*</span>
                </label>
                <input type="password"
                       name="mail_password"
                       id="mail_password"
                       value="{{ old('mail_password', $settings['mail_password'] ?? '') }}"
                       placeholder="Enter password"
                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                @error('mail_password')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="mail_encryption" class="block text-sm font-medium text-gray-700 mb-2">
                    Encryption <span class="text-red-500">*</span>
                </label>
                <select name="mail_encryption"
                        id="mail_encryption"
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    <option value="tls" {{ ($settings['mail_encryption'] ?? 'tls') == 'tls' ? 'selected' : '' }}>TLS</option>
                    <option value="ssl" {{ ($settings['mail_encryption'] ?? '') == 'ssl' ? 'selected' : '' }}>SSL</option>
                </select>
                @error('mail_encryption')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="mail_from_address" class="block text-sm font-medium text-gray-700 mb-2">
                    From Address <span class="text-red-500">*</span>
                </label>
                <input type="email"
                       name="mail_from_address"
                       id="mail_from_address"
                       value="{{ old('mail_from_address', $settings['mail_from_address'] ?? '') }}"
                       placeholder="noreply@example.com"
                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                @error('mail_from_address')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="mail_from_name" class="block text-sm font-medium text-gray-700 mb-2">
                    From Name
                </label>
                <input type="text"
                       name="mail_from_name"
                       id="mail_from_name"
                       value="{{ old('mail_from_name', $settings['mail_from_name'] ?? '') }}"
                       placeholder="Your Site Name"
                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                @error('mail_from_name')
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
