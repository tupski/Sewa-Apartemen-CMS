<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookingRequest;
use App\Models\Booking;
use App\Models\Property;
use App\Models\Voucher;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class BookingController extends Controller
{
    /**
     * Store a newly created booking (from the property page popup).
     */
    public function store(BookingRequest $request)
    {
        try {
            $data = $request->validated();

            // Validate voucher if provided
            if (!empty($data['voucher_code']) || !empty($data['voucher_id'])) {
                $code    = strtoupper($data['voucher_code'] ?? '');
                $voucher = !empty($data['voucher_id'])
                    ? Voucher::find($data['voucher_id'])
                    : Voucher::where('code', $code)->first();

                if (!$voucher || !$voucher->isValid()) {
                    throw new \Exception('Kode voucher tidak valid atau sudah kadaluarsa.');
                }

                $data['voucher_id'] = $voucher->id;
            }

            $booking = BookingService::create($data);

            // Increment voucher used_count after successful booking
            if (!empty($data['voucher_id'])) {
                Voucher::where('id', $data['voucher_id'])->increment('used_count');
            }

            log_activity('booking_created', "Booking {$booking->code} created for {$booking->customer_name}");

            $analyticsEvent = [
                'event' => 'booking_completed',
                'booking_id' => $booking->id,
                'booking_code' => $booking->code,
                'property_name' => $booking->property?->name,
                'value' => (float) $booking->total_price,
                'currency' => 'IDR',
            ];

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'code' => $booking->code,
                    'total' => (float) $booking->total_price,
                    'message' => 'Permintaan booking berhasil dikirim!',
                ]);
            }

            return redirect()
                ->route('bookings.success', $booking)
                ->with('success', 'Booking request submitted successfully! We will contact you shortly.')
                ->with('analytics_event', $analyticsEvent);
        } catch (\Exception $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Show the booking success page (non-JS fallback).
     */
    public function success(Booking $booking): View
    {
        $booking->load('property', 'voucher');

        return view('bookings.success', compact('booking'));
    }

    /**
     * Validate a voucher code for a booking amount (public, throttled).
     */
    public function validateVoucher(Request $request): JsonResponse
    {
        $request->validate([
            'code'        => ['required', 'string', 'max:50'],
            'property_id' => ['required', 'integer', 'exists:properties,id'],
            'amount'      => ['required', 'integer', 'min:0'],
        ]);

        $code    = strtoupper(trim($request->input('code')));
        $amount  = (int) $request->input('amount');
        $voucher = Voucher::where('code', $code)->first();

        if (!$voucher || !$voucher->isValid()) {
            return response()->json([
                'valid'   => false,
                'message' => 'Kode voucher tidak valid atau sudah kadaluarsa.',
            ], 422);
        }

        if ($voucher->min_booking_amount && $amount < $voucher->min_booking_amount) {
            return response()->json([
                'valid'   => false,
                'message' => 'Minimum booking Rp ' . number_format($voucher->min_booking_amount, 0, ',', '.') . ' untuk menggunakan voucher ini.',
            ], 422);
        }

        $discountAmount = $voucher->calculateDiscount($amount);
        $finalAmount    = max(0, $amount - $discountAmount);

        return response()->json([
            'valid'          => true,
            'voucher_id'     => $voucher->id,
            'code'           => $voucher->code,
            'name'           => $voucher->name,
            'discount_type'  => $voucher->discount_type,
            'discount_value' => $voucher->discount_value,
            'discount_amount'=> $discountAmount,
            'final_amount'   => $finalAmount,
        ]);
    }

    /**
     * Public booking status page — accessible by anyone with the booking code.
     */
    public function publicStatus(string $code): View
    {
        $booking = Booking::with('property')
            ->where('code', $code)
            ->firstOrFail();

        return view('bookings.status', compact('booking'));
    }

    /**
     * Display a listing of bookings (admin).
     */
    public function index(Request $request): View
    {
        $query = Booking::with('property');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('unit_type', 'like', "%{$search}%")
                  ->orWhereHas('property', fn ($p) => $p->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($bookingType = $request->get('booking_type')) {
            $query->where('booking_type', $bookingType);
        }

        if ($propertyId = $request->get('property_id')) {
            $query->where('property_id', $propertyId);
        }

        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('check_in', '>=', $dateFrom);
        }
        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('check_out', '<=', $dateTo);
        }

        $bookings = $query->orderBy('created_at', 'desc')->paginate(20)->appends($request->query());
        $properties = Property::orderBy('name')->get();

        return view('admin.bookings.index', compact('bookings', 'properties'));
    }

    /**
     * Display the specified booking (admin).
     */
    public function show(Booking $booking): View
    {
        $booking->load('property');

        return view('admin.bookings.show', compact('booking'));
    }

    /**
     * Confirm a booking.
     */
    public function confirm(Booking $booking): RedirectResponse
    {
        BookingService::confirm($booking);

        log_activity('booking_confirmed', "Booking {$booking->code} confirmed");

        return back()->with('success', 'Booking confirmed successfully.');
    }

    /**
     * Cancel a booking.
     */
    public function cancel(Booking $booking): RedirectResponse
    {
        BookingService::cancel($booking);

        log_activity('booking_cancelled', "Booking {$booking->code} cancelled");

        return back()->with('success', 'Booking cancelled.');
    }

    /**
     * Complete a booking.
     */
    public function complete(Booking $booking): RedirectResponse
    {
        BookingService::complete($booking);

        log_activity('booking_completed', "Booking {$booking->code} completed");

        return back()->with('success', 'Booking marked as completed.');
    }

    /**
     * Update booking notes.
     */
    public function updateNotes(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:10000'],
        ]);

        $booking->update(['notes' => $validated['notes']]);

        return back()->with('success', 'Notes updated.');
    }

    /**
     * Export bookings as CSV.
     */
    public function export(Request $request): Response
    {
        $query = Booking::with('property');

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $bookings = $query->orderBy('created_at', 'desc')->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="bookings-' . now()->format('Ymd') . '.csv"',
        ];

        $columns = ['Code', 'Customer', 'Email', 'Phone', 'Property', 'Room Type', 'Booking Type', 'Check-in', 'Check-out', 'Guests', 'Total', 'Status', 'Notes', 'Created'];

        $fp = fopen('php://temp', 'r+');
        fputcsv($fp, $columns);

        foreach ($bookings as $b) {
            fputcsv($fp, [
                $b->code,
                $b->customer_name,
                $b->customer_email,
                $b->customer_phone,
                $b->property->name ?? '',
                $b->property ? $b->property->typeLabel($b->unit_type) : $b->unit_type,
                ucfirst($b->booking_type),
                $b->check_in?->format('Y-m-d H:i'),
                $b->check_out?->format('Y-m-d H:i'),
                $b->guests,
                $b->total_price,
                $b->status,
                $b->notes,
                $b->created_at->format('Y-m-d H:i'),
            ]);
        }

        rewind($fp);
        $csvContent = stream_get_contents($fp);
        fclose($fp);

        return response($csvContent, 200, $headers);
    }

    /**
     * Remove the specified booking (admin).
     */
    public function destroy(Booking $booking): RedirectResponse
    {
        try {
            BookingService::cancel($booking);

            return redirect()
                ->route('admin.bookings.index')
                ->with('success', 'Booking cancelled successfully.');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to cancel booking: ' . $e->getMessage());
        }
    }
}
