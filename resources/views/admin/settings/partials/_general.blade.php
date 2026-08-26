{{-- General Settings Partial --}}
@php
    $logoPreview = !empty($settings['site_logo']) ? asset('storage/' . $settings['site_logo']) : '';
    $faviconPreview = !empty($settings['site_favicon']) ? asset('storage/' . $settings['site_favicon']) : '';
@endphp

<h3 class="text-lg font-semibold text-gray-800 mb-1">General Settings</h3>
<p class="text-sm text-gray-500 mb-6">
    Kolom bertanda <span class="text-red-500 font-bold">*</span> wajib diisi, sisanya opsional.
</p>

<div class="space-y-6">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Site Name --}}
        <div>
            <label for="site_name" class="block text-sm font-medium text-gray-700 mb-2">
                Site Name <span class="text-red-500">*</span>
            </label>
            <input type="text"
                   name="site_name"
                   id="site_name"
                   value="{{ old('site_name', $settings['site_name'] ?? '') }}"
                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                   required>
            @error('site_name')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Site Description (used as the site Tagline) --}}
        <div>
            <label for="site_description" class="block text-sm font-medium text-gray-700 mb-2">
                Site Description (Tagline)
            </label>
            <input type="text"
                   name="site_description"
                   id="site_description"
                   value="{{ old('site_description', $settings['site_description'] ?? '') }}"
                   placeholder="e.g. Quality Living in Premium Location"
                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
            <p class="text-xs text-gray-500 mt-1">
                Used as the site tagline in the homepage title: <span class="font-mono">Site Name - Tagline</span>.
            </p>
            @error('site_description')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>

    {{-- Logo & Favicon with Drag & Drop --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6"
         x-data="fileUploader({
             logoPreview: '{{ $logoPreview }}',
             faviconPreview: '{{ $faviconPreview }}'
         })">
        {{-- Logo Upload --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Site Logo</label>
            <div class="relative"
                 @dragover.prevent="logo.dragging = true"
                 @dragleave.prevent="logo.dragging = false"
                 @drop.prevent="handleDrop($event, 'logo')">
                <div class="border-2 border-dashed rounded-lg p-6 text-center cursor-pointer transition-colors"
                     :class="logo.dragging ? 'border-blue-500 bg-blue-50' : 'border-gray-300 hover:border-gray-400'"
                     @click="$refs.logoInput.click()">
                    <input type="file"
                           name="site_logo"
                           x-ref="logoInput"
                           accept="image/*"
                           class="hidden"
                           @change="handleFileSelect($event, 'logo')">

                    <template x-if="!logo.preview">
                        <div>
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <p class="mt-2 text-sm text-gray-600">
                                <span class="text-blue-600 hover:text-blue-700">Click to upload</span> or drag and drop
                            </p>
                            <p class="text-xs text-gray-500 mt-1">PNG, JPG, WEBP, SVG (max 2MB)</p>
                        </div>
                    </template>

                    <template x-if="logo.preview">
                        <div class="relative">
                            <img :src="logo.preview" alt="Logo preview" class="mx-auto max-h-24 rounded">
                            <button type="button"
                                    @click.stop="clearFile('logo')"
                                    class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>
            </div>
            @error('site_logo')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Favicon Upload --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Favicon</label>
            <div class="relative"
                 @dragover.prevent="favicon.dragging = true"
                 @dragleave.prevent="favicon.dragging = false"
                 @drop.prevent="handleDrop($event, 'favicon')">
                <div class="border-2 border-dashed rounded-lg p-6 text-center cursor-pointer transition-colors"
                     :class="favicon.dragging ? 'border-blue-500 bg-blue-50' : 'border-gray-300 hover:border-gray-400'"
                     @click="$refs.faviconInput.click()">
                    <input type="file"
                           name="site_favicon"
                           x-ref="faviconInput"
                           accept="image/*,.ico"
                           class="hidden"
                           @change="handleFileSelect($event, 'favicon')">

                    <template x-if="!favicon.preview">
                        <div>
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <p class="mt-2 text-sm text-gray-600">
                                <span class="text-blue-600 hover:text-blue-700">Click to upload</span> or drag and drop
                            </p>
                            <p class="text-xs text-gray-500 mt-1">PNG, JPG, ICO, SVG (max 1MB)</p>
                        </div>
                    </template>

                    <template x-if="favicon.preview">
                        <div class="relative">
                            <img :src="favicon.preview" alt="Favicon preview" class="mx-auto max-h-24 rounded">
                            <button type="button"
                                    @click.stop="clearFile('favicon')"
                                    class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>
            </div>
            @error('site_favicon')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Contact Email --}}
        <div>
            <label for="contact_email" class="block text-sm font-medium text-gray-700 mb-2">
                Contact Email
            </label>
            <input type="email"
                   name="contact_email"
                   id="contact_email"
                   value="{{ old('contact_email', $settings['contact_email'] ?? '') }}"
                   placeholder="admin@example.com"
                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
            @error('contact_email')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Contact Phone --}}
        <div>
            <label for="contact_phone" class="block text-sm font-medium text-gray-700 mb-2">
                Contact Phone
            </label>
            <input type="text"
                   name="contact_phone"
                   id="contact_phone"
                   value="{{ old('contact_phone', $settings['contact_phone'] ?? '') }}"
                   placeholder="+62 812 3456 7890"
                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
            @error('contact_phone')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>

    {{-- Contact Address --}}
    <div>
        <label for="contact_address" class="block text-sm font-medium text-gray-700 mb-2">
            Contact Address
        </label>
        <textarea name="contact_address"
                  id="contact_address"
                  rows="3"
                  class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">{{ old('contact_address', $settings['contact_address'] ?? '') }}</textarea>
        @error('contact_address')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- WhatsApp Default --}}
        <div>
            <label for="whatsapp_default" class="block text-sm font-medium text-gray-700 mb-2">
                WhatsApp Default Number
            </label>
            <input type="text"
                   name="whatsapp_default"
                   id="whatsapp_default"
                   value="{{ old('whatsapp_default', $settings['whatsapp_default'] ?? '') }}"
                   placeholder="6281234567890"
                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
            @error('whatsapp_default')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Timezone --}}
        <div>
            <label for="timezone" class="block text-sm font-medium text-gray-700 mb-2">
                Timezone
            </label>
            <select name="timezone"
                    id="timezone"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                @php
                    $timezones = ['UTC', 'Asia/Jakarta', 'Asia/Singapore', 'Asia/Kuala_Lumpur', 'Australia/Sydney', 'Europe/London', 'America/New_York'];
                @endphp
                @foreach($timezones as $tz)
                    <option value="{{ $tz }}" {{ ($settings['timezone'] ?? 'UTC') == $tz ? 'selected' : '' }}>{{ $tz }}</option>
                @endforeach
            </select>
            @error('timezone')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Locale --}}
        <div>
            <label for="locale" class="block text-sm font-medium text-gray-700 mb-2">
                Locale
            </label>
            <select name="locale"
                    id="locale"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                <option value="en" {{ ($settings['locale'] ?? 'en') == 'en' ? 'selected' : '' }}>English</option>
                <option value="id" {{ ($settings['locale'] ?? '') == 'id' ? 'selected' : '' }}>Indonesian</option>
            </select>
            @error('locale')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>

    {{-- Currency --}}
    <div class="w-full lg:w-1/3">
        <label for="currency" class="block text-sm font-medium text-gray-700 mb-2">
            Currency
        </label>
        <select name="currency"
                id="currency"
                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
            <option value="USD" {{ ($settings['currency'] ?? 'USD') == 'USD' ? 'selected' : '' }}>USD ($)</option>
            <option value="IDR" {{ ($settings['currency'] ?? '') == 'IDR' ? 'selected' : '' }}>IDR (Rp)</option>
            <option value="EUR" {{ ($settings['currency'] ?? '') == 'EUR' ? 'selected' : '' }}>EUR (€)</option>
        </select>
        @error('currency')
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

@push('scripts')
<script>
function fileUploader(config) {
    return {
        logo: {
            preview: config.logoPreview || null,
            dragging: false
        },
        favicon: {
            preview: config.faviconPreview || null,
            dragging: false
        },

        handleFileSelect(event, type) {
            const file = event.target.files[0];
            if (file) {
                this.setFile(file, type);
            }
        },

        handleDrop(event, type) {
            event.preventDefault();
            this[type].dragging = false;

            const file = event.dataTransfer.files[0];
            if (file && file.type.startsWith('image/')) {
                this.setFile(file, type);
            }
        },

        setFile(file, type) {
            const reader = new FileReader();
            reader.onload = (e) => {
                this[type].preview = e.target.result;
            };
            reader.readAsDataURL(file);

            // Assign the file to the hidden input so it gets submitted
            const input = this.$refs[type + 'Input'];
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            input.files = dataTransfer.files;
        },

        clearFile(type) {
            this[type].preview = null;
            this.$refs[type + 'Input'].value = '';
        }
    };
}
</script>
@endpush
