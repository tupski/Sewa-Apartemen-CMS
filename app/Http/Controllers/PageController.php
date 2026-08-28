<?php

namespace App\Http\Controllers;

use App\Http\Requests\PageRequest;
use App\Models\Block;
use App\Models\Page;
use App\Services\SeoService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PageController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of pages.
     */
    public function index(Request $request)
    {
        $query = Page::with('user');

        // Search by title
        if ($request->has('search') && $request->search) {
            $query->where('title', 'like', '%'.$request->search.'%');
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $pages = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('admin.pages.index', compact('pages'));
    }

    /**
     * Show the form for creating a new page.
     */
    public function create()
    {
        $pages = Page::orderBy('title')->get();

        return view('admin.pages.create', compact('pages'));
    }

    /**
     * Store a newly created page.
     */
    public function store(PageRequest $request)
    {
        try {
            $data = $request->validated();

            // Auto-generate slug if empty
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['title']);
            }

            // Set author
            $data['user_id'] = auth()->id();

            $page = Page::create($data);

            // Save SEO metadata
            $page->seo()->updateOrCreate([], [
                'meta_title' => $request->input('seo.meta_title'),
                'meta_description' => $request->input('seo.meta_description'),
                'open_graph' => $request->input('seo.open_graph'),
                'twitter' => $request->input('seo.twitter'),
                'canonical_url' => $request->input('seo.canonical_url'),
                'index_status' => $request->boolean('seo.index_status', true),
            ]);

            return redirect()
                ->route('admin.pages.index')
                ->with('success', 'Page created successfully.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to create page: '.$e->getMessage());
        }
    }

    /**
     * Public detail page for a published CMS page.
     */
    public function publicShow(Page $page)
    {
        abort_unless($page->status === 'published', 404);

        $blocks = Block::where('status', 'active')->get()
            ->filter(fn ($block) => $block->appearsOnPage($page->id))
            ->groupBy('area');

        // Build SEO from the page's own metadata (falls back to title/content).
        // Title suffixing (" - {Site Name}") is applied centrally by SeoService.
        $seo = SeoService::metaTagsArray($page);

        return view('pages.show', compact('page', 'blocks', 'seo'));
    }

    /**
     * Display the specified page (redirect to edit form).
     */
    public function show(Page $page)
    {
        $page->load('user');

        return view('admin.pages.show', compact('page'));
    }

    /**
     * Show the form for editing the specified page.
     */
    public function edit(Page $page)
    {
        $pages = Page::where('id', '!=', $page->id)->orderBy('title')->get();

        return view('admin.pages.edit', compact('page', 'pages'));
    }

    /**
     * Update the specified page.
     */
    public function update(PageRequest $request, Page $page)
    {
        try {
            $data = $request->validated();

            // Auto-generate slug if empty
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['title']);
            }

            $page->update($data);

            // Save SEO metadata
            $page->seo()->updateOrCreate([], [
                'meta_title' => $request->input('seo.meta_title'),
                'meta_description' => $request->input('seo.meta_description'),
                'open_graph' => $request->input('seo.open_graph'),
                'twitter' => $request->input('seo.twitter'),
                'canonical_url' => $request->input('seo.canonical_url'),
                'index_status' => $request->boolean('seo.index_status', true),
            ]);

            return redirect()
                ->route('admin.pages.index')
                ->with('success', 'Halaman berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to update page: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified page.
     */
    public function destroy(Page $page)
    {
        try {
            $page->delete();

            return redirect()
                ->route('admin.pages.index')
                ->with('success', 'Halaman berhasil dihapus.');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Gagal menghapus halaman: '.$e->getMessage());
        }
    }

    /**
     * Update page status.
     */
    public function updateStatus(Request $request, Page $page)
    {
        try {
            $validated = $request->validate([
                'status' => 'required|string|in:draft,published,scheduled',
            ]);

            $page->update(['status' => $validated['status']]);

            return response()->json([
                'success' => true,
                'message' => 'Status halaman berhasil diperbarui.',
                'status' => $page->status,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui status halaman: '.$e->getMessage(),
            ], 500);
        }
    }
}
