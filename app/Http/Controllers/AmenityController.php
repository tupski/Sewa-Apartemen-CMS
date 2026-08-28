<?php

namespace App\Http\Controllers;

use App\Http\Requests\AmenityRequest;
use App\Models\Amenity;
use Illuminate\Http\Request;
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
            ->paginate(15);

        return view('admin.amenities.index', compact('amenities'));
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
