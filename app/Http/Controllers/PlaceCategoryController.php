<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlaceCategoryBulkRequest;
use App\Models\Place;
use App\Models\PlaceCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * PlaceCategoryController — admin CRUD for the Artivo-managed POI category
 * catalogue, embedded in the property Create/Edit "Nearby Places" screen.
 *
 * Display labels (name_id / name_en), icon and color are admin-editable; the
 * `slug` is the raw Geoapify category key and is immutable once created (it is
 * the sync match key — rewriting it would orphan every synced place).
 *
 * Deletion is safe by default: a category still referenced by any persisted
 * place (exact slug or child prefix, e.g. `public_transport` used by a place
 * stored as `public_transport.train`) cannot be deleted.
 */
class PlaceCategoryController extends Controller
{
    /**
     * Bulk-upsert the category catalogue.
     */
    public function update(PlaceCategoryBulkRequest $request): JsonResponse
    {
        $rows = $request->validated('categories');

        // A new row's slug must not collide with a DB row that is not itself
        // being kept (matched by id) — `distinct` only covers the payload.
        $keptIds = collect($rows)->pluck('id')->filter()->all();
        $newSlugs = collect($rows)
            ->reject(fn (array $row): bool => ! empty($row['id']))
            ->pluck('slug')
            ->unique()
            ->values();

        if ($newSlugs->isNotEmpty()) {
            $colliding = PlaceCategory::query()
                ->whereIn('slug', $newSlugs)
                ->when($keptIds !== [], fn ($query) => $query->whereNotIn('id', $keptIds))
                ->first();

            if ($colliding !== null) {
                return response()->json([
                    'success' => false,
                    'message' => __('place_category.duplicate_slug', ['slug' => $colliding->slug]),
                ], 422);
            }
        }

        try {
            DB::transaction(function () use ($rows, &$created, &$updated): void {
                $created = 0;
                $updated = 0;

                foreach ($rows as $row) {
                    $payload = [
                        'name_id' => $row['name_id'],
                        'name_en' => $row['name_en'],
                        'icon' => $row['icon'] ?? null,
                        'color' => $row['color'] ?? null,
                        'is_active' => (bool) $row['is_active'],
                        'sort_order' => (int) ($row['sort_order'] ?? 0),
                    ];

                    if (! empty($row['id'])) {
                        // Slug is immutable on existing rows (see class docblock).
                        PlaceCategory::findOrFail($row['id'])->update($payload);
                        $updated++;
                    } else {
                        PlaceCategory::create($payload + ['slug' => $row['slug']]);
                        $created++;
                    }
                }
            });
        } catch (Throwable $e) {
            // Never leak the internals of an unexpected failure.
            report($e);

            return response()->json([
                'success' => false,
                'message' => __('place_category.save_failed'),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => __('place_category.saved'),
            'created' => $created,
            'updated' => $updated,
            'categories' => $this->categoryPayload(),
        ]);
    }

    /**
     * Delete a category — blocked while any persisted place still uses it.
     */
    public function destroy(PlaceCategory $placeCategory): JsonResponse
    {
        $slug = $placeCategory->slug;

        // Distinct raw categories are a small bounded set (provider keys), so the
        // prefix check is done in PHP — portable across MySQL and SQLite.
        $inUse = Place::query()
            ->select('category')
            ->distinct()
            ->get()
            ->contains(fn (Place $place): bool => $place->category === $slug
                || str_starts_with((string) $place->category, $slug.'.'));

        if ($inUse) {
            return response()->json([
                'success' => false,
                'message' => __('place_category.delete_in_use', ['slug' => $slug]),
            ], 422);
        }

        $placeCategory->delete();

        return response()->json([
            'success' => true,
            'message' => __('place_category.deleted'),
            'categories' => $this->categoryPayload(),
        ]);
    }

    /**
     * Fresh catalogue for the Alpine panel, in display order.
     *
     * @return array<int, array<string, mixed>>
     */
    private function categoryPayload(): array
    {
        return PlaceCategory::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (PlaceCategory $category): array => [
                'id' => $category->id,
                'slug' => $category->slug,
                'name_id' => $category->name_id,
                'name_en' => $category->name_en,
                'icon' => $category->icon,
                'color' => $category->color,
                'is_active' => $category->is_active,
                'sort_order' => $category->sort_order,
            ])
            ->all();
    }
}
