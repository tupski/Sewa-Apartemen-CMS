<?php

namespace App\Http\Controllers;

use App\Http\Requests\BlockRequest;
use App\Models\Block;
use App\Models\Page;
use App\Services\SafeHtmlService;
use Illuminate\Http\Request;

class BlockController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of blocks.
     */
    public function index(Request $request)
    {
        $query = Block::query();

        // Filter by type
        if ($request->has('type') && $request->type) {
            $query->byType($request->type);
        }

        // Filter by area
        if ($request->has('area') && $request->area) {
            $query->byArea($request->area);
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Search by name
        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        $blocks = $query->ordered()->paginate(15);

        return view('admin.blocks.index', compact('blocks'));
    }

    /**
     * Show the form for creating a new block.
     */
    public function create()
    {
        $pages = Page::orderBy('title')->get();

        return view('admin.blocks.create', compact('pages'));
    }

    /**
     * Store a newly created block.
     */
    public function store(BlockRequest $request)
    {
        try {
            $data = $request->validated();
            // FIND-005: sanitize legacy string content (array content is structured data)
            if (is_string($data['content'] ?? null)) {
                $data['content'] = SafeHtmlService::sanitize($data['content']);
            }

            $block = Block::create($data);

            return redirect()
                ->route('admin.blocks.index')
                ->with('success', 'Block created successfully.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to create block: '.$e->getMessage());
        }
    }

    /**
     * Display the specified block.
     */
    public function show(Block $block)
    {
        return view('admin.blocks.show', compact('block'));
    }

    /**
     * Show the form for editing the specified block.
     */
    public function edit(Block $block)
    {
        $pages = Page::orderBy('title')->get();

        return view('admin.blocks.edit', compact('block', 'pages'));
    }

    /**
     * Update the specified block.
     */
    public function update(BlockRequest $request, Block $block)
    {
        try {
            $data = $request->validated();
            // FIND-005: sanitize legacy string content (array content is structured data)
            if (is_string($data['content'] ?? null)) {
                $data['content'] = SafeHtmlService::sanitize($data['content']);
            }

            $block->update($data);

            return redirect()
                ->route('admin.blocks.index')
                ->with('success', 'Blok berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to update block: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified block.
     */
    public function destroy(Block $block)
    {
        try {
            $block->delete();

            return redirect()
                ->route('admin.blocks.index')
                ->with('success', 'Block deleted successfully.');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to delete block: '.$e->getMessage());
        }
    }

    /**
     * Update block ordering.
     */
    public function reorder(Request $request)
    {
        try {
            $validated = $request->validate([
                'blocks' => 'required|array',
                'blocks.*.id' => 'required|integer|exists:blocks,id',
                'blocks.*.order' => 'required|integer|min:0',
            ]);

            foreach ($validated['blocks'] as $blockData) {
                Block::where('id', $blockData['id'])
                    ->update(['order' => $blockData['order']]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Blocks reordered successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder blocks: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update block status.
     */
    public function updateStatus(Request $request, Block $block)
    {
        try {
            $validated = $request->validate([
                'status' => 'required|string|in:active,inactive',
            ]);

            $block->update(['status' => $validated['status']]);

            return response()->json([
                'success' => true,
                'message' => 'Status Block berhasil diperbarui.',
                'status' => $block->status,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update block status: '.$e->getMessage(),
            ], 500);
        }
    }
}
