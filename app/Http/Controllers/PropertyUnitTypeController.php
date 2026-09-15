<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\PropertyUnitType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * PropertyUnitTypeController — admin CRUD for the per-property unit-type
 * metadata rows (display name, description, occupancy, bed configuration,
 * size, active flag, ordering), used by the property Create/Edit screen.
 *
 * Security model: every route is nested under the property (route binding) and
 * the metadata row must actually belong to that property or it 404s — an IDOR
 * guard identical to the POI row endpoints. `unit_type` keys are validated
 * against the canonical Property::UNIT_TYPES whitelist; availability itself is
 * still governed by `properties.unit_types` (the checkbox grid in the pricing
 * section), which this controller never modifies.
 */
class PropertyUnitTypeController extends Controller
{
    /**
     * Validation rules shared by every write path.
     *
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'unit_type' => ['required', 'string', Rule::in(array_keys(Property::UNIT_TYPES))],
            'name' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'max_guests' => ['nullable', 'integer', 'min:1', 'max:30'],
            'bed_configuration' => ['nullable', 'string', 'max:255'],
            'size' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'size_unit' => ['nullable', 'string', 'in:sqm,sqft'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }

    /**
     * Store (or update, when the type already has a row) one unit-type's metadata.
     */
    public function store(Request $request, Property $property): JsonResponse
    {
        // Inline validation with a JSON response: the app renders validation
        // errors as redirects outside `api/*`, and this endpoint is fetch()-only.
        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $data = $validator->validated();
        $unitType = $data['unit_type'];

        // The canonical availability list must actually offer this type; the
        // metadata layer follows `properties.unit_types`, never leads it.
        if (! in_array($unitType, (array) ($property->unit_types ?? []), true)) {
            return response()->json([
                'success' => false,
                'message' => __('unit_type.not_offered'),
            ], 422);
        }

        $payload = [
            'name' => $this->nullableString($data['name'] ?? null),
            'description' => $this->nullableString($data['description'] ?? null),
            'max_guests' => $data['max_guests'] ?? null,
            'bed_configuration' => $this->nullableString($data['bed_configuration'] ?? null),
            'size' => $data['size'] ?? null,
            'size_unit' => $data['size_unit'] ?? 'sqm',
            'is_active' => (bool) $data['is_active'],
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];

        try {
            $row = DB::transaction(function () use ($property, $unitType, $payload): PropertyUnitType {
                return PropertyUnitType::updateOrCreate(
                    ['property_id' => $property->id, 'unit_type' => $unitType],
                    $payload
                );
            });
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => __('unit_type.save_failed'),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => __('unit_type.saved'),
            'unit_type' => $this->payload($row),
        ]);
    }

    /**
     * Bulk save the ordering of the property's unit-type rows.
     */
    public function reorder(Request $request, Property $property): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order' => ['required', 'array', 'max:50'],
            'order.*' => ['integer'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => __('unit_type.save_failed'),
            ], 422);
        }

        $order = $validator->validated()['order'];

        // Every submitted id must belong to THIS property — a stray id aborts
        // the whole reorder instead of silently moving another property's row.
        $ownedCount = PropertyUnitType::where('property_id', $property->id)
            ->whereIn('id', $order)
            ->count();

        if ($ownedCount !== count($order)) {
            return response()->json([
                'success' => false,
                'message' => __('unit_type.not_found'),
            ], 404);
        }

        DB::transaction(function () use ($order): void {
            foreach (array_values($order) as $position => $id) {
                PropertyUnitType::where('id', $id)->update(['sort_order' => $position]);
            }
        });

        return response()->json(['success' => true]);
    }

    /**
     * Delete one unit-type's metadata row.
     *
     * Deleting metadata does NOT touch availability: the type stays offered
     * (canonical `unit_types` + pricing intact); only its rich metadata goes.
     */
    public function destroy(Property $property, PropertyUnitType $unitType): JsonResponse
    {
        // IDOR guard: the row must belong to THIS property.
        abort_unless($unitType->property_id === $property->id, 404);

        $unitType->delete();

        return response()->json([
            'success' => true,
            'message' => __('unit_type.deleted'),
        ]);
    }

    /**
     * Admin API payload for one row.
     *
     * @return array<string, mixed>
     */
    private function payload(PropertyUnitType $row): array
    {
        return [
            'id' => $row->id,
            'unit_type' => $row->unit_type,
            'label' => Property::typeLabel($row->unit_type),
            'display_name' => $row->display_name,
            'name' => $row->name,
            'description' => $row->description,
            'max_guests' => $row->max_guests,
            'bed_configuration' => $row->bed_configuration,
            'size' => $row->size,
            'size_unit' => $row->size_unit,
            'size_formatted' => $row->size_formatted,
            'is_active' => $row->is_active,
            'sort_order' => $row->sort_order,
        ];
    }

    /**
     * Trim a string; all-whitespace becomes null.
     */
    private function nullableString(mixed $value): ?string
    {
        $trimmed = trim((string) ($value ?? ''));

        return $trimmed === '' ? null : $trimmed;
    }
}
