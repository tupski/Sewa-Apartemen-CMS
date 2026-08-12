<div class="space-y-6">
    <div>
        <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Title <span class="text-red-500">*</span></label>
        <input type="text" name="title" id="title"
               value="{{ old('title', $post->title ?? '') }}"
               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" required>
        @error('title') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="slug" class="block text-sm font-medium text-gray-700 mb-2">Slug <span class="text-red-500">*</span></label>
        <input type="text" name="slug" id="slug"
               value="{{ old('slug', $post->slug ?? '') }}"
               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" required>
        <p class="text-xs text-gray-500 mt-1">URL-friendly version of the title</p>
        @error('slug') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2">Category</label>
            <select name="category_id" id="category_id" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                <option value="">None</option>
                @foreach($categories ?? [] as $cat)
                    <option value="{{ $cat->id }}" {{ old('category_id', $post->category_id ?? '') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>
            @error('category_id') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status <span class="text-red-500">*</span></label>
            <select name="status" id="status" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" required>
                <option value="draft" {{ old('status', $post->status ?? 'draft') == 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="published" {{ old('status', $post->status ?? '') == 'published' ? 'selected' : '' }}>Published</option>
            </select>
            @error('status') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label for="tags" class="block text-sm font-medium text-gray-700 mb-2">Tags</label>
        <input type="text" name="tags" id="tags"
               value="{{ old('tags', $postTags ?? '') }}"
               placeholder="tag1, tag2, tag3"
               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
        <p class="text-xs text-gray-500 mt-1">Separate tags with commas</p>
        @error('tags') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="excerpt" class="block text-sm font-medium text-gray-700 mb-2">Excerpt</label>
        <textarea name="excerpt" id="excerpt" rows="3"
                  class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">{{ old('excerpt', $post->excerpt ?? '') }}</textarea>
        @error('excerpt') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="content" class="block text-sm font-medium text-gray-700 mb-2">Content <span class="text-red-500">*</span></label>
        <textarea name="content" id="content" rows="20"
                  class="wysiwyg w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 font-mono text-sm"
                  required>{{ old('content', $post->content ?? '') }}</textarea>
        <p class="text-xs text-gray-500 mt-1">HTML is supported. Use <h2>, <p>, <strong>, <em>, <ul>, <ol>, <li>, <a>, <img>, <blockquote></p>
        @error('content') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="featured_image" class="block text-sm font-medium text-gray-700 mb-2">Featured Image</label>
        <input type="file" name="featured_image" id="featured_image"
               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
        @if(isset($post) && $post->featured_image)
            <p class="text-sm text-gray-500 mt-1">Current: {{ $post->featured_image }}</p>
        @endif
        @error('featured_image') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
    </div>

    <!-- SEO Section -->
    <div class="border-t border-gray-200 pt-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">SEO Meta Information</h3>
        <div class="space-y-4">
            <div>
                <label for="seo_meta_title" class="block text-sm font-medium text-gray-700 mb-2">Meta Title</label>
                <input type="text" name="seo[meta_title]" id="seo_meta_title"
                       value="{{ old('seo.meta_title', $post->seo->meta_title ?? '') }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label for="seo_meta_description" class="block text-sm font-medium text-gray-700 mb-2">Meta Description</label>
                <textarea name="seo[meta_description]" id="seo_meta_description" rows="3"
                          class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">{{ old('seo.meta_description', $post->seo->meta_description ?? '') }}</textarea>
            </div>
        </div>
    </div>

    <div class="flex items-center justify-end gap-4 pt-4">
        <a href="{{ route('admin.posts.index') }}" class="px-6 py-2 bg-gray-200 text-gray-700 font-medium rounded-md hover:bg-gray-300 transition">Cancel</a>
        <button type="submit" class="px-6 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 transition">Save Post</button>
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('title').addEventListener('input', function() {
        const title = this.value;
        const slug = title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
        document.getElementById('slug').value = slug;
    });
</script>
@endpush
