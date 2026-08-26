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
        $query = Property::published()->with(['featuredImage', 'photos.media', 'amenities']);

        // --- Search ---
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('city', 'like', '%' . $search . '%')
                  ->orWhere('province', 'like', '%' . $search . '%')
                  ->orWhere('address', 'like', '%' . $search . '%');
            });
        }

        // --- Filter: Booking type (durasi sewa) ---
        // BUG-012 FIX: Filter booking type dilakukan di DB level menggunakan
        // JSON_CONTAINS (MySQL) agar tidak perlu load semua properti ke memory.
        $typeFilter = $request->input('type');
        if ($typeFilter) {
            $query->whereRaw(
                "JSON_CONTAINS(JSON_KEYS(COALESCE(prices, '{}')), JSON_QUOTE(?))",
                [$typeFilter]
            );
        }

        // --- Filter: Tipe unit ---
        $unitTypeFilter = $request->input('unit_type');
        if ($unitTypeFilter) {
            $query->whereJsonContains('unit_types', $unitTypeFilter);
        }

        // --- Filter: Kota ---
        $cityFilter = $request->input('city');
        if ($cityFilter) {
            $query->where('city', $cityFilter);
        }

        // --- Filter: Harga min/max (menggunakan lowestPrice via JSON prices) ---
        $priceMin = $request->input('price_min');
        $priceMax = $request->input('price_max');
        // Price filtering dilakukan di PHP-side setelah query karena prices adalah JSON column
        // yang membutuhkan logic kompleks (lowestPrice across all types & keys).

        // --- Filter: Fasilitas/amenity ---
        $amenityFilter = $request->input('amenities', []);
        if (is_string($amenityFilter)) {
            $amenityFilter = array_filter(explode(',', $amenityFilter));
        }
        if (!empty($amenityFilter)) {
            $query->whereHas('amenities', function ($q) use ($amenityFilter) {
                $q->whereIn('amenities.id', $amenityFilter);
            }, '>=', count($amenityFilter));
        }

        // --- Sorting ---
        $sort = $request->input('sort', 'default');
        switch ($sort) {
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'featured':
                $query->orderBy('is_featured', 'desc')->orderBy('order', 'asc');
                break;
            default:
                $query->orderBy('order', 'asc')->orderBy('created_at', 'desc');
        }

        // Paginate dahulu, lalu filter harga di PHP-side jika ada
        if ($priceMin || $priceMax) {
            $allProperties = $query->get();
            $filtered = $allProperties->filter(function ($p) use ($priceMin, $priceMax) {
                $lowest = $p->lowestPrice();
                if ($lowest === null) return false;
                if ($priceMin && $lowest < (float) $priceMin) return false;
                if ($priceMax && $lowest > (float) $priceMax) return false;
                return true;
            });
            // Gunakan LengthAwarePaginator manual untuk hasil yang sudah difilter
            $page = $request->input('page', 1);
            $perPage = 12;
            $total = $filtered->count();
            $items = $filtered->slice(($page - 1) * $perPage, $perPage)->values();
            $properties = new \Illuminate\Pagination\LengthAwarePaginator(
                $items, $total, $perPage, $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        } else {
            $properties = $query->paginate(12)->withQueryString();
        }

        // --- Data untuk filter sidebar ---
        $availableCities = Property::published()
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->distinct()
            ->orderBy('city')
            ->pluck('city');

        $availableAmenities = \App\Models\Amenity::active()
            ->orderBy('name')
            ->get();

        return view('properties.index', [
            'properties'        => $properties,
            'typeFilter'        => $typeFilter,
            'unitTypeFilter'    => $unitTypeFilter,
            'cityFilter'        => $cityFilter,
            'priceMin'          => $priceMin,
            'priceMax'          => $priceMax,
            'amenityFilter'     => (array) $amenityFilter,
            'sort'              => $sort,
            'availableCities'   => $availableCities,
            'availableAmenities'=> $availableAmenities,
        ]);
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
            'promoRates' => fn ($q) => $q->where('is_active', true)->orderBy('name'),
        ]);

        $nearbyProperties = $this->nearbyProperties($property);

        // Build SEO from the property's own metadata (falls back to name/description).
        // Title suffixing (" - {Site Name}") is applied centrally by SeoService.
        $seo = \App\Services\SeoService::metaTagsArray($property);

        return view('properties.show', compact('property', 'nearbyProperties', 'seo'));
    }

    /**
     * Find up to 3 other published properties to show in the
     * "nearby accommodations" section of the detail page.
     *
     * Selection strategy:
     *  - If the current property has coordinates, compute the great-circle
     *    (Haversine) distance to every other published property that also has
     *    coordinates, order by nearest, and take the 3 closest. Each returned
     *    property carries a `distance_km` attribute (float, KM).
     *  - Otherwise (or to backfill fewer than 3 results), fall back to
     *    same-city properties, then latest properties, excluding the current
     *    one and any already selected. Fallback entries have no `distance_km`.
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\Property>
     */
    protected function nearbyProperties(Property $property, int $limit = 3): \Illuminate\Support\Collection
    {
        $candidates = Property::published()
            ->where('id', '!=', $property->id)
            ->with(['featuredImage', 'photos.media', 'amenities'])
            ->get();

        $selected = collect();

        $hasCoords = $property->latitude !== null && $property->longitude !== null;

        if ($hasCoords) {
            $withDistance = $candidates
                ->filter(fn ($c) => $c->latitude !== null && $c->longitude !== null)
                ->map(function ($c) use ($property) {
                    $c->distance_km = $this->haversineKm(
                        (float) $property->latitude,
                        (float) $property->longitude,
                        (float) $c->latitude,
                        (float) $c->longitude
                    );
                    return $c;
                })
                ->sortBy('distance_km')
                ->take($limit);

            $selected = $withDistance->values();
        }

        // Backfill if we don't yet have enough (no coords, or too few geocoded).
        if ($selected->count() < $limit) {
            $excludeIds = $selected->pluck('id')->all();

            // Same-city first, then latest; distance_km stays unset for these.
            $fallback = $candidates
                ->whereNotIn('id', $excludeIds)
                ->sort(function ($a, $b) use ($property) {
                    $aCity = $a->city === $property->city ? 0 : 1;
                    $bCity = $b->city === $property->city ? 0 : 1;
                    if ($aCity !== $bCity) {
                        return $aCity <=> $bCity;
                    }
                    return $b->created_at <=> $a->created_at;
                })
                ->take($limit - $selected->count())
                ->values();

            $selected = $selected->concat($fallback)->values();
        }

        return $selected;
    }

    /**
     * Great-circle distance between two lat/lng points in kilometers
     * using the Haversine formula.
     */
    protected function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
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
            // FIND-005: sanitize rich content before persistence
            $data['description'] = \App\Services\SafeHtmlService::sanitize($data['description'] ?? null);

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
            // FIND-005: sanitize rich content before persistence
            $data['description'] = \App\Services\SafeHtmlService::sanitize($data['description'] ?? null);

            // Auto-generate slug if empty
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            $property->update($data);

            // Sync amenities. Always sync (default to an empty array) so that
            // unchecking every amenity on Edit actually detaches them — with the
            // previous `$request->has('amenities')` guard, an all-unchecked form
            // omits the `amenities` key entirely and the stale pivot rows were
            // never removed (a silent "cannot delete amenities" bug).
            $property->amenities()->sync($request->input('amenities', []));

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
                    // FIND-007: extension must come from the verified MIME type, never the client filename.
                    // Konvensi penamaan + folder terstruktur Apartment/{Nama}/{Kategori} via helper.
                    $result = upload_file($file, [
                        'base_folder'   => 'Apartment',
                        'sub_folders'   => [$property->name, $category],
                        'name_prefix'   => $property->name,
                        'name_category' => $category,
                    ]);
                    $extension = $result['extension'];
                    $filename  = $result['filename'];
                    $folder    = $result['folder'];
                    $path      = $result['path'];

                    $media = \App\Models\Media::create([
                        'user_id' => auth()->id(),
                        'disk' => 'public',
                        'directory' => $folder,
                        'filename' => $filename,
                        'original_filename' => $originalName,
                        'mime_type' => $file->getMimeType(),
                        'extension' => $extension,
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

        // 5. Category updates for existing saved photos
        //    Submitted as photo_categories_update[{photo_id}] = 'Category Name'
        foreach ((array) $request->input('photo_categories_update', []) as $photoId => $newCategory) {
            $newCategory = trim((string) $newCategory);
            if (!$newCategory) {
                continue;
            }
            $photo = $property->photos()->find($photoId);
            if ($photo) {
                $photo->update(['category' => $newCategory]);
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
                'distance_km' => ($place['distance_km'] ?? null) !== '' && ($place['distance_km'] ?? null) !== null
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
     * Toggle featured status via AJAX.
     */
    public function toggleFeatured(Property $property)
    {
        $property->update(['is_featured' => !$property->is_featured]);

        return response()->json([
            'success' => true,
            'is_featured' => $property->is_featured
        ]);
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

