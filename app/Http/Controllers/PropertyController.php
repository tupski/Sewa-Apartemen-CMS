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
        $query = Property::published()->with(['featuredImage', 'amenities']);

        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
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
            'photos.media',
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

        $mediaImages = \App\Models\Media::where('type', 'image')->latest()->limit(60)->get();

        return view('admin.properties.create', compact('amenities', 'mediaImages'));
    }

    /**
     * Store a newly created property.
     */
    public function store(PropertyRequest $request)
    {
        try {
            $data = array_merge($request->validated(), $this->normalizeDetailData($request), $this->buildPricingData($request));

            // Auto-generate slug if empty
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            $property = Property::create($data);

            // Sync amenities if provided
            if ($request->has('amenities')) {
                $property->amenities()->sync($request->amenities);
            }

            $this->saveGallery($request, $property);

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
     * Display the specified property (redirect to edit form).
     */
    public function show(Property $property)
    {
        return redirect()->route('admin.properties.edit', $property);
    }

    /**
     * Show the form for editing the specified property.
     */
    public function edit(Property $property)
    {
        $property->load(['amenities', 'photos.media']);

        $amenities = \App\Models\Amenity::where('is_active', true)
                                      ->orderBy('name')
                                      ->get();

        $mediaImages = \App\Models\Media::where('type', 'image')->latest()->limit(60)->get();

        return view('admin.properties.edit', compact('property', 'amenities', 'mediaImages'));
    }

    /**
     * Update the specified property.
     */
    public function update(PropertyRequest $request, Property $property)
    {
        try {
            $data = array_merge($request->validated(), $this->normalizeDetailData($request), $this->buildPricingData($request));

            // Auto-generate slug if empty
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            $property->update($data);

            // Sync amenities
            if ($request->has('amenities')) {
                $property->amenities()->sync($request->amenities);
            }

            $this->saveGallery($request, $property);

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
     * Update property status.
     */
    public function updateStatus(Request $request, Property $property)
    {
        try {
            $validated = $request->validate([
                'status' => 'required|string|in:published,draft',
            ]);

            $property->update(['status' => $validated['status']]);

            return response()->json([
                'success' => true,
                'message' => 'Property status updated successfully.',
                'status' => $property->status,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update property status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Save photo gallery: categories, uploads, media-library picks, deletions.
     */
    protected function saveGallery(Request $request, Property $property): void
    {
        // 1. Categories
        $categories = $request->input('photo_categories', []);
        if (is_string($categories)) {
            $categories = json_decode($categories, true) ?: [];
        }
        $categories = array_values(array_unique(array_filter(array_map('trim', (array) $categories))));
        if ($categories) {
            $property->update(['photo_categories' => $categories]);
        }

        // 2. Photos picked from the Media library (no file move, just link + category)
        foreach ((array) $request->input('gallery_media', []) as $index => $mediaIds) {
            $category = $categories[(int) $index] ?? null;
            if (!$category) {
                continue;
            }
            foreach ((array) $mediaIds as $mediaId) {
                if (!\App\Models\Media::whereKey($mediaId)->exists()) {
                    continue;
                }
                $exists = $property->photos()->where('media_id', $mediaId)->where('category', $category)->exists();
                if (!$exists) {
                    $property->photos()->create([
                        'media_id' => $mediaId,
                        'category' => $category,
                        'sort_order' => $property->photos()->count(),
                    ]);
                }
            }
        }

        // 3. Direct uploads (stored under properties/{id}/{category-slug}/)
        foreach ((array) $request->file('gallery_uploads', []) as $index => $files) {
            $category = $categories[(int) $index] ?? null;
            if (!$category) {
                continue;
            }
            $catSlug = \Illuminate\Support\Str::slug($category, '_') ?: 'general';

            foreach ((array) $files as $file) {
                try {
                    $originalName = $file->getClientOriginalName();
                    $safeName = \Illuminate\Support\Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) ?: 'photo';
                    $filename = $safeName . '-' . time() . '-' . \Illuminate\Support\Str::random(8) . '.' . $file->getClientOriginalExtension();
                    $folder = "properties/{$property->id}/{$catSlug}";
                    $path = $file->storeAs($folder, $filename, 'public');

                    $media = \App\Models\Media::create([
                        'user_id' => auth()->id(),
                        'disk' => 'public',
                        'directory' => $folder,
                        'filename' => $filename,
                        'original_filename' => $originalName,
                        'mime_type' => $file->getMimeType(),
                        'extension' => $file->getClientOriginalExtension(),
                        'size' => $file->getSize(),
                        'type' => 'image',
                        'alt' => $category,
                        'title' => $category,
                    ]);

                    $property->photos()->create([
                        'media_id' => $media->id,
                        'category' => $category,
                        'sort_order' => $property->photos()->count(),
                    ]);
                } catch (\Exception $e) {
                    // Skip unreadable file; keep the rest of the gallery intact
                    continue;
                }
            }
        }

        // 4. Deletions (photo rows; file removed only when no longer referenced anywhere)
        $deletedIds = $request->input('deleted_photo_ids', []);
        if (is_string($deletedIds)) {
            $deletedIds = array_filter(array_map('trim', explode(',', $deletedIds)));
        }
        foreach ((array) $deletedIds as $photoId) {
            $photo = \App\Models\PropertyPhoto::find($photoId);
            if (!$photo) {
                continue;
            }
            $media = $photo->media;
            $photo->delete();

            if ($media && $this->mediaIsUnused($media)) {
                $media->deleteFile();
                $media->delete();
            }
        }
    }

    /**
     * True when a media file is not referenced by any property photo or featured image.
     */
    protected function mediaIsUnused(\App\Models\Media $media): bool
    {
        $asPhoto = \App\Models\PropertyPhoto::where('media_id', $media->id)->exists();
        $asFeatured = \App\Models\Property::where('featured_image_id', $media->id)->exists();

        return !$asPhoto && !$asFeatured;
    }

    /**
     * Normalize JSON detail fields (required documents, nearby places) from the form.
     */
    protected function normalizeDetailData(Request $request): array
    {
        $docs = array_values(array_filter(array_map('trim', (array) $request->input('required_documents', []))));
        $places = [];

        foreach ((array) $request->input('nearby_places', []) as $place) {
            $name = trim((string) ($place['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $places[] = [
                'name' => $name,
                'category' => $place['category'] ?? 'Others',
                'distance_km' => $place['distance_km'] !== '' && $place['distance_km'] !== null
                    ? (float) $place['distance_km']
                    : null,
            ];
        }

        return [
            'required_documents' => $docs ?: null,
            'nearby_places' => $places ?: null,
        ];
    }

    /**
     * Build unit_types / weekend_days / prices arrays from the form.
     */
    protected function buildPricingData(Request $request): array
    {
        $types = $request->input('unit_types', []);
        $types = array_values(array_intersect(array_keys(\App\Models\Property::UNIT_TYPES), (array) $types));

        $weekendDays = $request->input('weekend_days', []);
        $weekendDays = array_values(array_unique(array_map('intval', (array) $weekendDays)));

        $priceKeys = ['night_wd', 'night_we', 't3_wd', 't3_we', 't6_wd', 't6_we', 't9_wd', 't9_we', 't12_wd', 't12_we', 't24_wd', 't24_we', 'weekly', 'monthly'];

        $prices = [];
        foreach ($types as $type) {
            $typePrices = $request->input("prices.{$type}", []);
            foreach ($priceKeys as $key) {
                $value = $typePrices[$key] ?? null;
                if ($value !== null && $value !== '') {
                    $prices[$type][$key] = (float) $value;
                }
            }
        }

        return [
            'unit_types' => $types,
            'weekend_days' => $weekendDays ?: [6, 0],
            'prices' => $prices,
        ];
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
