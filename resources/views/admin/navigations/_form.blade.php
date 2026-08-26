<div class="space-y-6">
    <!-- Title -->
    <div>
        <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
            {{ __('navigation.title') }} <span class="text-red-500">*</span>
        </label>
        <input type="text"
               name="title"
               id="title"
               value="{{ old('title', $navigation->title ?? '') }}"
               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
               required>
        @error('title')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- Type -->
    <div>
        <label for="type" class="block text-sm font-medium text-gray-700 mb-2">
            {{ __('navigation.link_type') }} <span class="text-red-500">*</span>
        </label>
        <select name="type"
                id="type"
                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                required>
            <option value="url" {{ old('type', $navigation->type ?? 'url') == 'url' ? 'selected' : '' }}>{{ __('navigation.type_url') }}</option>
            <option value="page" {{ old('type', $navigation->type ?? '') == 'page' ? 'selected' : '' }}>{{ __('navigation.type_page') }}</option>
            <option value="custom" {{ old('type', $navigation->type ?? '') == 'custom' ? 'selected' : '' }}>{{ __('navigation.type_custom') }}</option>
        </select>
        @error('type')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- URL (for url and custom types) -->
    <div id="url-field">
        <label for="url" class="block text-sm font-medium text-gray-700 mb-2">
            URL <span class="text-red-500">*</span>
        </label>
        <input type="text"
               name="url"
               id="url"
               value="{{ old('url', $navigation->url ?? '') }}"
               placeholder="/about or https://example.com"
               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
        @error('url')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- Page Selection (for page type) -->
    <div id="page-field" style="display: none;">
        <label for="page_id" class="block text-sm font-medium text-gray-700 mb-2">
            {{ __('navigation.select_page') }} <span class="text-red-500">*</span>
        </label>
        <select name="page_id"
                id="page_id"
                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
            <option value="">-- {{ __('navigation.select_page') }} --</option>
            @foreach($pages ?? [] as $page)
                <option value="{{ $page->id }}" {{ old('page_id', $navigation->page_id ?? '') == $page->id ? 'selected' : '' }}>
                    {{ $page->title }}
                </option>
            @endforeach
        </select>
        @error('page_id')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Menu Location -->
        <div>
            <label for="menu_location" class="block text-sm font-medium text-gray-700 mb-2">
                {{ __('navigation.menu_location') }} <span class="text-red-500">*</span>
            </label>
            <select name="menu_location"
                    id="menu_location"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                    required>
                <option value="main" {{ old('menu_location', $navigation->menu_location ?? 'main') == 'main' ? 'selected' : '' }}>{{ __('navigation.location_main') }}</option>
                <option value="footer" {{ old('menu_location', $navigation->menu_location ?? '') == 'footer' ? 'selected' : '' }}>{{ __('navigation.location_footer') }}</option>
                <option value="sidebar" {{ old('menu_location', $navigation->menu_location ?? '') == 'sidebar' ? 'selected' : '' }}>{{ __('navigation.location_sidebar') }}</option>
            </select>
            @error('menu_location')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Order -->
        <div>
            <label for="order" class="block text-sm font-medium text-gray-700 mb-2">
                {{ __('navigation.order') }}
            </label>
            <input type="number"
                   name="order"
                   id="order"
                   value="{{ old('order', $navigation->order ?? 0) }}"
                   min="0"
                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
            <p class="text-xs text-gray-500 mt-1">{{ __('navigation.order_hint') }}</p>
            @error('order')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <!-- Icon -->
    <div>
        <label for="icon" class="block text-sm font-medium text-gray-700 mb-2">
            {{ __('navigation.icon') }}
        </label>
        <input type="text"
               name="icon"
               id="icon"
               value="{{ old('icon', $navigation->icon ?? '') }}"
               placeholder="fa-solid fa-home"
               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
        <p class="text-xs text-gray-500 mt-1">{{ __('navigation.icon_hint') }}</p>
        @error('icon')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- Parent Item -->
    <div>
        <label for="parent_id" class="block text-sm font-medium text-gray-700 mb-2">
            {{ __('navigation.parent_item') }}
        </label>
        <select name="parent_id"
                id="parent_id"
                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
            <option value="">None (Top Level)</option>
            @foreach($navigations ?? [] as $nav)
                @if(!isset($navigation) || $nav->id != $navigation->id)
                    <option value="{{ $nav->id }}" {{ old('parent_id', $navigation->parent_id ?? '') == $nav->id ? 'selected' : '' }}>
                        {{ $nav->title }} ({{ ucfirst($nav->menu_location ?? $nav->location) }})
                    </option>
                @endif
            @endforeach
        </select>
        <p class="text-xs text-gray-500 mt-1">{{ __('navigation.parent_hint') }}</p>
        @error('parent_id')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- Target -->
    <div>
        <label for="target" class="block text-sm font-medium text-gray-700 mb-2">
            {{ __('navigation.open_link_in') }}
        </label>
        <select name="target"
                id="target"
                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
            <option value="_self" {{ old('target', $navigation->target ?? '_self') == '_self' ? 'selected' : '' }}>{{ __('navigation.same_window') }}</option>
            <option value="_blank" {{ old('target', $navigation->target ?? '') == '_blank' ? 'selected' : '' }}>{{ __('navigation.new_window') }}</option>
        </select>
        @error('target')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- CSS Class -->
    <div>
        <label for="css_class" class="block text-sm font-medium text-gray-700 mb-2">
            {{ __('navigation.css_class') }}
        </label>
        <input type="text"
               name="css_class"
               id="css_class"
               value="{{ old('css_class', $navigation->css_class ?? '') }}"
               placeholder="custom-class another-class"
               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
        <p class="text-xs text-gray-500 mt-1">{{ __('navigation.css_class_hint') }}</p>
        @error('css_class')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- Status -->
    <div>
        <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
            {{ __('navigation.status') }} <span class="text-red-500">*</span>
        </label>
        <select name="status"
                id="status"
                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                required>
            <option value="active" {{ old('status', $navigation->status ?? 'active') == 'active' ? 'selected' : '' }}>{{ __('navigation.active') }}</option>
            <option value="inactive" {{ old('status', $navigation->status ?? '') == 'inactive' ? 'selected' : '' }}>{{ __('navigation.inactive') }}</option>
        </select>
        @error('status')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>
</div>

@push('scripts')
<script>
    // Toggle between URL and Page fields based on type
    document.getElementById('type').addEventListener('change', function() {
        const urlField = document.getElementById('url-field');
        const pageField = document.getElementById('page-field');

        if (this.value === 'page') {
            urlField.style.display = 'none';
            pageField.style.display = 'block';
            document.getElementById('url').required = false;
            document.getElementById('page_id').required = true;
        } else {
            urlField.style.display = 'block';
            pageField.style.display = 'none';
            document.getElementById('url').required = true;
            document.getElementById('page_id').required = false;
        }
    });

    // Trigger on page load to set correct state
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('type').dispatchEvent(new Event('change'));
    });
</script>
@endpush
