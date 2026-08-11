<div class="space-y-6">
    <!-- From URL -->
    <div>
        <label for="from_url" class="block text-sm font-medium text-gray-700 mb-2">
            From URL <span class="text-red-500">*</span>
        </label>
        <input type="text"
               name="from_url"
               id="from_url"
               value="{{ old('from_url', $redirect->from_url ?? '') }}"
               placeholder="/old-page"
               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
               required>
        <p class="text-xs text-gray-500 mt-1">The URL path to redirect from (e.g., /old-page)</p>
        @error('from_url')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- To URL -->
    <div>
        <label for="to_url" class="block text-sm font-medium text-gray-700 mb-2">
            To URL <span class="text-red-500">*</span>
        </label>
        <input type="text"
               name="to_url"
               id="to_url"
               value="{{ old('to_url', $redirect->to_url ?? '') }}"
               placeholder="/new-page"
               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
               required>
        <p class="text-xs text-gray-500 mt-1">The target URL to redirect to (e.g., /new-page)</p>
        @error('to_url')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- Status Code -->
    <div>
        <label for="status_code" class="block text-sm font-medium text-gray-700 mb-2">
            Status Code <span class="text-red-500">*</span>
        </label>
        <select name="status_code"
                id="status_code"
                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                required>
            <option value="301" {{ old('status_code', $redirect->status_code ?? 301) == 301 ? 'selected' : '' }}>301 - Permanent Redirect</option>
            <option value="302" {{ old('status_code', $redirect->status_code ?? 301) == 302 ? 'selected' : '' }}>302 - Temporary Redirect</option>
        </select>
        @error('status_code')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>
</div>
