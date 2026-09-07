<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Media;
use App\Models\Post;
use App\Models\Tag;
use App\Services\SafeHtmlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PostController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $query = Post::with(['category', 'author']);

        if ($request->has('search') && $request->search) {
            $query->where('title', 'like', '%'.$request->search.'%');
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('category_id') && $request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        $posts = $query->orderBy('created_at', 'desc')->paginate(15);
        $categories = Category::orderBy('name')->get();

        return view('admin.posts.index', compact('posts', 'categories'));
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();
        $tags = Tag::orderBy('name')->get();
        $featuredMedia = null;
        // Pillar candidates: every other post (drafts included — editorial
        // preparation); the select also allows choosing none.
        $pillarPosts = Post::orderBy('title')->get(['id', 'title']);

        return view('admin.posts.create', compact('categories', 'tags', 'featuredMedia', 'pillarPosts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            // Slug is OPTIONAL: auto-generated from the title when empty.
            'slug' => 'nullable|string|max:255|unique:posts,slug',
            'content' => 'required|string',
            'excerpt' => 'nullable|string',
            // Status now comes from a hidden field (draft/published) — kept
            // nullable so legacy clients/tests that omit it still work (defaults to draft).
            'status' => 'nullable|in:draft,published',
            'category_id' => 'nullable|exists:categories,id',
            // Editorial pillar link: optional, must be an existing post.
            // (Self-reference is impossible at creation — the post has no id yet.)
            'pillar_post_id' => 'nullable|integer|exists:posts,id|not_in:0',
            // Client enforces images only + 5MB, keep server-side rules as the trust boundary.
            'featured_image' => 'nullable|image|mimes:jpeg,png,webp,gif|max:5120',
            'featured_image_media_id' => 'nullable|integer|exists:media,id',
            'remove_featured_image' => 'nullable|boolean',
            'tags' => 'nullable|string',
            'seo' => 'nullable|array',
            // BUG-024 FIX: Validasi field SEO agar tidak ada string tak terbatas
            'seo.meta_title' => 'nullable|string|max:255',
            'seo.meta_description' => 'nullable|string|max:320',
            'seo.canonical_url' => 'nullable|url|max:2048',
        ]);

        try {
            $featuredMedia = $this->resolveFeaturedMedia($validated['featured_image_media_id'] ?? null);

            $data = $validated;
            unset($data['featured_image_media_id'], $data['remove_featured_image']);
            // Empty select submits '' — normalize to null for the nullable FK column.
            $data['pillar_post_id'] = filled($data['pillar_post_id'] ?? null) ? (int) $data['pillar_post_id'] : null;
            $data['user_id'] = auth()->id();
            // Status is optional in the request; default to draft.
            $data['status'] = $data['status'] ?? 'draft';
            // FIND-005: sanitize rich content before persistence
            $data['content'] = SafeHtmlService::sanitize($data['content'] ?? null);

            // Slug is optional — generate from title with a uniqueness suffix.
            if (empty($data['slug'])) {
                $data['slug'] = Post::uniqueSlug($data['title']);
            }

            if ($request->hasFile('featured_image')) {
                $result = upload_file($request->file('featured_image'), [
                    'base_folder' => 'Blog',
                    'sub_folders' => [$data['title'] ?? 'post'],
                    'name_prefix' => 'Blog',
                    'name_category' => $data['title'] ?? 'post',
                ]);
                $data['featured_image'] = $result['path'];
            } elseif ($featuredMedia) {
                $data['featured_image'] = $this->mediaPath($featuredMedia);
            }

            $post = Post::create($data);

            // Sync tags
            $this->syncTags($post, $request->input('tags', ''));

            // Save SEO metadata
            $post->seo()->updateOrCreate([], [
                'meta_title' => $request->input('seo.meta_title'),
                'meta_description' => $request->input('seo.meta_description'),
                'open_graph' => $request->input('seo.open_graph'),
                'twitter' => $request->input('seo.twitter'),
                'canonical_url' => $request->input('seo.canonical_url'),
                'index_status' => $request->boolean('seo.index_status', true),
            ]);

            return redirect()
                ->route('admin.posts.index')
                ->with('success', 'Post created successfully.');
        } catch (\Exception $e) {
            if ($e instanceof ValidationException) {
                throw $e;
            }

            return back()
                ->withInput()
                ->with('error', 'Failed to create post: '.$e->getMessage());
        }
    }

    public function edit(Post $post)
    {
        $categories = Category::orderBy('name')->get();
        $tags = Tag::orderBy('name')->get();
        $postTags = $post->tags->pluck('name')->implode(', ');
        $featuredMedia = $this->mediaForPath($post->featured_image);
        // Pillar candidates exclude the current post (self-reference).
        $pillarPosts = Post::where('id', '!=', $post->id)->orderBy('title')->get(['id', 'title']);

        return view('admin.posts.edit', compact('post', 'categories', 'tags', 'postTags', 'featuredMedia', 'pillarPosts'));
    }

    /**
     * Display the specified post (redirect to edit form).
     */
    public function show(Post $post)
    {
        return redirect()->route('admin.posts.edit', $post);
    }

    public function update(Request $request, Post $post)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            // Slug is OPTIONAL: auto-generated from the title when empty.
            'slug' => 'nullable|string|max:255|unique:posts,slug,'.$post->id,
            'content' => 'required|string',
            'excerpt' => 'nullable|string',
            'status' => 'nullable|in:draft,published',
            'category_id' => 'nullable|exists:categories,id',
            // Editorial pillar link: optional, must be an existing post,
            // never the post itself (self-reference).
            'pillar_post_id' => 'nullable|integer|exists:posts,id|not_in:0,'.$post->id,
            'featured_image' => 'nullable|image|mimes:jpeg,png,webp,gif|max:5120',
            'featured_image_media_id' => 'nullable|integer|exists:media,id',
            'remove_featured_image' => 'nullable|boolean',
            'tags' => 'nullable|string',
            'seo' => 'nullable|array',
            // BUG-024 FIX: Konsisten dengan store() — validasi field SEO pada update juga
            'seo.meta_title' => 'nullable|string|max:255',
            'seo.meta_description' => 'nullable|string|max:320',
            'seo.canonical_url' => 'nullable|url|max:2048',
        ]);

        try {
            $featuredMedia = $this->resolveFeaturedMedia($validated['featured_image_media_id'] ?? null);

            // FIND-005: sanitize rich content before persistence
            $validated['content'] = SafeHtmlService::sanitize($validated['content'] ?? null);
            $data = $validated;
            unset($data['featured_image_media_id'], $data['remove_featured_image']);
            // Empty select submits '' — normalize to null for the nullable FK column.
            $data['pillar_post_id'] = filled($data['pillar_post_id'] ?? null) ? (int) $data['pillar_post_id'] : null;

            // Status is optional in the request; default to draft.
            $data['status'] = $data['status'] ?? 'draft';

            // Slug is optional — generate from title with a uniqueness suffix.
            if (empty($data['slug'])) {
                $data['slug'] = Post::uniqueSlug($data['title'], $post->id);
            }

            // An empty file input submits `featured_image = null`, so only touch
            // the column when the user actually uploaded or explicitly removed it.
            if ($request->hasFile('featured_image')) {
                $result = upload_file($request->file('featured_image'), [
                    'base_folder' => 'Blog',
                    'sub_folders' => [$data['title'] ?? 'post'],
                    'name_prefix' => 'Blog',
                    'name_category' => $data['title'] ?? 'post',
                ]);
                $this->deleteLegacyFeaturedImage($post->featured_image);
                $data['featured_image'] = $result['path'];
            } elseif ($featuredMedia) {
                $this->deleteLegacyFeaturedImage($post->featured_image);
                $data['featured_image'] = $this->mediaPath($featuredMedia);
            } elseif ($request->boolean('remove_featured_image')) {
                // The user removed the featured image in the form (× button).
                $this->deleteLegacyFeaturedImage($post->featured_image);
                $data['featured_image'] = null;
            } else {
                unset($data['featured_image']);
            }

            $post->update($data);

            // Sync tags
            $this->syncTags($post, $request->input('tags', ''));

            // Save SEO metadata
            $post->seo()->updateOrCreate([], [
                'meta_title' => $request->input('seo.meta_title'),
                'meta_description' => $request->input('seo.meta_description'),
                'open_graph' => $request->input('seo.open_graph'),
                'twitter' => $request->input('seo.twitter'),
                'canonical_url' => $request->input('seo.canonical_url'),
                'index_status' => $request->boolean('seo.index_status', true),
            ]);

            return redirect()
                ->route('admin.posts.index')
                ->with('success', 'Postingan berhasil diperbarui.');
        } catch (\Exception $e) {
            if ($e instanceof ValidationException) {
                throw $e;
            }

            return back()
                ->withInput()
                ->with('error', 'Failed to update post: '.$e->getMessage());
        }
    }

    public function destroy(Post $post)
    {
        try {
            $post->delete();

            return redirect()
                ->route('admin.posts.index')
                ->with('success', 'Post deleted successfully.');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to delete post: '.$e->getMessage());
        }
    }

    /**
     * AJAX image upload used by the Quill WYSIWYG editor.
     * Stores the file via the shared upload_file() helper and returns the
     * public /storage/... URL so it can be inserted into the editor content.
     *
     * @return JsonResponse
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,webp,gif|max:5120',
        ]);

        try {
            $result = upload_file($request->file('image'), [
                'base_folder' => 'Blog',
                'sub_folders' => ['content'],
                'name_prefix' => 'Blog',
                'name_category' => 'content',
            ]);

            return response()->json([
                'success' => true,
                'url' => Storage::url($result['path']),
                'path' => $result['path'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Image upload failed: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Resolve a selected media-library item to an image owned by the application.
     */
    protected function resolveFeaturedMedia(?int $mediaId): ?Media
    {
        if ($mediaId === null) {
            return null;
        }

        $media = Media::query()
            ->whereKey($mediaId)
            ->where('type', 'image')
            ->whereIn('mime_type', ['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
            ->first();

        if (! $media) {
            throw ValidationException::withMessages([
                'featured_image_media_id' => 'The selected featured image is invalid.',
            ]);
        }

        return $media;
    }

    protected function mediaPath(Media $media): string
    {
        return trim($media->directory.'/'.$media->filename, '/');
    }

    protected function mediaForPath(?string $path): ?Media
    {
        if (! $path) {
            return null;
        }

        $normalizedPath = trim(str_replace('\\', '/', $path), '/');
        $directory = trim(dirname($normalizedPath), '.\/');
        $query = Media::query()->where('filename', basename($normalizedPath));

        if ($directory === '') {
            $query->whereNull('directory');
        } else {
            $query->where('directory', $directory);
        }

        return $query
            ->where('type', 'image')
            ->whereIn('mime_type', ['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
            ->first();
    }

    protected function deleteLegacyFeaturedImage(?string $path): void
    {
        if (! $path || $this->mediaForPath($path)) {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    protected function syncTags(Post $post, string $tagString): void
    {
        $tagNames = array_filter(array_map('trim', explode(',', $tagString)));
        $tagIds = [];

        foreach ($tagNames as $name) {
            if (empty($name)) {
                continue;
            }
            $tag = Tag::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            );
            $tagIds[] = $tag->id;
        }

        $post->tags()->sync($tagIds);
    }
}
