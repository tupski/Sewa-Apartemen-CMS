<div class="space-y-6">
    <!-- Title -->
    <div>
        <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
            Title <span class="text-red-500">*</span>
        </label>
        <input type="text"
               name="title"
               id="title"
               value="{{ old('title', $page->title ?? '') }}"
               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
               required>
        @error('title')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- Slug (auto-generated from title, still editable) -->
    <div>
        <label for="slug" class="block text-sm font-medium text-gray-700 mb-2">
            Slug <span class="text-red-500">*</span>
        </label>
        <div class="flex items-center gap-2">
            <span class="text-sm text-gray-400 shrink-0">{{ url('/') }}/</span>
            <input type="text"
                   name="slug"
                   id="slug"
                   value="{{ old('slug', $page->slug ?? '') }}"
                   class="flex-1 w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                   required>
        </div>
        <p class="text-xs text-gray-500 mt-1">Auto-generated from the title. You can edit it if needed. The page will be publicly available at this URL when published.</p>
        @error('slug')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- Content (WYSIWYG) -->
    <div>
        <label for="content" class="block text-sm font-medium text-gray-700 mb-2">
            Content
        </label>
        <textarea name="content"
                  id="content"
                  rows="16"
                  class="wysiwyg w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm">{{ old('content', $page->content ?? '') }}</textarea>
        <p class="text-xs text-gray-500 mt-1">Use the rich text editor to format content, add images, links, and more.</p>
        @error('content')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Status -->
        <div>
            <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                Status <span class="text-red-500">*</span>
            </label>
            <select name="status"
                    id="status"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                    required>
                <option value="draft" {{ old('status', $page->status ?? 'draft') == 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="published" {{ old('status', $page->status ?? '') == 'published' ? 'selected' : '' }}>Published</option>
                <option value="scheduled" {{ old('status', $page->status ?? '') == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
            </select>
            @error('status')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Published At -->
        <div id="published-at-field">
            <label for="published_at" class="block text-sm font-medium text-gray-700 mb-2">
                Published At
            </label>
            <input type="datetime-local"
                   name="published_at"
                   id="published_at"
                   value="{{ old('published_at', isset($page->published_at) ? $page->published_at->format('Y-m-d\TH:i') : '') }}"
                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
            @error('published_at')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Parent Page -->
        <div>
            <label for="parent_id" class="block text-sm font-medium text-gray-700 mb-2">
                Parent Page
            </label>
            <select name="parent_id"
                    id="parent_id"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                <option value="">None (Top Level)</option>
                @foreach($pages ?? [] as $parentPage)
                    @if(!isset($page) || $parentPage->id != $page->id)
                        <option value="{{ $parentPage->id }}" {{ old('parent_id', $page->parent_id ?? '') == $parentPage->id ? 'selected' : '' }}>
                            {{ $parentPage->title }}
                        </option>
                    @endif
                @endforeach
            </select>
            @error('parent_id')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Template -->
        <div>
            <label for="template" class="block text-sm font-medium text-gray-700 mb-2">
                Template
            </label>
            <select name="template"
                    id="template"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                <option value="default" {{ old('template', $page->template ?? 'default') == 'default' ? 'selected' : '' }}>Default</option>
                <option value="full-width" {{ old('template', $page->template ?? '') == 'full-width' ? 'selected' : '' }}>Full Width</option>
                <option value="landing" {{ old('template', $page->template ?? '') == 'landing' ? 'selected' : '' }}>Landing Page</option>
                <option value="contact" {{ old('template', $page->template ?? '') == 'contact' ? 'selected' : '' }}>Contact</option>
            </select>
            @error('template')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <!-- SEO Section -->
    <div class="border-t border-gray-200 pt-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">SEO Meta Information</h3>

        <div class="space-y-4">
            <!-- Meta Title -->
            <div>
                <label for="seo_meta_title" class="block text-sm font-medium text-gray-700 mb-2">
                    Meta Title
                </label>
                <input type="text"
                       name="seo[meta_title]"
                       id="seo_meta_title"
                       value="{{ old('seo.meta_title', $page->seo->meta_title ?? '') }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                <p class="text-xs text-gray-500 mt-1">Recommended: 50-60 characters</p>
                @error('seo.meta_title')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Meta Description -->
            <div>
                <label for="seo_meta_description" class="block text-sm font-medium text-gray-700 mb-2">
                    Meta Description
                </label>
                <textarea name="seo[meta_description]"
                          id="seo_meta_description"
                          rows="3"
                          class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">{{ old('seo.meta_description', $page->seo->meta_description ?? '') }}</textarea>
                <p class="text-xs text-gray-500 mt-1">Recommended: 150-160 characters</p>
                @error('seo.meta_description')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Meta Keywords (stored in open_graph.keywords for compatibility) -->
            <div>
                <label for="seo_meta_keywords" class="block text-sm font-medium text-gray-700 mb-2">
                    Meta Keywords
                </label>
                <input type="text"
                       name="seo[open_graph][keywords]"
                       id="seo_meta_keywords"
                       value="{{ old('seo.open_graph.keywords', $page->seo->open_graph['keywords'] ?? '') }}"
                       placeholder="keyword1, keyword2, keyword3"
                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                <p class="text-xs text-gray-500 mt-1">Separate keywords with commas</p>
                @error('seo.open_graph.keywords')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const titleInput = document.getElementById('title');
        const slugInput = document.getElementById('slug');
        let slugDirty = false;

        // Mark slug as manually edited once the user types in it
        if (slugInput) {
            slugInput.addEventListener('input', function () { slugDirty = true; });
        }

        // Auto-generate slug from title only if the user hasn't manually edited it
        if (titleInput && slugInput) {
            titleInput.addEventListener('input', function () {
                if (slugDirty) return;
                const slug = this.value
                    .toLowerCase()
                    .trim()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '')
                    .slice(0, 80);
                slugInput.value = slug;
            });
        }

        // Initialise TinyMCE rich text editor
        if (typeof tinymce !== 'undefined') {
            tinymce.init({
                selector: '#content',
                height: 450,
                menubar: false,
                plugins: 'lists link image table code autolink fullscreen preview',
                toolbar: 'undo redo | blocks | bold italic underline strikethrough | bullist numlist | link image | alignleft aligncenter alignright | table | code fullscreen',
                branding: false,
                convert_urls: false,
                relative_urls: false,
                setup: function (editor) {
                    editor.on('change', function () { editor.save(); });
                }
            });
        }
    });
</script>
@endpush
