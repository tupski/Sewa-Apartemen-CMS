<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Category;
use App\Models\Tag;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Cache;

class BlogController extends Controller
{
    public function index()
    {
        $posts = Post::with(['category', 'tags', 'author'])
                    ->published()
                    ->orderBy('published_at', 'desc')
                    ->paginate(12);

        $sidebarData = $this->getSidebarData();

        return view('blog.index', array_merge(compact('posts'), $sidebarData));
    }

    public function show(string $slug)
    {
        $post = Post::with(['category', 'tags', 'author', 'seo'])
                    ->where('slug', $slug)
                    ->firstOrFail();

        if ($post->status !== 'published') {
            abort(404);
        }

        $sidebarData = $this->getSidebarData();

        $relatedPosts = Post::published()
                            ->where('category_id', $post->category_id)
                            ->where('id', '!=', $post->id)
                            ->latest('published_at')
                            ->limit(3)
                            ->get();

        return view('blog.show', array_merge(compact('post', 'relatedPosts'), $sidebarData));
    }

    public function category(string $slug)
    {
        $category = Category::where('slug', $slug)->firstOrFail();

        $posts = Post::with(['category', 'tags', 'author'])
                    ->published()
                    ->where('category_id', $category->id)
                    ->orderBy('published_at', 'desc')
                    ->paginate(12);

        $sidebarData = $this->getSidebarData();

        return view('blog.index', array_merge(compact('posts', 'category'), $sidebarData));
    }

    public function tag(string $slug)
    {
        $tag = Tag::where('slug', $slug)->firstOrFail();

        $posts = Post::with(['category', 'tags', 'author'])
                    ->published()
                    ->whereHas('tags', fn($q) => $q->where('slug', $slug))
                    ->orderBy('published_at', 'desc')
                    ->paginate(12);

        $sidebarData = $this->getSidebarData();

        return view('blog.index', array_merge(compact('posts', 'tag'), $sidebarData));
    }

    protected function getSidebarData(): array
    {
        return Cache::remember('blog_sidebar', now()->addHour(), function () {
            return [
                'recentPosts' => Post::published()->latest('published_at')->limit(5)->get(),
                'categories' => Category::withCount(['posts' => fn($q) => $q->published()])->orderBy('name')->get(),
                'tags' => Tag::withCount(['posts' => fn($q) => $q->published()])->orderBy('name')->get(),
            ];
        });
    }
}
