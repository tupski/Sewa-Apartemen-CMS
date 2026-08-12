@php
    $freeIcons = [
        'fa-wifi', 'fa-utensils', 'fa-dumbbell', 'fa-person-swimming', 'fa-car',
        'fa-elevator', 'fa-snowflake', 'fa-tv', 'fa-bed', 'fa-shower',
        'fa-toilet', 'fa-square-parking', 'fa-shield-heart', 'fa-fire-extinguisher', 'fa-wind',
        'fa-fan', 'fa-plug', 'fa-lightbulb', 'fa-door-open', 'fa-key',
        'fa-lock', 'fa-couch', 'fa-chair', 'fa-building', 'fa-building-columns',
        'fa-basketball', 'fa-volleyball', 'fa-table-tennis-paddle-ball', 'fa-children', 'fa-dog',
        'fa-paw', 'fa-tree', 'fa-umbrella-beach', 'fa-water', 'fa-droplet',
        'fa-bolt', 'fa-gas-pump', 'fa-motorcycle', 'fa-bicycle', 'fa-bus',
        'fa-train-subway', 'fa-plane', 'fa-camera', 'fa-mug-hot', 'fa-soap',
        'fa-kitchen-set', 'fa-phone', 'fa-bell', 'fa-clock', 'fa-calendar-check',
        'fa-heart', 'fa-people-roof', 'fa-fire-burner', 'fa-temperature-arrow-down', 'fa-bath',
    ];
@endphp

<div class="space-y-6">
    <!-- Name -->
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
            Name <span class="text-red-500">*</span>
        </label>
        <input type="text"
               name="name"
               id="name"
               value="{{ old('name', $amenity->name ?? '') }}"
               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
               required>
        @error('name')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- Slug -->
    <div>
        <label for="slug" class="block text-sm font-medium text-gray-700 mb-2">
            Slug
        </label>
        <input type="text"
               name="slug"
               id="slug"
               value="{{ old('slug', $amenity->slug ?? '') }}"
               placeholder="auto-generated from name"
               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
        <p class="text-xs text-gray-500 mt-1">Leave empty to auto-generate</p>
        @error('slug')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Category -->
        <div>
            <label for="category" class="block text-sm font-medium text-gray-700 mb-2">
                Category <span class="text-red-500">*</span>
            </label>
            <select name="category"
                    id="category"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                    required>
                <option value="property" {{ old('category', $amenity->category ?? 'property') == 'property' ? 'selected' : '' }}>Property</option>
                <option value="unit" {{ old('category', $amenity->category ?? '') == 'unit' ? 'selected' : '' }}>Unit</option>
            </select>
            @error('category')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Active -->
        <div class="flex items-end pb-2">
            <label class="inline-flex items-center">
                <input type="checkbox" name="is_active" value="1"
                       class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                       {{ old('is_active', $amenity->is_active ?? true) ? 'checked' : '' }}>
                <span class="ml-2 text-sm font-medium text-gray-700">Active</span>
            </label>
        </div>
    </div>

    <!-- Icon Picker (Font Awesome 6 Free) -->
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            Icon <span class="text-gray-400 font-normal">(Font Awesome 6 Free)</span>
        </label>
        <input type="hidden" name="icon" id="icon" value="{{ old('icon', $amenity->icon ?? '') }}">
        <div class="grid grid-cols-6 sm:grid-cols-8 md:grid-cols-10 gap-2 p-3 border border-gray-300 rounded-md bg-gray-50 max-h-56 overflow-y-auto">
            @foreach($freeIcons as $icon)
                <button type="button"
                        data-icon="{{ $icon }}"
                        title="{{ $icon }}"
                        class="icon-pick aspect-square flex items-center justify-center rounded-md border border-gray-200 bg-white text-gray-600 hover:border-blue-400 hover:text-blue-600 hover:shadow-sm transition">
                    <i class="fa-solid {{ $icon }} text-lg"></i>
                </button>
            @endforeach
        </div>
        <div class="mt-2 flex items-center gap-2">
            <span class="text-sm text-gray-500">or custom class:</span>
            <input type="text"
                   id="icon-custom"
                   value="{{ old('icon', $amenity->icon ?? '') }}"
                   placeholder="fa-solid fa-martini-glass"
                   class="flex-1 px-3 py-1.5 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">
            <span id="icon-preview" class="text-gray-500 w-8 text-center"><i class="fa-solid {{ old('icon', $amenity->icon ?? 'fa-wifi') }}"></i></span>
        </div>
        @error('icon')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- Description -->
    <div>
        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
            Description
        </label>
        <textarea name="description"
                  id="description"
                  rows="4"
                  class="wysiwyg w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">{{ old('description', $amenity->description ?? '') }}</textarea>
        @error('description')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>
</div>

@push('scripts')
<script>
    (function () {
        var hidden = document.getElementById('icon');
        var custom = document.getElementById('icon-custom');
        var preview = document.getElementById('icon-preview');

        function setIcon(value) {
            hidden.value = value;
            custom.value = value;
            preview.innerHTML = value ? '<i class="fa-solid ' + value + '"></i>' : '';
            document.querySelectorAll('.icon-pick').forEach(function (btn) {
                var active = btn.dataset.icon === value;
                btn.classList.toggle('bg-blue-600', active);
                btn.classList.toggle('text-white', active);
                btn.classList.toggle('border-blue-600', active);
                btn.classList.toggle('text-gray-600', !active);
            });
        }

        document.querySelectorAll('.icon-pick').forEach(function (btn) {
            btn.addEventListener('click', function () { setIcon(btn.dataset.icon); });
        });

        custom.addEventListener('input', function () {
            hidden.value = custom.value.trim();
            preview.innerHTML = custom.value.trim() ? '<i class="fa-solid ' + custom.value.trim() + '"></i>' : '';
        });

        setIcon(hidden.value || 'fa-wifi');
    })();

    // Auto-generate slug from name
    document.getElementById('name').addEventListener('input', function () {
        var slugField = document.getElementById('slug');
        if (!slugField.dataset.touched) {
            slugField.value = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
        }
    });
    document.getElementById('slug').addEventListener('input', function () {
        this.dataset.touched = '1';
    });
</script>
@endpush
