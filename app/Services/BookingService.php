<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;

class BookingService
{
    /**
     * Generate a unique booking code.
     *
     * Format: BK-YYYYMMDD-XXXX (where XXXX is a 4-digit sequence number)
     *
     * @return string
     */
    public static function generateCode(): string
    {
        $date = now();
        $datePrefix = $date->format('Ymd');
        
        // Get the last booking code for today
        $lastBooking = Booking::where('code', 'like', "BK-{$datePrefix}-%")
            ->orderBy('code', 'desc')
            ->first();
        
        if ($lastBooking) {
            // Extract the sequence number
            $lastNumber = (int) substr($lastBooking->code, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return "BK-{$datePrefix}-" . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create a new booking with all related data.
     *
     * @param array $data
     * @return Booking
     * @throws \Exception
     */
    public static function create(array $data): Booking
    {
        return DB::transaction(function () use ($data) {
            // Validate unit availability
            self::validateUnitAvailability(
                $data['unit_id'],
                $data['check_in'],
                $data['check_out']
            );
            
            // Calculate pricing
            $unit = Unit::findOrFail($data['unit_id']);
            $pricePerNight = $unit->price_per_night ?? 0;
            $numNights = now($data['check_in'])->diffInDays($data['check_out']);
            $totalPrice = $pricePerNight * $numNights;
            $depositAmount = $totalPrice * 0.3; // 30% deposit
            
            // Create the booking
            $booking = Booking::create([
                'unit_id' => $data['unit_id'],
                'property_id' => $data['property_id'],
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'] ?? null,
                'customer_phone' => $data['customer_phone'],
                'customer_whatsapp' => $data['customer_whatsapp'] ?? null,
                'check_in' => $data['check_in'],
                'check_out' => $data['check_out'],
                'guests' => $data['guests'] ?? 1,
                'code' => self::generateCode(),
                'message' => $data['message'] ?? null,
                'status' => 'pending',
                'total_price' => $totalPrice,
                'deposit_amount' => $depositAmount,
                'metadata' => [
                    'num_nights' => $numNights,
                    'price_per_night' => $pricePerNight,
                ],
            ]);
            
            // Mark unit as booked
            $unit->update(['status' => 'booked']);
            
            return $booking;
        });
    }

    /**
     * Validate unit availability for the requested dates.
     *
     * @param int $unitId
     * @param string $checkIn
     * @param string $checkOut
     * @return bool
     * @throws \Exception
     */
    protected static function validateUnitAvailability(int $unitId, string $checkIn, string $checkOut): bool
    {
        // Check for conflicting bookings
        $conflicting = Booking::where('unit_id', $unitId)
            ->where('status', '!=', 'cancelled')
            ->where(function ($query) use ($checkIn, $checkOut) {
                $query->where(function ($q) use ($checkIn, $checkOut) {
                    // New booking starts during existing booking
                    $q->where('check_in', '<=', $checkIn)
                      ->where('check_out', '>', $checkIn);
                })->orWhere(function ($q) use ($checkIn, $checkOut) {
                    // New booking ends during existing booking
                    $q->where('check_in', '<', $checkOut)
                      ->where('check_out', '>=', $checkOut);
                })->orWhere(function ($q) use ($checkIn, $checkOut) {
                    // New booking completely contains existing booking
                    $q->where('check_in', '>=', $checkIn)
                      ->where('check_out', '<=', $checkOut);
                });
            })
            ->exists();
        
        if ($conflicting) {
            throw new \Exception('Unit is not available for the selected dates.');
        }
        
        return true;
    }

    /**
     * Confirm a booking.
     *
     * @param Booking $booking
     * @return Booking
     */
    public static function confirm(Booking $booking): Booking
    {
        return DB::transaction(function () use ($booking) {
            $booking->update([
                'status' => 'confirmed',
            ]);
            
            // Mark unit as booked
            $booking->unit->update(['status' => 'booked']);
            
            return $booking;
        });
    }

    /**
     * Cancel a booking.
     *
     * @param Booking $booking
     * @return Booking
     */
    public static function cancel(Booking $booking): Booking
    {
        return DB::transaction(function () use ($booking) {
            $booking->update([
                'status' => 'cancelled',
            ]);
            
            // Mark unit as available again
            $booking->unit->update(['status' => 'available']);
            
            return $booking;
        });
    }

    /**
     * Complete a booking (after stay is finished).
     *
     * @param Booking $booking
     * @return Booking
     */
    public static function complete(Booking $booking): Booking
    {
        return DB::transaction(function () use ($booking) {
            $booking->update([
                'status' => 'completed',
            ]);
            
            // Mark unit as available
            $booking->unit->update(['status' => 'available']);
            
            return $booking;
        });
    }
}