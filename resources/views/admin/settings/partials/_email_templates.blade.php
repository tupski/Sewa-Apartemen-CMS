{{-- Email Templates Settings Partial --}}
<h3 class="text-lg font-semibold text-gray-800 mb-1">Email Templates</h3>
<p class="text-sm text-gray-500 mb-6">Kustomisasi template email untuk notifikasi sistem.</p>

<div class="space-y-6">
    @foreach([
        'booking_confirmation' => 'Booking Confirmation',
        'booking_cancellation' => 'Booking Cancellation',
        'password_reset' => 'Password Reset',
        'welcome' => 'Welcome Email',
    ] as $templateKey => $templateLabel)
        <div class="border border-gray-200 rounded-lg p-4">
            <div class="flex items-center justify-between mb-4">
                <h4 class="font-medium text-gray-800">{{ $templateLabel }}</h4>
                <span class="text-xs text-gray-500">Key: {{ $templateKey }}</span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="email_{{ $templateKey }}_subject" class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                    <input type="text"
                           name="email_{{ $templateKey }}_subject"
                           id="email_{{ $templateKey }}_subject"
                           value="{{ old('email_' . $templateKey . '_subject', $settings['email_' . $templateKey . '_subject'] ?? '') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm">
                    @error('email_' . $templateKey . '_subject')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="email_{{ $templateKey }}_body" class="block text-sm font-medium text-gray-700 mb-1">Body (HTML)</label>
                    <textarea name="email_{{ $templateKey }}_body"
                              id="email_{{ $templateKey }}_body"
                              rows="4"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm font-mono">{{ old('email_' . $templateKey . '_body', $settings['email_' . $templateKey . '_body'] ?? '') }}</textarea>
                    @error('email_' . $templateKey . '_body')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- Submit Button --}}
<div class="pt-6 border-t border-gray-200 mt-6 flex justify-end">
    <button type="submit"
            class="px-6 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
        Save Settings
    </button>
</div>
