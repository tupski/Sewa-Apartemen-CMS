<?php

namespace App\Services;

use App\Models\Property;
use Carbon\Carbon;

class BookingPricingService
{
    /** Transit duration buckets in hours. */
    public const TRANSIT_BUCKETS = [3, 6, 9, 12, 24];

    /** Night count for bulk rates. */
    public const WEEKLY_NIGHTS = 7;
    public const MONTHLY_NIGHTS = 30;

    /**
     * Calculate the total price for a booking.
     *
     * Weekday/weekend is decided per night (daily) or per check-in day (transit)
     * using the property's own weekend_days configuration.
     *
     * @return array{total: float, nights: int, hours: ?int, is_weekend: ?bool, days: array<string,float>, rate: ?float}
     */
    public function calculate(
        Property $property,
        string $unitType,
        string $bookingType,
        Carbon $checkIn,
        ?Carbon $checkOut = null,
        ?int $hours = null
    ): array {
        $prices = $property->prices[$unitType] ?? [];
        $weekendDays = $property->weekendDays();

        // Transit: fixed hourly bucket, rate decided by the check-in day
        if ($bookingType === 'transit') {
            $hours = in_array($hours, self::TRANSIT_BUCKETS, true) ? $hours : 3;
            $isWeekend = in_array($checkIn->dayOfWeek, $weekendDays, true);
            $key = ($isWeekend ? 't' . $hours . '_we' : 't' . $hours . '_wd');
            $rate = (float) ($prices[$key] ?? 0);

            return [
                'total' => $rate,
                'nights' => 1,
                'hours' => $hours,
                'is_weekend' => $isWeekend,
                'days' => [$checkIn->format('Y-m-d') => $rate],
                'rate' => $rate,
            ];
        }

        // Bulk rates: weekly / monthly are flat prices
        if ($bookingType === 'weekly') {
            $rate = (float) ($prices['weekly'] ?? 0);
            $nights = self::WEEKLY_NIGHTS;

            return [
                'total' => $rate,
                'nights' => $nights,
                'hours' => null,
                'is_weekend' => null,
                'days' => [],
                'rate' => $rate,
            ];
        }

        if ($bookingType === 'monthly') {
            $rate = (float) ($prices['monthly'] ?? 0);
            $nights = self::MONTHLY_NIGHTS;

            return [
                'total' => $rate,
                'nights' => $nights,
                'hours' => null,
                'is_weekend' => null,
                'days' => [],
                'rate' => $rate,
            ];
        }

        // Daily: per-night pricing, weekday/weekend per night
        $nights = $checkOut ? max(1, (int) $checkIn->diffInDays($checkOut)) : 1;
        $total = 0.0;
        $days = [];

        for ($i = 0; $i < $nights; $i++) {
            $day = $checkIn->copy()->addDays($i);
            $isWeekend = in_array($day->dayOfWeek, $weekendDays, true);
            $rate = (float) ($prices[$isWeekend ? 'night_we' : 'night_wd'] ?? 0);
            $total += $rate;
            $days[$day->format('Y-m-d')] = $rate;
        }

        return [
            'total' => round($total, 2),
            'nights' => $nights,
            'hours' => null,
            'is_weekend' => null,
            'days' => $days,
            'rate' => null,
        ];
    }
}
