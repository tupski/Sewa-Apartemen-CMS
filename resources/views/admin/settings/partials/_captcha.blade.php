{{-- CAPTCHA / Security Settings Partial --}}
<h3 class="text-lg font-semibold text-gray-800 mb-1">Security (CAPTCHA)</h3>
<p class="text-sm text-gray-500 mb-6">Configure CAPTCHA and bot protection settings.</p>

<div class="space-y-6">
    {{-- CAPTCHA Provider --}}
    <div>
        <label for="captcha_provider" class="block text-sm font-medium text-gray-700 mb-2">
            CAPTCHA Provider
        </label>
        <select name="captcha_provider"
                id="captcha_provider"
                x-data
                @change="$dispatch('captcha-provider-changed', { value: $event.target.value })"
                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
            <option value="none" {{ ($settings['captcha_provider'] ?? 'none') === 'none' ? 'selected' : '' }}>None (Disabled)</option>
            <option value="recaptcha_v2" {{ ($settings['captcha_provider'] ?? '') === 'recaptcha_v2' ? 'selected' : '' }}>reCAPTCHA v2 (Checkbox)</option>
            <option value="recaptcha_v3" {{ ($settings['captcha_provider'] ?? '') === 'recaptcha_v3' ? 'selected' : '' }}>reCAPTCHA v3 (Invisible)</option>
            <option value="hcaptcha" {{ ($settings['captcha_provider'] ?? '') === 'hcaptcha' ? 'selected' : '' }}>hCaptcha</option>
            <option value="turnstile" {{ ($settings['captcha_provider'] ?? '') === 'turnstile' ? 'selected' : '' }}>Cloudflare Turnstile</option>
        </select>
        @error('captcha_provider')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- reCAPTCHA v3 Min Score (shown when provider = recaptcha_v3) --}}
    <div x-data="{ show: '{{ old('captcha_provider', $settings['captcha_provider'] ?? 'none') }}' === 'recaptcha_v3' }"
         @captcha-provider-changed.window="show = $event.detail.value === 'recaptcha_v3'"
         x-show="show"
         x-cloak>
        <label for="captcha_recaptcha_min_score" class="block text-sm font-medium text-gray-700 mb-2">
            reCAPTCHA v3 Minimum Score
        </label>
        <input type="number"
               name="captcha_recaptcha_min_score"
               id="captcha_recaptcha_min_score"
               value="{{ old('captcha_recaptcha_min_score', $settings['captcha_recaptcha_min_score'] ?? '0.5') }}"
               min="0" max="1" step="0.1"
               class="w-full max-w-xs px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
        <p class="text-xs text-gray-500 mt-1">Nilai antara 0.0 (sangat permisif) hingga 1.0 (sangat ketat). Default: 0.5.</p>
        @error('captcha_recaptcha_min_score')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Site Key --}}
    <div>
        <label for="captcha_site_key" class="block text-sm font-medium text-gray-700 mb-2">
            Site Key
        </label>
        <input type="text"
               name="captcha_site_key"
               id="captcha_site_key"
               value="{{ old('captcha_site_key', $settings['captcha_site_key'] ?? '') }}"
               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 font-mono text-sm">
        @error('captcha_site_key')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Secret Key --}}
    <div>
        <label for="captcha_secret_key" class="block text-sm font-medium text-gray-700 mb-2">
            Secret Key
        </label>
        <input type="text"
               name="captcha_secret_key"
               id="captcha_secret_key"
               value="{{ old('captcha_secret_key', $settings['captcha_secret_key'] ?? '') }}"
               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 font-mono text-sm">
        @error('captcha_secret_key')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>
</div>

{{-- Submit Button --}}
<div class="pt-6 border-t border-gray-200 mt-6 flex justify-end">
    <button type="submit"
            class="px-6 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
        Save Settings
    </button>
</div>
