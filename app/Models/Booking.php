<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'unit_id',
        'property_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_whatsapp',
        'check_in',
        'check_out',
        'guests',
        'code',
        'message',
        'status',
        'whatsapp_status',
        'whatsapp_sent_at',
        'total_price',
        'deposit_amount',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'guests' => 'integer',
        'total_price' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'metadata' => 'json',
        'whatsapp_sent_at' => 'datetime',
    ];

    /**
     * Get the unit that owns the booking.
     */
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the property that owns the booking.
     */
    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Scope a query to only include pending bookings.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include confirmed bookings.
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    /**
     * Scope a query to only include cancelled bookings.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope a query to only include completed bookings.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Check if the booking is active.
     */
    public function isActive(): bool
    {
        return in_array($this->status, ['pending', 'confirmed']);
    }

    /**
     * Check if the booking is past due.
     */
    public function isPastDue(): bool
    {
        return $this->check_out->isPast() && $this->status !== 'completed';
    }
}
