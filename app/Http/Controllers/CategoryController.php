<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $categories = Category::withCount('posts')->orderBy('name')->paginate(15);

        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:categories,slug',
            'description' => 'nullable|string',
        ]);

        try {
            $data = $validated;
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            Category::create($data);

            return redirect()
                ->route('admin.categories.index')
                ->with('success', 'Category created successfully.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to create category: ' . $e->getMessage());
        }
    }

    /**
     * AJAX quick-create of a category from the post create/edit form.
     * Accepts a name (optionally a slug/description), persists it and returns JSON
     * so the front-end can insert the new option into the category dropdown.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeAjax(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'slug'        => 'nullable|string|max:255|unique:categories,slug',
            'description' => 'nullable|string|max:255',
        ]);

        try {
            $data = $validated;
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
                // Ensure uniqueness by appending a numeric suffix if needed.
                $base = $data['slug'];
                $i = 1;
                while (\App\Models\Category::where('slug', $data['slug'])->exists()) {
                    $data['slug'] = $base . '-' . $i++;
                }
            }

            $category = Category::create($data);

            return response()->json([
                'success' => true,
                'id'      => $category->id,
                'name'    => $category->name,
                'slug'    => $category->slug,
                'message' => 'Category created successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create category: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function edit(Category $category)
    {
        return view('admin.categories.edit', compact('category'));
    }

    /**
     * Display the specified category (redirect to edit form).
     */
    public function show(Category $category)
    {
        return redirect()->route('admin.categories.edit', $category);
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:categories,slug,' . $category->id,
            'description' => 'nullable|string',
        ]);

        try {
            $data = $validated;
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            $category->update($data);

            return redirect()
                ->route('admin.categories.index')
                ->with('success', 'Category updated successfully.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to update category: ' . $e->getMessage());
        }
    }

    public function destroy(Category $category)
    {
        try {
            $category->delete();

            return redirect()
                ->route('admin.categories.index')
                ->with('success', 'Category deleted successfully.');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to delete category: ' . $e->getMessage());
        }
    }
}
