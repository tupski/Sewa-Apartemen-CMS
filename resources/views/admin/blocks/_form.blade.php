<div class="space-y-6">
    <!-- Name -->
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
            Name <span class="text-red-500">*</span>
        </label>
        <input type="text"
               name="name"
               id="name"
               value="{{ old('name', $block->name ?? '') }}"
               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
               required>
        @error('name')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Type -->
        <div>
            <label for="type" class="block text-sm font-medium text-gray-700 mb-2">
                Type <span class="text-red-500">*</span>
            </label>
            <select name="type"
                    id="type"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                    required>
                <option value="text" {{ old('type', $block->type ?? 'text') == 'text' ? 'selected' : '' }}>Text</option>
                <option value="html" {{ old('type', $block->type ?? '') == 'html' ? 'selected' : '' }}>HTML</option>
                <option value="menu" {{ old('type', $block->type ?? '') == 'menu' ? 'selected' : '' }}>Menu</option>
                <option value="widget" {{ old('type', $block->type ?? '') == 'widget' ? 'selected' : '' }}>Widget</option>
                <option value="custom" {{ old('type', $block->type ?? '') == 'custom' ? 'selected' : '' }}>Custom</option>
            </select>
            @error('type')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Identifier -->
        <div>
            <label for="identifier" class="block text-sm font-medium text-gray-700 mb-2">
                Identifier <span class="text-red-500">*</span>
            </label>
            <input type="text"
                   name="identifier"
                   id="identifier"
                   value="{{ old('identifier', $block->identifier ?? '') }}"
                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                   required>
            <p class="text-xs text-gray-500 mt-1">Unique identifier for this block</p>
            @error('identifier')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Area -->
        <div>
            <label for="area" class="block text-sm font-medium text-gray-700 mb-2">
                Area <span class="text-red-500">*</span>
            </label>
            <select name="area"
                    id="area"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                    required>
                <option value="header" {{ old('area', $block->area ?? 'header') == 'header' ? 'selected' : '' }}>Header</option>
                <option value="footer" {{ old('area', $block->area ?? '') == 'footer' ? 'selected' : '' }}>Footer</option>
                <option value="sidebar" {{ old('area', $block->area ?? '') == 'sidebar' ? 'selected' : '' }}>Sidebar</option>
                <option value="content" {{ old('area', $block->area ?? '') == 'content' ? 'selected' : '' }}>Content</option>
            </select>
            @error('area')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Order -->
        <div>
            <label for="order" class="block text-sm font-medium text-gray-700 mb-2">
                Order
            </label>
            <input type="number"
                   name="order"
                   id="order"
                   value="{{ old('order', $block->order ?? 0) }}"
                   min="0"
                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
            <p class="text-xs text-gray-500 mt-1">Display order within the area (0 = first)</p>
            @error('order')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <!-- Content -->
    <div>
        <label for="content" class="block text-sm font-medium text-gray-700 mb-2">
            Content
        </label>
        <textarea name="content"
                  id="content"
                  rows="10"
                  class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 font-mono text-sm">{{ old('content', $block->content ?? '') }}</textarea>
        <p class="text-xs text-gray-500 mt-1">HTML, JSON, or plain text depending on block type</p>
        @error('content')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- Status -->
    <div>
        <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
            Status <span class="text-red-500">*</span>
        </label>
        <select name="status"
                id="status"
                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                required>
            <option value="active" {{ old('status', $block->status ?? 'active') == 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ old('status', $block->status ?? '') == 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
        @error('status')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- Settings (JSON) -->
    <div>
        <label for="settings" class="block text-sm font-medium text-gray-700 mb-2">
            Settings (JSON)
        </label>
        <textarea name="settings"
                  id="settings"
                  rows="6"
                  class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 font-mono text-sm">{{ old('settings', isset($block->settings) ? json_encode($block->settings, JSON_PRETTY_PRINT) : '{}') }}</textarea>
        <p class="text-xs text-gray-500 mt-1">Additional configuration in JSON format</p>
        @error('settings')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- Display on Pages -->
    <div class="border-t border-gray-200 pt-6">
        <h3 class="text-md font-semibold text-gray-700 mb-4">Display Options</h3>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Display on Pages
            </label>
            <div class="space-y-2 max-h-48 overflow-y-auto border border-gray-300 rounded-md p-4">
                <div class="flex items-center">
                    <input type="checkbox"
                           name="display_pages[]"
                           value="all"
                           id="page_all"
                           {{ old('display_all', isset($block) && empty($block->display_pages) ? 'checked' : '') }}
                           class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <label for="page_all" class="ml-2 text-sm text-gray-700">
                        All Pages
                    </label>
                </div>

                @foreach($pages ?? [] as $page)
                    <div class="flex items-center">
                        <input type="checkbox"
                               name="display_pages[]"
                               value="{{ $page->id }}"
                               id="page_{{ $page->id }}"
                               {{ old('display_pages') && in_array($page->id, old('display_pages')) ? 'checked' : '' }}
                               {{ isset($block) && is_array($block->display_pages) && in_array($page->id, $block->display_pages) ? 'checked' : '' }}
                               class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <label for="page_{{ $page->id }}" class="ml-2 text-sm text-gray-700">
                            {{ $page->title }}
                        </label>
                    </div>
                @endforeach
            </div>
            <p class="text-xs text-gray-500 mt-1">Select which pages should display this block</p>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Auto-generate identifier from name
    document.getElementById('name').addEventListener('input', function() {
        const name = this.value;
        const identifier = name
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '_')
            .replace(/^_+|_+$/g, '');

        // Only auto-fill if identifier is empty or was auto-generated
        const identifierField = document.getElementById('identifier');
        if (!identifierField.value || identifierField.dataset.autogenerated === 'true') {
            identifierField.value = identifier;
            identifierField.dataset.autogenerated = 'true';
        }
    });

    // Mark identifier as manually edited if user changes it
    document.getElementById('identifier').addEventListener('input', function() {
        if (this.value !== this.dataset.lastAutoValue) {
            this.dataset.autogenerated = 'false';
        }
    });
</script>
@endpush

