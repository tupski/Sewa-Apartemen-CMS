<div class="space-y-6">
    <!-- Title -->
    <div>
        <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
            Title <span class="text-red-500">*</span>
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
            Link Type <span class="text-red-500">*</span>
        </label>
        <select name="type"
                id="type"
                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                required>
            <option value="internal" {{ old('type', $navigation->type ?? 'internal') == 'internal' ? 'selected' : '' }}>Internal Link</option>
            <option value="external" {{ old('type', $navigation->type ?? '') == 'external' ? 'selected' : '' }}>External Link</option>
            <option value="page" {{ old('type', $navigation->type ?? '') == 'page' ? 'selected' : '' }}>Link to Page</option>
        </select>
        @error('type')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- URL (for internal and external) -->
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
            Select Page <span class="text-red-500">*</span>
        </label>
        <select name="page_id"
                id="page_id"
                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
            <option value="">-- Select a Page --</option>
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
        <!-- Location -->
        <div>
            <label for="location" class="block text-sm font-medium text-gray-700 mb-2">
                Menu Location <span class="text-red-500">*</span>
            </label>
            <select name="location"
                    id="location"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                    required>
                <option value="main" {{ old('location', $navigation->location ?? 'main') == 'main' ? 'selected' : '' }}>Main Menu</option>
                <option value="footer" {{ old('location', $navigation->location ?? '') == 'footer' ? 'selected' : '' }}>Footer Menu</option>
                <option value="sidebar" {{ old('location', $navigation->location ?? '') == 'sidebar' ? 'selected' : '' }}>Sidebar Menu</option>
            </select>
            @error('location')
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
                   value="{{ old('order', $navigation->order ?? 0) }}"
                   min="0"
                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
            <p class="text-xs text-gray-500 mt-1">Display order (0 = first)</p>
            @error('order')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <!-- Parent Item -->
    <div>
        <label for="parent_id" class="block text-sm font-medium text-gray-700 mb-2">
            Parent Menu Item
        </label>
        <select name="parent_id"
                id="parent_id"
                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
            <option value="">None (Top Level)</option>
            @foreach($navigations ?? [] as $nav)
                @if(!isset($navigation) || $nav->id != $navigation->id)
                    <option value="{{ $nav->id }}" {{ old('parent_id', $navigation->parent_id ?? '') == $nav->id ? 'selected' : '' }}>
                        {{ $nav->title }} ({{ ucfirst($nav->location) }})
                    </option>
                @endif
            @endforeach
        </select>
        <p class="text-xs text-gray-500 mt-1">Create a dropdown by selecting a parent item</p>
        @error('parent_id')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- Target -->
    <div>
        <label for="target" class="block text-sm font-medium text-gray-700 mb-2">
            Open Link In
        </label>
        <select name="target"
                id="target"
                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
            <option value="_self" {{ old('target', $navigation->target ?? '_self') == '_self' ? 'selected' : '' }}>Same Window</option>
            <option value="_blank" {{ old('target', $navigation->target ?? '') == '_blank' ? 'selected' : '' }}>New Window</option>
        </select>
        @error('target')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- CSS Classes -->
    <div>
        <label for="css_classes" class="block text-sm font-medium text-gray-700 mb-2">
            CSS Classes
        </label>
        <input type="text"
               name="css_classes"
               id="css_classes"
               value="{{ old('css_classes', $navigation->css_classes ?? '') }}"
               placeholder="custom-class another-class"
               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
        <p class="text-xs text-gray-500 mt-1">Space-separated CSS classes to apply</p>
        @error('css_classes')
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
            <option value="active" {{ old('status', $navigation->status ?? 'active') == 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ old('status', $navigation->status ?? '') == 'inactive' ? 'selected' : '' }}>Inactive</option>
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
