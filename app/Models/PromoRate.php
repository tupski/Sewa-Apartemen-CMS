<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PromoRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'name',
        'applies_to',
        'active_days',
        'start_time',
        'end_time',
        'price',
        'booking_type',
        'duration_hours',
        'is_active',
    ];

    protected $casts = [
        'active_days'    => 'array',
        'price'          => 'integer',
        'duration_hours' => 'integer',
        'is_active'      => 'boolean',
    ];

    /**
     * Get the property that owns the promo rate.
     */
    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Check if this promo applies to the given day of week (0=Sun..6=Sat)
     * and check-in time string ("HH:MM").
     */
    public function matchesCheckin(int $dayOfWeek, string $time, string $bookingType, ?int $durationHours = null): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // Check booking type compatibility
        if ($this->booking_type !== 'all' && $this->booking_type !== $bookingType) {
            return false;
        }

        // For transit bookings, check duration
        if ($bookingType === 'transit' && $this->booking_type === 'transit' && $this->duration_hours !== null) {
            if ($this->duration_hours !== $durationHours) {
                return false;
            }
        }

        // Check day of week
        $weekendDays = [0, 6]; // Sun, Sat — simplified default; real check uses property config
        $dayMatches = match ($this->applies_to) {
            'weekday' => !in_array($dayOfWeek, $weekendDays),
            'weekend' => in_array($dayOfWeek, $weekendDays),
            'custom'  => in_array($dayOfWeek, $this->active_days ?? []),
            default   => true, // 'all'
        };

        if (!$dayMatches) {
            return false;
        }

        // Check time window (supports overnight: start > end means crosses midnight)
        $start = $this->start_time;
        $end   = $this->end_time;

        if ($start <= $end) {
            // Same-day window: e.g. 14:00–22:00
            return $time >= $start && $time < $end;
        } else {
            // Overnight window: e.g. 21:00–03:00
            return $time >= $start || $time < $end;
        }
    }
}
