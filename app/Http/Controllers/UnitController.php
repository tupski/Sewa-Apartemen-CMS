<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\Property;
use App\Http\Requests\UnitRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UnitController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of units.
     */
    public function index(Request $request)
    {
        $query = Unit::with(['property', 'featuredImage']);

        // Search by name
        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Filter by property
        if ($request->has('property_id') && $request->property_id) {
            $query->where('property_id', $request->property_id);
        }

        // Filter by unit type
        if ($request->has('unit_type') && $request->unit_type) {
            $query->where('unit_type', $request->unit_type);
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $units = $query->orderBy('created_at', 'desc')->paginate(15);
        $properties = Property::orderBy('name')->get();

        return view('admin.units.index', compact('units', 'properties'));
    }

    /**
     * Show the form for creating a new unit.
     */
    public function create()
    {
        $properties = Property::orderBy('name')->get();
        $amenities = \App\Models\Amenity::where('is_active', true)
                                      ->where('category', 'unit')
                                      ->orderBy('name')
                                      ->get();

        return view('admin.units.create', compact('properties', 'amenities'));
    }

    /**
     * Store a newly created unit.
     */
    public function store(UnitRequest $request)
    {
        try {
            $data = $request->validated();

            // Auto-generate slug if empty
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            $unit = Unit::create($data);

            // Sync amenities if provided
            if ($request->has('amenities')) {
                $unit->amenities()->sync($request->amenities);
            }

            return redirect()
                ->route('admin.units.index')
                ->with('success', 'Unit created successfully.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to create unit: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified unit.
     */
    public function edit(Unit $unit)
    {
        $unit->load('amenities');

        $properties = Property::orderBy('name')->get();
        $amenities = \App\Models\Amenity::where('is_active', true)
                                      ->where('category', 'unit')
                                      ->orderBy('name')
                                      ->get();

        return view('admin.units.edit', compact('unit', 'properties', 'amenities'));
    }

    /**
     * Update the specified unit.
     */
    public function update(UnitRequest $request, Unit $unit)
    {
        try {
            $data = $request->validated();

            // Auto-generate slug if empty
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            $unit->update($data);

            // Sync amenities
            if ($request->has('amenities')) {
                $unit->amenities()->sync($request->amenities);
            }

            return redirect()
                ->route('admin.units.index')
                ->with('success', 'Unit updated successfully.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to update unit: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified unit.
     */
    public function destroy(Unit $unit)
    {
        try {
            $unit->delete();

            return redirect()
                ->route('admin.units.index')
                ->with('success', 'Unit deleted successfully.');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to delete unit: ' . $e->getMessage());
        }
    }
}
