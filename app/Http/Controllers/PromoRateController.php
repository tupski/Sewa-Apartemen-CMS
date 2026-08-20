<?php

namespace App\Http\Controllers;

use App\Models\PromoRate;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromoRateController extends Controller
{
    /**
     * Store a new promo rate for a property.
     */
    public function store(Request $request, Property $property): JsonResponse
    {
        $data = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'applies_to'     => ['required', 'string', 'in:all,weekday,weekend,custom'],
            'active_days'    => ['nullable', 'array'],
            'active_days.*'  => ['integer', 'min:0', 'max:6'],
            'start_time'     => ['required', 'date_format:H:i'],
            'end_time'       => ['required', 'date_format:H:i'],
            'price'          => ['required', 'integer', 'min:0'],
            'booking_type'   => ['required', 'string', 'in:all,night,transit,weekly,monthly'],
            'duration_hours' => ['nullable', 'integer', 'in:3,6,9,12,24'],
            'is_active'      => ['boolean'],
        ]);

        $promo = $property->promoRates()->create($data);

        return response()->json([
            'success' => true,
            'promo'   => $promo,
            'message' => 'Promo berhasil ditambahkan.',
        ], 201);
    }

    /**
     * Update an existing promo rate.
     */
    public function update(Request $request, Property $property, PromoRate $promo): JsonResponse
    {
        abort_unless($promo->property_id === $property->id, 404);

        $data = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'applies_to'     => ['required', 'string', 'in:all,weekday,weekend,custom'],
            'active_days'    => ['nullable', 'array'],
            'active_days.*'  => ['integer', 'min:0', 'max:6'],
            'start_time'     => ['required', 'date_format:H:i'],
            'end_time'       => ['required', 'date_format:H:i'],
            'price'          => ['required', 'integer', 'min:0'],
            'booking_type'   => ['required', 'string', 'in:all,night,transit,weekly,monthly'],
            'duration_hours' => ['nullable', 'integer', 'in:3,6,9,12,24'],
            'is_active'      => ['boolean'],
        ]);

        $promo->update($data);

        return response()->json([
            'success' => true,
            'promo'   => $promo->fresh(),
            'message' => 'Promo berhasil diperbarui.',
        ]);
    }

    /**
     * Delete a promo rate.
     */
    public function destroy(Property $property, PromoRate $promo): JsonResponse
    {
        abort_unless($promo->property_id === $property->id, 404);

        $promo->delete();

        return response()->json([
            'success' => true,
            'message' => 'Promo berhasil dihapus.',
        ]);
    }
}
