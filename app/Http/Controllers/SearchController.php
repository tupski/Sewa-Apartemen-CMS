<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Post;
use App\Models\Property;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Public JSON suggestions for the search autocomplete.
     * Returns title + url + type for published posts, properties and pages.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function suggest(Request $request)
    {
        // ponytail: guard API di controller (bukan FormRequest) karena endpoint
        // read-only kecil; throttling ditangani middleware route.
        // Bilamana cakupan luas (excerpt/content, highlight) atau skala besar
        // dibutuhkan, migrasi ke search full-text / Laravel Scout.
        $q = trim((string) $request->query('q'));

        if (mb_strlen($q) < 2) {
            return response()->json(['data' => []]);
        }

        // Escape LIKE wildcards agar query user tidak melebar tak terkendali.
        $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';

        $posts = Post::published()
            ->where('title', 'like', $like)
            ->orderByDesc('published_at')
            ->limit(4)
            ->get(['title', 'slug'])
            ->map(fn (Post $p) => [
                'title' => $p->title,
                'url' => route('blog.show', $p->slug),
                'type' => 'post',
            ]);

        $properties = Property::published()
            ->where('name', 'like', $like)
            ->orderBy('name')
            ->limit(4)
            ->get(['name', 'slug'])
            ->map(fn (Property $p) => [
                'title' => $p->name,
                'url' => route('properties.public.show', $p->slug),
                'type' => 'property',
            ]);

        // Halaman homepage dieksklusi: `is_homepage` merujuk ke `/`, bukan slug.
        $pages = Page::published()
            ->where('is_homepage', false)
            ->where('title', 'like', $like)
            ->orderBy('title')
            ->limit(4)
            ->get(['title', 'slug'])
            ->map(fn (Page $p) => [
                'title' => $p->title,
                'url' => route('pages.show', $p->slug),
                'type' => 'page',
            ]);

        $results = $posts->concat($properties)->concat($pages)->take(8)->values();

        return response()->json(['data' => $results]);
    }
}
