<div class="space-y-6">
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Name <span class="text-red-500">*</span></label>
        <input type="text" name="name" id="name"
               value="{{ old('name', $tag->name ?? '') }}"
               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" required>
        @error('name') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="slug" class="block text-sm font-medium text-gray-700 mb-2">Slug <span class="text-red-500">*</span></label>
        <input type="text" name="slug" id="slug"
               value="{{ old('slug', $tag->slug ?? '') }}"
               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" required>
        @error('slug') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="flex items-center justify-end gap-4 pt-4">
        <a href="{{ route('admin.tags.index') }}" class="px-6 py-2 bg-gray-200 text-gray-700 font-medium rounded-md hover:bg-gray-300 transition">Cancel</a>
        <button type="submit" class="px-6 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 transition">Save Tag</button>
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('name').addEventListener('input', function() {
        const name = this.value;
        const slug = name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
        document.getElementById('slug').value = slug;
    });
</script>
@endpush
