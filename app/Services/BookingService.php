<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Property;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BookingService
{
    /**
     * Generate a unique booking code.
     *
     * Format: BK-YYYYMMDD-XXXX (where XXXX is a 4-digit sequence number)
     *
     * BUG-006 FIX: Pembacaan kode terakhir + increment kini dilakukan di dalam
     * DB::transaction dengan lockForUpdate() agar tidak menghasilkan kode duplikat
     * pada concurrent requests di hari yang sama.
     */
    public static function generateCode(): string
    {
        return DB::transaction(function () {
            $datePrefix = now()->format('Ymd');

            // Lock row terakhir agar tidak ada dua request yang membaca nilai yang sama
            $lastBooking = Booking::where('code', 'like', "BK-{$datePrefix}-%")
                ->orderBy('code', 'desc')
                ->lockForUpdate()
                ->first();

            $newNumber = $lastBooking
                ? ((int) substr($lastBooking->code, -4)) + 1
                : 1;

            return "BK-{$datePrefix}-" . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Create a new booking with pricing.
     *
     * @param array $data expected keys: property_id, booking_type, unit_type,
     *                    duration_hours (transit), check_in, check_out (nullable),
     *                    customer_name, customer_email, customer_phone,
     *                    customer_whatsapp, guests, message
     * @return Booking
     * @throws \Exception
     */
    public static function create(array $data): Booking
    {
        return DB::transaction(function () use ($data) {
            $property = Property::findOrFail($data['property_id']);

            if (!$property->hasType($data['unit_type'])) {
                throw new \Exception('Tipe kamar tidak tersedia di properti ini.');
            }

            // Normalize new booking method (duration + unit jam/malam) to legacy fields
            if (!empty($data['unit']) && !empty($data['duration'])) {
                $unit = $data['unit'];
                $duration = (int) $data['duration'];

                if ($unit === 'jam') {
                    $data['booking_type'] = 'transit';
                    $data['duration_hours'] = $duration;
                    $data['check_out'] = null;
                } else {
                    $data['booking_type'] = 'daily';
                    $data['duration_hours'] = null;
                    $maxDays = $property->maxBookingDays();
                    if ($maxDays && $duration > $maxDays) {
                        throw new \Exception("Durasi maksimal pemesanan properti ini adalah {$maxDays} malam.");
                    }
                    $data['check_out'] = Carbon::parse($data['check_in'])->addDays($duration)->format('Y-m-d');
                }
            }

            // Legacy booking types: reject when the price for that type is not set
            if (in_array($data['booking_type'] ?? '', ['weekly', 'monthly', 'transit'], true)
                && !$property->hasBookingType($data['booking_type'])) {
                throw new \Exception('Metode sewa ini belum tersedia. Silakan hubungi admin.');
            }

            $checkIn = Carbon::parse($data['check_in']);
            $checkOut = !empty($data['check_out']) ? Carbon::parse($data['check_out']) : null;

            if (!empty($data['check_in_time'])) {
                [$h, $m] = array_pad(explode(':', $data['check_in_time']), 2, '0');
                $checkIn = $checkIn->copy()->setTime((int) $h, (int) $m);
                // Keep full nights intact: align check-out to the same time of day
                if ($checkOut) {
                    $checkOut = $checkOut->copy()->setTime((int) $h, (int) $m);
                }
            }

            // Transit keeps check_out null unless a start time is known; price is by bucket
            $pricing = app(BookingPricingService::class)->calculate(
                $property,
                $data['unit_type'],
                $data['booking_type'],
                $checkIn,
                $checkOut,
                $data['duration_hours'] ?? null,
            );

            if ($pricing['total'] <= 0) {
                throw new \Exception('Harga untuk tipe sewa ini belum diatur. Silakan hubungi admin.');
            }

            self::validateAvailability($property->id, $data['unit_type'], $checkIn, $checkOut, $data['booking_type']);

            $totalPrice = $pricing['total'];
            $depositAmount = round($totalPrice * 0.3, 2);

            // Transit bookings: store the exact window when a start time is provided
            $effectiveCheckOut = $data['booking_type'] === 'transit'
                ? $checkIn->copy()->addHours((int) ($data['duration_hours'] ?? 3))
                : $checkOut;

            $booking = Booking::create([
                'property_id' => $property->id,
                'booking_type' => $data['booking_type'],
                'unit_type' => $data['unit_type'],
                'duration_hours' => $data['booking_type'] === 'transit' ? ($data['duration_hours'] ?? 3) : null,
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'] ?? null,
                'customer_phone' => $data['customer_phone'],
                'customer_whatsapp' => $data['customer_whatsapp'] ?? null,
                'check_in' => $checkIn,
                'check_out' => $effectiveCheckOut,
                'guests' => $data['guests'] ?? 1,
                'code' => self::generateCode(),
                'message' => $data['message'] ?? null,
                'status' => 'pending',
                'total_price' => $totalPrice,
                'deposit_amount' => $depositAmount,
                'price_breakdown' => $pricing,
                'metadata' => [
                    'booking_type' => $data['booking_type'],
                    'unit_type' => $data['unit_type'],
                    'nights' => $pricing['nights'],
                    'hours' => $pricing['hours'],
                    'check_in_time' => $data['check_in_time'] ?? null,
                    'unit' => $data['unit'] ?? null,
                    'duration' => $data['duration'] ?? null,
                ],
            ]);

            // Outbound notification must never roll the booking back; dispatch after commit.
            DB::afterCommit(function () use ($booking) {
                BookingNotificationService::send(BookingNotificationService::EVENT_CREATED, $booking);
            });

            return $booking;
        });
    }

    /**
     * Prevent overlapping bookings for the same property + room type.
     */
    protected static function validateAvailability(int $propertyId, string $unitType, Carbon $checkIn, ?Carbon $checkOut, string $bookingType): void
    {
        if ($bookingType === 'transit') {
            return; // transit windows are short; manual handling
        }

        if (!$checkOut) {
            return;
        }

        $conflicting = Booking::where('property_id', $propertyId)
            ->where('unit_type', $unitType)
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($checkIn, $checkOut) {
                $q->where('check_in', '<', $checkOut)
                  ->where('check_out', '>', $checkIn);
            })
            ->exists();

        if ($conflicting) {
            throw new \Exception('Tipe kamar ini sudah dibooking pada tanggal tersebut.');
        }
    }

    /**
     * Confirm a booking.
     */
    public static function confirm(Booking $booking): Booking
    {
        $booking->update(['status' => 'confirmed']);
        BookingNotificationService::send(BookingNotificationService::EVENT_CONFIRMED, $booking);

        return $booking;
    }

    /**
     * Cancel a booking.
     */
    public static function cancel(Booking $booking): Booking
    {
        $booking->update(['status' => 'cancelled']);
        BookingNotificationService::send(BookingNotificationService::EVENT_CANCELLED, $booking);

        return $booking;
    }

    /**
     * Complete a booking (after stay is finished).
     */
    public static function complete(Booking $booking): Booking
    {
        $booking->update(['status' => 'completed']);
        BookingNotificationService::send(BookingNotificationService::EVENT_COMPLETED, $booking);

        return $booking;
    }
}
