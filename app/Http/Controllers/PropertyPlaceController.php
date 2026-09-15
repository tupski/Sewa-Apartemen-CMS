<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\PropertyPlace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * PropertyPlaceController — inline admin edits on a property's POI rows
 * (visibility toggle + custom presentation name), used by the POI table on
 * the property Create/Edit screen.
 *
 * Security: every route binds BOTH the property and the pivot row; the pivot
 * must actually belong to the given property or it 404s (IDOR/BOLA guard).
 * The Geoapify provider name on `places.name` is never touched — `custom_name`
 * is a presentation override only.
 */
class PropertyPlaceController extends Controller
{
    /**
     * Inline update of a POI row: visibility and/or custom name.
     */
    public function update(Request $request, Property $property, PropertyPlace $place): JsonResponse
    {
        // IDOR guard: the row must belong to THIS property.
        abort_unless($place->property_id === $property->id, 404);

        // Inline validation with a JSON response: the app renders validation
        // errors as redirects outside `api/*`, and this endpoint is fetch()-only.
        $validator = Validator::make($request->all(), [
            'show_on_frontend' => ['required', 'boolean'],
            'custom_name' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $data = $validator->validated();

        // Normalize: an all-whitespace custom name means "use provider name".
        $customName = trim((string) ($data['custom_name'] ?? ''));

        $place->update([
            'show_on_frontend' => (bool) $data['show_on_frontend'],
            'custom_name' => $customName === '' ? null : $customName,
        ]);

        return response()->json([
            'success' => true,
            'message' => __('poi_table.updated'),
            'place' => [
                'id' => $place->id,
                'show_on_frontend' => $place->show_on_frontend,
                'custom_name' => $place->custom_name,
                'display_name' => $place->display_name,
            ],
        ]);
    }
}
