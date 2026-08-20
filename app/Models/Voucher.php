<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Voucher extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'discount_type',
        'discount_value',
        'min_booking_amount',
        'max_discount_amount',
        'usage_limit',
        'used_count',
        'valid_from',
        'valid_until',
        'is_active',
    ];

    protected $casts = [
        'discount_value'     => 'decimal:2',
        'min_booking_amount' => 'integer',
        'max_discount_amount'=> 'integer',
        'usage_limit'        => 'integer',
        'used_count'         => 'integer',
        'valid_from'         => 'date',
        'valid_until'        => 'date',
        'is_active'          => 'boolean',
    ];

    /**
     * Always store code as uppercase.
     */
    public function setCodeAttribute(string $value): void
    {
        $this->attributes['code'] = strtoupper($value);
    }

    /**
     * Check if this voucher is currently valid.
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $today = Carbon::today();

        if ($this->valid_from && $today->lt($this->valid_from)) {
            return false;
        }

        if ($this->valid_until && $today->gt($this->valid_until)) {
            return false;
        }

        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    /**
     * Calculate the discount amount for a given booking total.
     * Respects max_discount_amount cap for percent-type vouchers.
     */
    public function calculateDiscount(int $amount): int
    {
        if ($this->discount_type === 'percent') {
            $discount = (int) round($amount * ($this->discount_value / 100));

            if ($this->max_discount_amount !== null) {
                $discount = min($discount, $this->max_discount_amount);
            }
        } else {
            // fixed
            $discount = (int) $this->discount_value;
        }

        // Discount cannot exceed the booking amount
        return min($discount, $amount);
    }

    /**
     * Bookings that used this voucher.
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}
