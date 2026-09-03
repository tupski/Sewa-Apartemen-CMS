<div class="space-y-6 pb-24" id="post-form">

    {{-- ──────────── Hidden status field (set by Save Draft / Publish buttons) ──────────── --}}
    <input type="hidden" name="status" id="post_status" value="{{ old('status', $post->status ?? 'draft') }}">

    {{-- ──────────── Title ──────────── --}}
    <div>
        <label for="title" class="block text-sm font-medium text-gray-700 mb-2">{{ __('Title') }} <span class="text-red-500">*</span></label>
        <input type="text" name="title" id="title"
               value="{{ old('title', $post->title ?? '') }}"
               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" required>
        @error('title') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- ──────────── Slug (optional, auto-generated) ──────────── --}}
    <div>
        <label for="slug" class="block text-sm font-medium text-gray-700 mb-2">{{ __('Slug') }}</label>
        <input type="text" name="slug" id="slug"
               value="{{ old('slug', $post->slug ?? '') }}"
               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
               placeholder="{{ __('auto-generated from title') }}">
        <p class="text-xs text-gray-500 mt-1">{{ __('URL-friendly version. Leave empty to auto-generate from the title.') }}</p>
        @error('slug') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- ──────────── Category + Excerpt grid ──────────── --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Category --}}
        <div>
            <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2">{{ __('Category') }}</label>
            <div class="flex gap-2">
                <select name="category_id" id="category_id"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    <option value="">{{ __('None') }}</option>
                    @foreach($categories ?? [] as $cat)
                        <option value="{{ $cat->id }}" {{ old('category_id', $post->category_id ?? '') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
                <button type="button" id="add-category-btn"
                        class="px-3 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition text-sm shrink-0"
                        title="{{ __('Add new category') }}">
                    <i class="fa-solid fa-plus"></i>
                </button>
            </div>
            @error('category_id') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Excerpt --}}
        <div>
            <label for="excerpt" class="block text-sm font-medium text-gray-700 mb-2">{{ __('Excerpt') }}</label>
            <input type="text" name="excerpt" id="excerpt"
                   value="{{ old('excerpt', $post->excerpt ?? '') }}"
                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                   placeholder="{{ __('Short summary of the post') }}">
            @error('excerpt') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- ──────────── Content (Quill WYSIWYG) ──────────── --}}
    <div>
        <label for="content" class="block text-sm font-medium text-gray-700 mb-2">{{ __('Content') }} <span class="text-red-500">*</span></label>
        {{-- The admin layout auto-inits Quill on textarea.wysiwyg. We use a custom class
             'post-content-editor' instead so we can set up our own toolbar with image upload. --}}
        <textarea name="content" id="content" rows="20"
                  class="post-content-editor w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 font-mono text-sm"
                  required>{{ old('content', $post->content ?? '') }}</textarea>
        @error('content') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- ──────────── Featured Image (Drag & Drop) ──────────── --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Featured Image') }}</label>
        <div id="featured-image-dropzone"
             class="relative border-2 border-dashed border-gray-300 rounded-lg p-8 text-center cursor-pointer hover:border-blue-400 transition group">
            <input type="file" name="featured_image" id="featured_image_input" accept="image/jpeg,image/png,image/webp,image/gif"
                   class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
            <div id="featured-image-preview" class="hidden relative">
                <img id="featured-image-img" src="" alt="Preview" class="max-h-64 mx-auto rounded-lg shadow">
                <button type="button" id="featured-image-remove"
                        class="absolute -top-2 -right-2 w-7 h-7 bg-red-600 text-white rounded-full shadow hover:bg-red-700 transition flex items-center justify-center z-20"
                        title="{{ __('Remove image') }}">
                    <i class="fa-solid fa-times text-xs"></i>
                </button>
            </div>
            <div id="featured-image-placeholder" class="text-gray-400 group-hover:text-gray-600 transition">
                <i class="fa-solid fa-cloud-upload-alt text-4xl mb-2"></i>
                <p class="text-sm">{{ __('Drag & drop image here or click to browse') }}</p>
                <p class="text-xs mt-1">{{ __('JPEG, PNG, WebP or GIF. Max 5 MB.') }}</p>
            </div>
        </div>
        @error('featured_image') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- ──────────── Tags (Pills / Badges) ──────────── --}}
    <div>
        <label for="tags-input" class="block text-sm font-medium text-gray-700 mb-2">{{ __('Tags') }}</label>
        <div class="flex flex-wrap gap-2 mb-2" id="tags-pills-container"></div>
        <input type="text" id="tags-input"
               placeholder="{{ __('Type a tag and press Enter or comma') }}"
               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
        <input type="hidden" name="tags" id="tags-hidden" value="{{ old('tags', $postTags ?? '') }}">
        <p class="text-xs text-gray-500 mt-1">{{ __('Press Enter or comma to add a tag. Click × to remove.') }}</p>
        @error('tags') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- ──────────── SEO Meta Information ──────────── --}}
    <div class="border-t border-gray-200 pt-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('SEO Meta Information') }}</h3>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- SEO inputs --}}
            <div class="space-y-4">
                <div>
                    <label for="seo_meta_title" class="block text-sm font-medium text-gray-700 mb-2">{{ __('Meta Title') }}</label>
                    <input type="text" name="seo[meta_title]" id="seo_meta_title"
                           value="{{ old('seo.meta_title', $post->seo->meta_title ?? '') }}"
                           maxlength="60"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                           placeholder="{{ $post->title ?? __('Post title') }}">
                    <div class="flex justify-between mt-1">
                        <span class="text-xs text-gray-500">{{ __('Recommended: 50-60 characters') }}</span>
                        <span id="meta-title-counter" class="text-xs text-gray-500">0/60</span>
                    </div>
                </div>
                <div>
                    <label for="seo_meta_description" class="block text-sm font-medium text-gray-700 mb-2">{{ __('Meta Description') }}</label>
                    <textarea name="seo[meta_description]" id="seo_meta_description" rows="3"
                              maxlength="155"
                              class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                              placeholder="{{ __('A brief description of the post for search results') }}">{{ old('seo.meta_description', $post->seo->meta_description ?? '') }}</textarea>
                    <div class="flex justify-between mt-1">
                        <span class="text-xs text-gray-500">{{ __('Recommended: 50-155 characters') }}</span>
                        <span id="meta-desc-counter" class="text-xs text-gray-500">0/155</span>
                    </div>
                </div>
            </div>

            {{-- Google Preview --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Google Search Preview') }}</label>
                {{-- Tabs: Desktop / Mobile --}}
                <div class="flex gap-2 mb-3">
                    <button type="button" class="preview-tab-btn px-3 py-1 text-xs font-medium rounded bg-blue-600 text-white" data-preview="desktop">{{ __('Desktop') }}</button>
                    <button type="button" class="preview-tab-btn px-3 py-1 text-xs font-medium rounded bg-gray-200 text-gray-700 hover:bg-gray-300" data-preview="mobile">{{ __('Mobile') }}</button>
                </div>

                {{-- Desktop preview --}}
                <div id="preview-desktop" class="border border-gray-200 rounded-lg p-4 bg-white">
                    <div class="flex items-center gap-1 text-xs text-gray-600 mb-1">
                        <span class="w-4 h-4 bg-gray-300 rounded-full inline-flex items-center justify-center text-[8px] font-bold text-white">G</span>
                        <span class="text-gray-500">›</span>
                        <span class="text-gray-500" id="preview-breadcrumb">example.com</span>
                        <span class="text-gray-500">›</span>
                        <span class="text-gray-500" id="preview-breadcrumb-slug">blog</span>
                    </div>
                    <div class="text-blue-800 text-lg font-medium hover:underline cursor-pointer truncate" id="preview-title">
                        {{ $post->title ?? __('Post Title') }}
                    </div>
                    <div class="text-green-700 text-sm truncate" id="preview-url">
                        https://example.com/blog/{{ $post->slug ?? 'post-slug' }}
                    </div>
                    <div class="text-gray-600 text-sm mt-1 leading-snug line-clamp-2" id="preview-description">
                        {{ $post->seo->meta_description ?? ($post->excerpt ?? __('A brief description of the post for search results')) }}
                    </div>
                </div>

                {{-- Mobile preview --}}
                <div id="preview-mobile" class="hidden border border-gray-200 rounded-lg p-4 bg-white max-w-sm">
                    <div class="text-sm text-gray-600 truncate" id="preview-mobile-url">
                        https://example.com/blog/{{ $post->slug ?? 'post-slug' }}
                    </div>
                    <div class="text-blue-800 text-base font-medium hover:underline cursor-pointer truncate mt-1" id="preview-mobile-title">
                        {{ $post->title ?? __('Post Title') }}
                    </div>
                    <div class="text-gray-600 text-sm mt-1 leading-snug line-clamp-3" id="preview-mobile-description">
                        {{ $post->seo->meta_description ?? ($post->excerpt ?? __('A brief description of the post for search results')) }}
                    </div>
                </div>
            </div>
        </div>

        {{-- ──────────── Open Graph / Social Share Preview ────────────
             Mirrors what WhatsApp / Facebook / X render from the post's
             og:title, og:description and og:image. The tags themselves are
             emitted on the public page by SeoService — this block is a
             read-only preview so editors can see the result before publishing.
        --}}
        <div class="mt-8 pt-6 border-t border-gray-200" data-testid="og-preview">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                <h4 class="text-base font-semibold text-gray-800">{{ __('Social Share Preview') }}</h4>
                <div class="flex gap-2">
                    <button type="button" class="og-tab-btn px-3 py-1 text-xs font-medium rounded bg-blue-600 text-white" data-og="whatsapp">
                        <i class="fa-brands fa-whatsapp mr-1"></i>WhatsApp
                    </button>
                    <button type="button" class="og-tab-btn px-3 py-1 text-xs font-medium rounded bg-gray-200 text-gray-700 hover:bg-gray-300" data-og="facebook">
                        <i class="fa-brands fa-facebook mr-1"></i>Facebook
                    </button>
                </div>
            </div>

            <p class="text-xs text-gray-500 mb-3">
                {{ __('Uses the meta title/description above and the featured image. Save the post to update the live preview on social platforms.') }}
            </p>

            {{-- WhatsApp-style chat bubble --}}
            <div id="og-preview-whatsapp" class="max-w-sm">
                <div class="rounded-lg p-2" style="background-color:#dcf8c6">
                    <div class="bg-white rounded-md overflow-hidden shadow-sm">
                        <img id="og-wa-image" src="" alt=""
                             class="w-full h-40 object-cover hidden">
                        <div id="og-wa-noimage" class="w-full h-40 bg-gray-100 flex items-center justify-center text-gray-400">
                            <i class="fa-regular fa-image text-3xl" aria-hidden="true"></i>
                        </div>
                        <div class="p-2.5">
                            <p class="text-[13px] font-semibold text-gray-900 leading-snug line-clamp-2" id="og-wa-title">{{ $post->title ?? __('Post Title') }}</p>
                            <p class="text-[12px] text-gray-600 leading-snug line-clamp-2 mt-0.5" id="og-wa-description">{{ $post->seo->meta_description ?? ($post->excerpt ?? __('A brief description of the post for search results')) }}</p>
                            <p class="text-[11px] text-gray-400 mt-1 truncate" id="og-wa-domain">{{ parse_url(config('app.url'), PHP_URL_HOST) ?: 'example.com' }}</p>
                        </div>
                    </div>
                    <p class="text-[12px] text-gray-700 mt-1.5 px-1 truncate" id="og-wa-url">{{ url('/blog/'.($post->slug ?? 'post-slug')) }}</p>
                </div>
            </div>

            {{-- Facebook-style link card --}}
            <div id="og-preview-facebook" class="hidden max-w-md">
                <div class="border border-gray-300 rounded-md overflow-hidden bg-white">
                    <img id="og-fb-image" src="" alt=""
                         class="w-full h-48 object-cover hidden">
                    <div id="og-fb-noimage" class="w-full h-48 bg-gray-100 flex items-center justify-center text-gray-400">
                        <i class="fa-regular fa-image text-4xl" aria-hidden="true"></i>
                    </div>
                    <div class="px-3 py-2.5 bg-gray-50 border-t border-gray-200">
                        <p class="text-[11px] uppercase tracking-wide text-gray-500 truncate" id="og-fb-domain">{{ parse_url(config('app.url'), PHP_URL_HOST) ?: 'example.com' }}</p>
                        <p class="text-[15px] font-semibold text-gray-900 leading-snug line-clamp-2 mt-0.5" id="og-fb-title">{{ $post->title ?? __('Post Title') }}</p>
                        <p class="text-[13px] text-gray-600 leading-snug line-clamp-1 mt-0.5" id="og-fb-description">{{ $post->seo->meta_description ?? ($post->excerpt ?? __('A brief description of the post for search results')) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ──────────── Bottom action buttons ──────────── --}}
    <div class="flex items-center justify-between gap-4 pt-4 border-t border-gray-200" id="bottom-actions">
        <a href="{{ route('admin.posts.index') }}" class="px-6 py-2 bg-gray-200 text-gray-700 font-medium rounded-md hover:bg-gray-300 transition text-center">
            <i class="fa-solid fa-arrow-left mr-1"></i> {{ __('Cancel') }}
        </a>
        <div class="flex gap-3">
            <button type="button" class="save-draft-btn px-6 py-2 bg-gray-600 text-white font-medium rounded-md hover:bg-gray-700 transition">
                <i class="fa-solid fa-file-pen mr-1"></i> {{ __('Save Draft') }}
            </button>
            <button type="button" class="publish-btn px-6 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 transition">
                <i class="fa-solid fa-check mr-1"></i> {{ __('Publish') }}
            </button>
        </div>
    </div>
</div>

{{-- ──────────── Floating sticky action bar ──────────── --}}
<div id="sticky-bar"
     class="fixed bottom-0 left-0 right-0 z-40 bg-white border-t border-gray-200 shadow-lg px-4 py-3 transform translate-y-0 transition-transform duration-200"
     style="margin-left: var(--sidebar-width, 16rem);">
    <div class="max-w-4xl mx-auto flex items-center justify-between gap-4">
        <a href="{{ route('admin.posts.index') }}" class="px-5 py-2 bg-gray-200 text-gray-700 font-medium rounded-md hover:bg-gray-300 transition text-sm">
            <i class="fa-solid fa-arrow-left mr-1"></i> {{ __('Cancel') }}
        </a>
        <div class="flex gap-2">
            <button type="button" class="save-draft-btn px-5 py-2 bg-gray-600 text-white font-medium rounded-md hover:bg-gray-700 transition text-sm">
                <i class="fa-solid fa-file-pen mr-1"></i> {{ __('Save Draft') }}
            </button>
            <button type="button" class="publish-btn px-5 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 transition text-sm">
                <i class="fa-solid fa-check mr-1"></i> {{ __('Publish') }}
            </button>
        </div>
    </div>
</div>

{{-- ──────────── Inline "Add Category" Modal ──────────── --}}
<div id="add-category-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4">
        <div class="flex items-center justify-between mb-4">
            <h4 class="text-lg font-semibold text-gray-800">{{ __('Add New Category') }}</h4>
            <button type="button" id="close-category-modal" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <div class="space-y-4">
            <div>
                <label for="new-category-name" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Category Name') }} <span class="text-red-500">*</span></label>
                <input type="text" id="new-category-name" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" required>
            </div>
            <div>
                <label for="new-category-slug" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Slug') }}</label>
                <input type="text" id="new-category-slug" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" placeholder="{{ __('auto-generated') }}">
            </div>
            <div>
                <label for="new-category-desc" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Description') }}</label>
                <input type="text" id="new-category-desc" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div id="category-modal-error" class="text-red-500 text-sm hidden"></div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" id="cancel-category-modal" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition text-sm">{{ __('Cancel') }}</button>
                <button type="button" id="save-category-btn"
                        class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition text-sm">
                    <i class="fa-solid fa-plus mr-1"></i> {{ __('Add Category') }}
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function initPostForm() {
        // Guard against double-initialization (DOMContentLoaded + turbo:load can both fire)
        var formRoot = document.getElementById('post-form');
        if (!formRoot || formRoot.dataset.postFormInited) return;
        formRoot.dataset.postFormInited = '1';
        // ═══════════════════════════════════════════════════════════════
        // 1. SLUG: auto-generate from title (stop if user edits slug)
        // ═══════════════════════════════════════════════════════════════
        (function () {
            var titleInput = document.getElementById('title');
            var slugInput = document.getElementById('slug');
            if (!titleInput || !slugInput) return;

            var slugEdited = false;
            var slugOriginal = slugInput.value;

            // If slug was pre-filled (edit mode), mark as user-edited
            if (slugOriginal.trim().length > 0) {
                slugEdited = true;
            }

            titleInput.addEventListener('input', function () {
                if (!slugEdited) {
                    slugInput.value = slugify(this.value);
                }
            });

            slugInput.addEventListener('input', function () {
                if (this.value !== slugify(titleInput.value)) {
                    slugEdited = true;
                } else {
                    slugEdited = false;
                }
            });
        })();

        // ═══════════════════════════════════════════════════════════════
        // OPEN GRAPH PREVIEW HELPERS
        // Declared at initPostForm scope (function declarations hoist) so the
        // featured-image section and the SEO section can both drive the
        // WhatsApp/Facebook share cards.
        // ═══════════════════════════════════════════════════════════════
        var OG_BLOG_BASE = @json(rtrim(url('/blog'), '/'));

        function setOgImage(url) {
            [
                { img: 'og-wa-image', empty: 'og-wa-noimage' },
                { img: 'og-fb-image', empty: 'og-fb-noimage' }
            ].forEach(function (pair) {
                var imgEl = document.getElementById(pair.img);
                var emptyEl = document.getElementById(pair.empty);
                if (!imgEl || !emptyEl) return;
                if (url) {
                    imgEl.src = url;
                    imgEl.classList.remove('hidden');
                    emptyEl.classList.add('hidden');
                } else {
                    imgEl.removeAttribute('src');
                    imgEl.classList.add('hidden');
                    emptyEl.classList.remove('hidden');
                }
            });
        }

        function updateOgText(title, description, slug) {
            var map = {
                'og-wa-title': title,
                'og-fb-title': title,
                'og-wa-description': description,
                'og-fb-description': description,
                'og-wa-url': OG_BLOG_BASE + '/' + slug
            };
            Object.keys(map).forEach(function (id) {
                var el = document.getElementById(id);
                if (el) el.textContent = map[id];
            });
        }

        // Social-share preview tabs (WhatsApp / Facebook)
        (function () {
            document.querySelectorAll('.og-tab-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var target = this.getAttribute('data-og');
                    document.querySelectorAll('.og-tab-btn').forEach(function (b) {
                        b.className = 'og-tab-btn px-3 py-1 text-xs font-medium rounded bg-gray-200 text-gray-700 hover:bg-gray-300';
                    });
                    this.className = 'og-tab-btn px-3 py-1 text-xs font-medium rounded bg-blue-600 text-white';

                    var wa = document.getElementById('og-preview-whatsapp');
                    var fb = document.getElementById('og-preview-facebook');
                    if (wa) wa.classList.toggle('hidden', target !== 'whatsapp');
                    if (fb) fb.classList.toggle('hidden', target !== 'facebook');
                });
            });
        })();

        function slugify(text) {
            // Transliterate common accented characters to ASCII
            var map = {
                'á': 'a', 'â': 'a', 'ã': 'a', 'ä': 'a', 'å': 'a', 'à': 'a',
                'é': 'e', 'ê': 'e', 'ë': 'e', 'è': 'e',
                'í': 'i', 'î': 'i', 'ï': 'i', 'ì': 'i',
                'ó': 'o', 'ô': 'o', 'õ': 'o', 'ö': 'o', 'ò': 'o',
                'ú': 'u', 'û': 'u', 'ü': 'u', 'ù': 'u',
                'ý': 'y', 'ÿ': 'y',
                'ñ': 'n', 'ç': 'c',
                'Á': 'A', 'Â': 'A', 'Ã': 'A', 'Ä': 'A', 'Å': 'A', 'À': 'A',
                'É': 'E', 'Ê': 'E', 'Ë': 'E', 'È': 'E',
                'Í': 'I', 'Î': 'I', 'Ï': 'I', 'Ì': 'I',
                'Ó': 'O', 'Ô': 'O', 'Õ': 'O', 'Ö': 'O', 'Ò': 'O',
                'Ú': 'U', 'Û': 'U', 'Ü': 'U', 'Ù': 'U',
                'Ý': 'Y', 'Ÿ': 'Y',
                'Ñ': 'N', 'Ç': 'C'
            };
            return text
                .replace(/[áâãäåàéêëèíîïìóôõöòúûüùýÿñç]/gi, function (m) { return map[m] || m; })
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
        }

        // ═══════════════════════════════════════════════════════════════
        // 2. STATUS: Save Draft / Publish buttons
        // ═══════════════════════════════════════════════════════════════
        (function () {
            var statusInput = document.getElementById('post_status');
            var form = document.querySelector('#post-form').closest('form');

            // Sync Quill content into the hidden textarea before submitting
            // (form.submit() bypasses the submit event listener set by Quill).
            function syncQuillBeforeSubmit() {
                var ta = document.getElementById('content');
                if (!ta) return;
                var editor = document.querySelector('.wysiwyg-container .ql-editor');
                if (editor) {
                    ta.value = editor.innerHTML;
                }
            }

            document.querySelectorAll('.save-draft-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    statusInput.value = 'draft';
                    syncQuillBeforeSubmit();
                    if (form) form.submit();
                });
            });

            document.querySelectorAll('.publish-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    statusInput.value = 'published';
                    syncQuillBeforeSubmit();
                    if (form) form.submit();
                });
            });
        })();

        // ═══════════════════════════════════════════════════════════════
        // 3. QUILL: Initialize WYSIWYG with custom toolbar + image upload
        // ═══════════════════════════════════════════════════════════════
        (function () {
            var ta = document.getElementById('content');
            if (!ta || typeof Quill === 'undefined') return;

            var holder = document.createElement('div');
            holder.classList.add('wysiwyg-container', 'bg-white', 'rounded-md', 'border', 'border-gray-300');
            ta.parentNode.insertBefore(holder, ta);

            var quill = new Quill(holder, {
                theme: 'snow',
                placeholder: '{{ __('Write your content here...') }}',
                modules: {
                    toolbar: {
                        container: [
                            [{ header: [1, 2, 3, false] }],
                            ['bold', 'italic', 'underline', 'strike'],
                            [{ list: 'ordered' }, { list: 'bullet' }],
                            ['blockquote', 'code-block'],
                            ['link', 'image'],
                            [{ align: [] }],
                            ['clean']
                        ],
                        handlers: {
                            image: function () {
                                var input = document.createElement('input');
                                input.setAttribute('type', 'file');
                                input.setAttribute('accept', 'image/jpeg,image/png,image/webp,image/gif');
                                input.click();

                                input.onchange = function () {
                                    var file = input.files[0];
                                    if (!file) return;

                                    if (file.size > 5 * 1024 * 1024) {
                                        alert('{{ __('Image must be less than 5 MB.') }}');
                                        return;
                                    }

                                    var formData = new FormData();
                                    formData.append('image', file);
                                    formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

                                    var range = quill.getSelection(true);

                                    fetch('{{ route("admin.posts.upload-image") }}', {
                                        method: 'POST',
                                        body: formData,
                                        headers: {
                                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                        }
                                    })
                                    .then(function (r) { return r.json(); })
                                    .then(function (data) {
                                        if (data.success) {
                                            quill.insertEmbed(range.index, 'image', data.url);
                                        } else {
                                            alert(data.message || '{{ __('Upload failed.') }}');
                                        }
                                    })
                                    .catch(function () {
                                        alert('{{ __('Upload failed. Please try again.') }}');
                                    });
                                };
                            }
                        }
                    }
                }
            });

            // Load existing content
            if (ta.value) {
                try {
                    quill.clipboard.dangerouslyPasteHTML(0, ta.value);
                } catch (e) { /* keep empty */ }
            }

            // Sync hidden textarea on submit
            var form = ta.closest('form');
            function sync() { ta.value = quill.root.innerHTML; }
            quill.on('text-change', sync);
            if (form) form.addEventListener('submit', sync);
            ta.style.display = 'none';
        })();

        // ═══════════════════════════════════════════════════════════════
        // 4. TAGS: Pills / Badges
        // ═══════════════════════════════════════════════════════════════
        (function () {
            var input = document.getElementById('tags-input');
            var hidden = document.getElementById('tags-hidden');
            var container = document.getElementById('tags-pills-container');
            if (!input || !hidden || !container) return;

            var tags = [];

            // Load existing tags from hidden input
            function loadTags() {
                tags = hidden.value.split(',')
                    .map(function (t) { return t.trim(); })
                    .filter(function (t) { return t.length > 0; });
                render();
            }

            function render() {
                container.innerHTML = '';
                tags.forEach(function (tag, index) {
                    var pill = document.createElement('span');
                    pill.className = 'inline-flex items-center gap-1 px-3 py-1 bg-gray-200 text-gray-700 rounded-full text-sm';
                    pill.innerHTML = tag +
                        '<button type="button" class="tag-remove ml-1 text-gray-500 hover:text-red-600 focus:outline-none" data-index="' + index + '">&times;</button>';
                    container.appendChild(pill);
                });
                hidden.value = tags.join(',');
            }

            function addTag(name) {
                name = name.trim().replace(/,$/, '');
                if (!name) return;
                // Check duplicates (case-insensitive)
                var exists = tags.some(function (t) { return t.toLowerCase() === name.toLowerCase(); });
                if (exists) return;
                tags.push(name);
                render();
                input.value = '';
            }

            // Input: comma or Enter triggers add
            input.addEventListener('input', function () {
                if (this.value.indexOf(',') !== -1) {
                    var parts = this.value.split(',');
                    parts.forEach(function (p) { addTag(p); });
                }
            });

            input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ',') {
                    e.preventDefault();
                    addTag(this.value);
                }
            });

            // Remove tag via × button (event delegation)
            container.addEventListener('click', function (e) {
                var btn = e.target.closest('.tag-remove');
                if (!btn) return;
                var index = parseInt(btn.getAttribute('data-index'), 10);
                if (!isNaN(index) && index >= 0 && index < tags.length) {
                    tags.splice(index, 1);
                    render();
                }
            });

            loadTags();
        })();

        // ═══════════════════════════════════════════════════════════════
        // 5. FEATURED IMAGE: Drag & Drop + Preview + Remove
        // ═══════════════════════════════════════════════════════════════
        (function () {
            var dropzone = document.getElementById('featured-image-dropzone');
            var fileInput = document.getElementById('featured_image_input');
            var preview = document.getElementById('featured-image-preview');
            var img = document.getElementById('featured-image-img');
            var placeholder = document.getElementById('featured-image-placeholder');
            var removeBtn = document.getElementById('featured-image-remove');
            if (!dropzone || !fileInput || !preview || !img || !placeholder || !removeBtn) return;

            // Pre-load existing image on edit
            @if(isset($post) && $post->featured_image)
                (function () {
                    var existingUrl = '{{ Storage::url($post->featured_image) }}';
                    if (existingUrl) {
                        img.src = existingUrl;
                        placeholder.classList.add('hidden');
                        preview.classList.remove('hidden');
                        fileInput.disabled = true; // max 1
                        setOgImage(existingUrl);
                    }
                })();
            @endif

            function showPreview(file) {
                if (!file) return;
                var reader = new FileReader();
                reader.onload = function (e) {
                    img.src = e.target.result;
                    placeholder.classList.add('hidden');
                    preview.classList.remove('hidden');
                    fileInput.disabled = true; // max 1 photo
                    // Keep the social-share preview in sync with the picked file.
                    setOgImage(e.target.result);
                };
                reader.readAsDataURL(file);
            }

            // File input change
            fileInput.addEventListener('change', function () {
                if (this.files && this.files[0]) {
                    showPreview(this.files[0]);
                }
            });

            // Drag & drop
            dropzone.addEventListener('dragover', function (e) {
                e.preventDefault();
                dropzone.classList.add('border-blue-500', 'bg-blue-50');
            });

            dropzone.addEventListener('dragleave', function () {
                dropzone.classList.remove('border-blue-500', 'bg-blue-50');
            });

            dropzone.addEventListener('drop', function (e) {
                e.preventDefault();
                dropzone.classList.remove('border-blue-500', 'bg-blue-50');
                if (fileInput.disabled) return; // max 1
                if (e.dataTransfer.files && e.dataTransfer.files[0]) {
                    fileInput.files = e.dataTransfer.files;
                    showPreview(e.dataTransfer.files[0]);
                }
            });

            // Remove image
            removeBtn.addEventListener('click', function () {
                img.src = '';
                placeholder.classList.remove('hidden');
                preview.classList.add('hidden');
                fileInput.value = '';
                fileInput.disabled = false;
                setOgImage('');
                // Add a hidden input to signal deletion on the server
                var delInput = document.getElementById('remove_featured_image');
                if (!delInput) {
                    delInput = document.createElement('input');
                    delInput.type = 'hidden';
                    delInput.name = 'remove_featured_image';
                    delInput.id = 'remove_featured_image';
                    delInput.value = '1';
                    dropzone.parentNode.appendChild(delInput);
                }
            });
        })();

        // ═══════════════════════════════════════════════════════════════
        // 6. SEO: Google Preview + Character Counters
        // ═══════════════════════════════════════════════════════════════
        (function () {
            var titleInput = document.getElementById('seo_meta_title');
            var descInput = document.getElementById('seo_meta_description');
            var postTitle = document.getElementById('title');
            var slugInput = document.getElementById('slug');
            var previewTitle = document.getElementById('preview-title');
            var previewUrl = document.getElementById('preview-url');
            var previewDesc = document.getElementById('preview-description');
            var previewMobileTitle = document.getElementById('preview-mobile-title');
            var previewMobileUrl = document.getElementById('preview-mobile-url');
            var previewMobileDesc = document.getElementById('preview-mobile-description');
            var breadcrumbSlug = document.getElementById('preview-breadcrumb-slug');
            var titleCounter = document.getElementById('meta-title-counter');
            var descCounter = document.getElementById('meta-desc-counter');

            if (!titleInput || !descInput || !titleCounter || !descCounter) return;

            var titleMax = 60;
            var descMax = 155;

            function updateSEO() {
                var t = titleInput.value.trim() || postTitle.value.trim() || '{{ __('Post Title') }}';
                var d = descInput.value.trim() || '{{ __('A brief description of the post for search results') }}';
                var s = slugInput.value.trim() || 'post-slug';

                previewTitle.textContent = t;
                previewMobileTitle.textContent = t;
                previewUrl.textContent = 'https://example.com/blog/' + s;
                previewMobileUrl.textContent = 'https://example.com/blog/' + s;
                previewDesc.textContent = d;
                previewMobileDesc.textContent = d;
                if (breadcrumbSlug) breadcrumbSlug.textContent = 'blog';

                // Mirror the same values into the social-share preview so the
                // WhatsApp/Facebook cards stay in sync while typing.
                updateOgText(t, d, s);

                // Character counters
                var tl = titleInput.value.length;
                titleCounter.textContent = tl + '/' + titleMax;
                titleCounter.className = 'text-xs ' + (tl > titleMax ? 'text-red-600 font-semibold' : tl > 50 ? 'text-orange-500' : 'text-gray-500');

                var dl = descInput.value.length;
                descCounter.textContent = dl + '/' + descMax;
                descCounter.className = 'text-xs ' + (dl > descMax ? 'text-red-600 font-semibold' : dl > 130 ? 'text-orange-500' : 'text-gray-500');
            }

            titleInput.addEventListener('input', updateSEO);
            descInput.addEventListener('input', updateSEO);
            if (postTitle) postTitle.addEventListener('input', updateSEO);
            if (slugInput) slugInput.addEventListener('input', updateSEO);

            // Initial update
            updateSEO();
        })();

        // ═══════════════════════════════════════════════════════════════
        // 7. PREVIEW TABS: Desktop / Mobile toggle
        // ═══════════════════════════════════════════════════════════════
        (function () {
            document.querySelectorAll('.preview-tab-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var target = this.getAttribute('data-preview');
                    document.querySelectorAll('.preview-tab-btn').forEach(function (b) {
                        b.className = 'preview-tab-btn px-3 py-1 text-xs font-medium rounded bg-gray-200 text-gray-700 hover:bg-gray-300';
                    });
                    this.className = 'preview-tab-btn px-3 py-1 text-xs font-medium rounded bg-blue-600 text-white';

                    document.getElementById('preview-desktop').classList.toggle('hidden', target !== 'desktop');
                    document.getElementById('preview-mobile').classList.toggle('hidden', target !== 'mobile');
                });
            });
        })();

        // ═══════════════════════════════════════════════════════════════
        // 8. INLINE CATEGORY CREATION
        // ═══════════════════════════════════════════════════════════════
        (function () {
            var addBtn = document.getElementById('add-category-btn');
            var modal = document.getElementById('add-category-modal');
            var closeBtn = document.getElementById('close-category-modal');
            var cancelBtn = document.getElementById('cancel-category-modal');
            var saveBtn = document.getElementById('save-category-btn');
            var nameInput = document.getElementById('new-category-name');
            var slugInput = document.getElementById('new-category-slug');
            var descInput = document.getElementById('new-category-desc');
            var errorEl = document.getElementById('category-modal-error');
            var categorySelect = document.getElementById('category_id');

            if (!addBtn || !modal) return;

            function openModal() {
                modal.classList.remove('hidden');
                nameInput.value = '';
                slugInput.value = '';
                descInput.value = '';
                errorEl.classList.add('hidden');
                nameInput.focus();
            }

            function closeModal() {
                modal.classList.add('hidden');
            }

            addBtn.addEventListener('click', openModal);
            closeBtn.addEventListener('click', closeModal);
            cancelBtn.addEventListener('click', closeModal);
            modal.addEventListener('click', function (e) {
                if (e.target === modal) closeModal();
            });

            // Auto-generate slug from name
            if (nameInput && slugInput) {
                nameInput.addEventListener('input', function () {
                    slugInput.value = slugify(this.value);
                });
            }

            saveBtn.addEventListener('click', function () {
                var name = nameInput.value.trim();
                if (!name) {
                    errorEl.textContent = '{{ __('Category name is required.') }}';
                    errorEl.classList.remove('hidden');
                    return;
                }

                saveBtn.disabled = true;
                saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> {{ __('Saving...') }}';

                fetch('{{ route("admin.categories.store-ajax") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        name: name,
                        slug: slugInput.value.trim() || undefined,
                        description: descInput.value.trim() || undefined
                    })
                })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.success) {
                        // Add the new option to the select
                        var opt = document.createElement('option');
                        opt.value = data.id;
                        opt.textContent = data.name;
                        opt.selected = true;
                        categorySelect.appendChild(opt);
                        closeModal();
                    } else {
                        errorEl.textContent = data.message || '{{ __('Failed to create category.') }}';
                        errorEl.classList.remove('hidden');
                    }
                })
                .catch(function () {
                    errorEl.textContent = '{{ __('Network error. Please try again.') }}';
                    errorEl.classList.remove('hidden');
                })
                .finally(function () {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<i class="fa-solid fa-plus mr-1"></i> {{ __('Add Category') }}';
                });
            });
        })();

        // ═══════════════════════════════════════════════════════════════
        // 9. STICKY BAR: adjust margin-left based on sidebar state
        // ═══════════════════════════════════════════════════════════════
        (function () {
            var stickyBar = document.getElementById('sticky-bar');
            if (!stickyBar) return;

            function updateStickyMargin() {
                // On mobile (< lg) the sidebar is off-canvas (fixed, no layout space),
                // so the bar should span the full viewport. Only offset on lg+.
                if (window.innerWidth < 1024) {
                    stickyBar.style.marginLeft = '0';
                    return;
                }
                var sidebar = document.querySelector('aside');
                if (!sidebar) return;
                var collapsed = sidebar.classList.contains('lg:w-20');
                stickyBar.style.marginLeft = collapsed ? '5rem' : '16rem';
            }

            // Observe sidebar collapse toggle
            var observer = new MutationObserver(updateStickyMargin);
            var sidebar = document.querySelector('aside');
            if (sidebar) {
                observer.observe(sidebar, { attributes: true, attributeFilter: ['class'] });
            }

            // Also update on resize
            window.addEventListener('resize', updateStickyMargin);
            updateStickyMargin();
        })();
    }

    // Turbo-compatible init: DOMContentLoaded for first load, re-run on Turbo body swap
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPostForm);
    } else {
        initPostForm();
    }
    document.addEventListener('turbo:load', initPostForm);
</script>
@endpush
