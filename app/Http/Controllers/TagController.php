<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TagController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $tags = Tag::withCount('posts')->orderBy('name')->paginate(20);

        return view('admin.tags.index', compact('tags'));
    }

    public function create()
    {
        return view('admin.tags.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:tags,slug',
        ]);

        try {
            $data = $validated;
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            Tag::create($data);

            return redirect()
                ->route('admin.tags.index')
                ->with('success', 'Tag created successfully.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to create tag: '.$e->getMessage());
        }
    }

    public function edit(Tag $tag)
    {
        return view('admin.tags.edit', compact('tag'));
    }

    /**
     * Display the specified tag (redirect to edit form).
     */
    public function show(Tag $tag)
    {
        return redirect()->route('admin.tags.edit', $tag);
    }

    public function update(Request $request, Tag $tag)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:tags,slug,'.$tag->id,
        ]);

        try {
            $data = $validated;
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            $tag->update($data);

            return redirect()
                ->route('admin.tags.index')
                ->with('success', 'Tag berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Gagal memperbarui Tag: '.$e->getMessage());
        }
    }

    public function destroy(Tag $tag)
    {
        try {
            $tag->delete();

            return redirect()
                ->route('admin.tags.index')
                ->with('success', 'Tag berhasil dihapus.');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Gagal menghapus Tag: '.$e->getMessage());
        }
    }
}
