<?php

namespace App\Http\Controllers;

use App\Http\Requests\AmenityRequest;
use App\Models\Amenity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AmenityController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of amenities.
     */
    public function index(Request $request)
    {
        $query = Amenity::query();

        // Search by name
        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        // Filter by category
        if ($request->has('category') && $request->category) {
            $query->where('category', $request->category);
        }

        // Filter by active status
        if ($request->has('is_active') && $request->is_active !== '') {
            $query->where('is_active', $request->is_active);
        }

        $amenities = $query->orderBy('category')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.amenities.index', compact('amenities'));
    }

    /**
     * Paginated amenity options (JSON) for the property-form picker.
     *
     * Returns a small fixed page (20) of active amenities with only the
     * columns the picker renders, so the browser never downloads the full
     * dataset. `has_more` tells the frontend whether another page exists;
     * existence is detected by over-fetching one row instead of COUNT(*).
     */
    public function options(Request $request)
    {
        // Inline validation with a JSON response: like GeocodeController, this
        // endpoint is fetch()-only and `validate()` would redirect-with-errors
        // for requests that are not flagged AJAX.
        $validator = Validator::make($request->all(), [
            'search' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter pencarian tidak valid.',
            ], 422);
        }

        $validated = $validator->validated();

        $perPage = 20;
        $page = max(1, (int) ($validated['page'] ?? 1));

        $query = Amenity::query()->where('is_active', true);

        if (! empty($validated['search'])) {
            // Escape LIKE wildcards so a literal '%' searches for '%'.
            // Case-insensitivity comes from the DB collation
            // (utf8mb4_unicode_ci on MySQL; LIKE is ASCII-case-insensitive on SQLite).
            $query->where('name', 'like', '%'.addcslashes($validated['search'], '%_\\').'%');
        }

        $rows = $query->orderBy('category')
            ->orderBy('name')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage + 1)
            ->get(['id', 'name', 'icon', 'category']);

        $hasMore = $rows->count() > $perPage;

        return response()->json([
            'success' => true,
            'data' => $rows->take($perPage)->map(fn (Amenity $a): array => [
                'id' => $a->id,
                'name' => $a->name,
                'icon' => $a->icon_class,
                'category' => $a->category,
            ])->values(),
            'page' => $page,
            'has_more' => $hasMore,
        ]);
    }

    /**
     * Show the form for creating a new amenity.
     */
    public function create()
    {
        return view('admin.amenities.create');
    }

    /**
     * Store a newly created amenity.
     */
    public function store(AmenityRequest $request)
    {
        try {
            $data = $request->validated();

            // Auto-generate slug if empty
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            // Set is_active default
            if (! isset($data['is_active'])) {
                $data['is_active'] = true;
            }

            Amenity::create($data);

            return redirect()
                ->route('admin.amenities.index')
                ->with('success', 'Amenity created successfully.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to create amenity: '.$e->getMessage());
        }
    }

    /**
     * Display the specified amenity (redirect to edit form).
     */
    public function show(Amenity $amenity)
    {
        return redirect()->route('admin.amenities.edit', $amenity);
    }

    /**
     * Show the form for editing the specified amenity.
     */
    public function edit(Amenity $amenity)
    {
        return view('admin.amenities.edit', compact('amenity'));
    }

    /**
     * Update the specified amenity.
     */
    public function update(AmenityRequest $request, Amenity $amenity)
    {
        try {
            $data = $request->validated();

            // Auto-generate slug if empty
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            // Set is_active default
            if (! isset($data['is_active'])) {
                $data['is_active'] = false;
            }

            $amenity->update($data);

            return redirect()
                ->route('admin.amenities.index')
                ->with('success', 'Amenitas berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to update amenity: '.$e->getMessage());
        }
    }

    /**
     * Update amenity active status (AJAX).
     */
    public function updateStatus(Request $request, Amenity $amenity)
    {
        try {
            $validated = $request->validate([
                'is_active' => 'required|boolean',
            ]);

            $amenity->update(['is_active' => $validated['is_active']]);

            return response()->json([
                'success' => true,
                'message' => 'Status amenitas berhasil diperbarui.',
                'is_active' => $amenity->is_active,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update amenity status: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified amenity.
     */
    public function destroy(Amenity $amenity)
    {
        try {
            // Detach from properties before deleting
            $amenity->properties()->detach();
            $amenity->delete();

            return redirect()
                ->route('admin.amenities.index')
                ->with('success', 'Amenity deleted successfully.');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to delete amenity: '.$e->getMessage());
        }
    }
}
