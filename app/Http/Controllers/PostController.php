<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
            $query->where('title', 'like', '%' . $request->search . '%');
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

        return view('admin.posts.create', compact('categories', 'tags'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'          => 'required|string|max:255',
            'slug'           => 'nullable|string|max:255|unique:posts,slug',
            'content'        => 'required|string',
            'excerpt'        => 'nullable|string',
            'status'         => 'required|in:draft,published',
            'category_id'    => 'nullable|exists:categories,id',
            'featured_image' => 'nullable|image|max:2048',
            'tags'           => 'nullable|string',
            'seo'            => 'nullable|array',
            // BUG-024 FIX: Validasi field SEO agar tidak ada string tak terbatas
            'seo.meta_title'       => 'nullable|string|max:255',
            'seo.meta_description' => 'nullable|string|max:320',
            'seo.canonical_url'    => 'nullable|url|max:2048',
        ]);

        try {
            $data = $validated;
            $data['user_id'] = auth()->id();
            // FIND-005: sanitize rich content before persistence
            $data['content'] = \App\Services\SafeHtmlService::sanitize($data['content'] ?? null);

            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['title']);
            }

            if ($request->hasFile('featured_image')) {
                $result = upload_file($request->file('featured_image'), [
                    'base_folder'   => 'Blog',
                    'sub_folders'   => [$data['title'] ?? 'post'],
                    'name_prefix'   => 'Blog',
                    'name_category' => $data['title'] ?? 'post',
                ]);
                $data['featured_image'] = $result['path'];
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
            return back()
                ->withInput()
                ->with('error', 'Failed to create post: ' . $e->getMessage());
        }
    }

    public function edit(Post $post)
    {
        $categories = Category::orderBy('name')->get();
        $tags = Tag::orderBy('name')->get();
        $postTags = $post->tags->pluck('name')->implode(', ');

        return view('admin.posts.edit', compact('post', 'categories', 'tags', 'postTags'));
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
            'title'          => 'required|string|max:255',
            'slug'           => 'nullable|string|max:255|unique:posts,slug,' . $post->id,
            'content'        => 'required|string',
            'excerpt'        => 'nullable|string',
            'status'         => 'required|in:draft,published',
            'category_id'    => 'nullable|exists:categories,id',
            'featured_image' => 'nullable|image|max:2048',
            'tags'           => 'nullable|string',
            'seo'            => 'nullable|array',
            // BUG-024 FIX: Konsisten dengan store() — validasi field SEO pada update juga
            'seo.meta_title'       => 'nullable|string|max:255',
            'seo.meta_description' => 'nullable|string|max:320',
            'seo.canonical_url'    => 'nullable|url|max:2048',
        ]);

        try {
            // FIND-005: sanitize rich content before persistence
            $validated['content'] = \App\Services\SafeHtmlService::sanitize($validated['content'] ?? null);
            $data = $validated;

            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['title']);
            }

            if ($request->hasFile('featured_image')) {
                $result = upload_file($request->file('featured_image'), [
                    'base_folder'   => 'Blog',
                    'sub_folders'   => [$data['title'] ?? 'post'],
                    'name_prefix'   => 'Blog',
                    'name_category' => $data['title'] ?? 'post',
                ]);
                $data['featured_image'] = $result['path'];
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
                ->with('success', 'Post updated successfully.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to update post: ' . $e->getMessage());
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
                ->with('error', 'Failed to delete post: ' . $e->getMessage());
        }
    }

    protected function syncTags(Post $post, string $tagString): void
    {
        $tagNames = array_filter(array_map('trim', explode(',', $tagString)));
        $tagIds = [];

        foreach ($tagNames as $name) {
            if (empty($name)) continue;
            $tag = Tag::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            );
            $tagIds[] = $tag->id;
        }

        $post->tags()->sync($tagIds);
    }
}
