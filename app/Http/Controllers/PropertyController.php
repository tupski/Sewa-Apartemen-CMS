<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Http\Requests\PropertyRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PropertyController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth')->except(['publicIndex', 'publicShow']);
    }

    /**
     * Public listing of published properties.
     */
    public function publicIndex(Request $request)
    {
        $query = Property::published()->with('featuredImage');

        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->has('city') && $request->city) {
            $query->where('city', 'like', '%' . $request->city . '%');
        }

        $properties = $query->orderBy('order', 'asc')
                            ->orderBy('created_at', 'desc')
                            ->paginate(12)
                            ->withQueryString();

        return view('properties.index', compact('properties'));
    }

    /**
     * Public detail page for a published property.
     */
    public function publicShow(Property $property)
    {
        abort_unless($property->status === 'published', 404);

        $property->load([
            'featuredImage',
            'amenities',
            'units' => fn ($q) => $q->orderBy('price_per_night')->orderBy('name'),
            'units.amenities',
            'units.featuredImage',
        ]);

        return view('properties.show', compact('property'));
    }

    /**
     * Display a listing of properties.
     */
    public function index(Request $request)
    {
        $query = Property::with('featuredImage');

        // Search by name
        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by city
        if ($request->has('city') && $request->city) {
            $query->where('city', 'like', '%' . $request->city . '%');
        }

        $properties = $query->orderBy('order', 'asc')
                           ->orderBy('created_at', 'desc')
                           ->paginate(15);

        return view('admin.properties.index', compact('properties'));
    }

    /**
     * Show the form for creating a new property.
     */
    public function create()
    {
        $amenities = \App\Models\Amenity::where('is_active', true)
                                      ->orderBy('name')
                                      ->get();

        return view('admin.properties.create', compact('amenities'));
    }

    /**
     * Store a newly created property.
     */
    public function store(PropertyRequest $request)
    {
        try {
            $data = $request->validated();

            // Auto-generate slug if empty
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            $property = Property::create($data);

            // Sync amenities if provided
            if ($request->has('amenities')) {
                $property->amenities()->sync($request->amenities);
            }

            // Save SEO metadata
            $property->seo()->updateOrCreate([], [
                'meta_title' => $request->input('seo.meta_title'),
                'meta_description' => $request->input('seo.meta_description'),
                'open_graph' => $request->input('seo.open_graph'),
                'twitter' => $request->input('seo.twitter'),
                'canonical_url' => $request->input('seo.canonical_url'),
                'index_status' => $request->boolean('seo.index_status', true),
            ]);

            return redirect()
                ->route('admin.properties.index')
                ->with('success', 'Property created successfully.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to create property: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified property.
     */
    public function edit(Property $property)
    {
        $property->load('amenities');

        $amenities = \App\Models\Amenity::where('is_active', true)
                                      ->orderBy('name')
                                      ->get();

        return view('admin.properties.edit', compact('property', 'amenities'));
    }

    /**
     * Update the specified property.
     */
    public function update(PropertyRequest $request, Property $property)
    {
        try {
            $data = $request->validated();

            // Auto-generate slug if empty
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            $property->update($data);

            // Sync amenities
            if ($request->has('amenities')) {
                $property->amenities()->sync($request->amenities);
            }

            // Save SEO metadata
            $property->seo()->updateOrCreate([], [
                'meta_title' => $request->input('seo.meta_title'),
                'meta_description' => $request->input('seo.meta_description'),
                'open_graph' => $request->input('seo.open_graph'),
                'twitter' => $request->input('seo.twitter'),
                'canonical_url' => $request->input('seo.canonical_url'),
                'index_status' => $request->boolean('seo.index_status', true),
            ]);

            return redirect()
                ->route('admin.properties.index')
                ->with('success', 'Property updated successfully.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to update property: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified property.
     */
    public function destroy(Property $property)
    {
        try {
            $property->delete();

            return redirect()
                ->route('admin.properties.index')
                ->with('success', 'Property deleted successfully.');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to delete property: ' . $e->getMessage());
        }
    }
}
