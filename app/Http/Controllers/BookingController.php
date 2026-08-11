<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookingRequest;
use App\Models\Booking;
use App\Models\Property;
use App\Models\Unit;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BookingController extends Controller
{
    /**
     * Show the booking form.
     */
    public function create(Unit $unit): View
    {
        $unit->load('property');

        return view('bookings.create', compact('unit'));
    }

    /**
     * Store a newly created booking.
     */
    public function store(BookingRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();

            $booking = BookingService::create($data);

            log_activity('booking_created', "Booking {$booking->code} created for {$booking->customer_name}");

            $analyticsEvent = [
                'event' => 'booking_completed',
                'booking_id' => $booking->id,
                'booking_code' => $booking->code,
                'unit_name' => $booking->unit?->name,
                'property_name' => $booking->property?->name ?? $booking->unit?->property?->name,
                'value' => (float) $booking->total_price,
                'currency' => 'IDR',
            ];

            return redirect()
                ->route('bookings.success', $booking)
                ->with('success', 'Booking request submitted successfully! We will contact you shortly.')
                ->with('analytics_event', $analyticsEvent);
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Show the booking success page.
     */
    public function success(Booking $booking): View
    {
        $booking->load('unit.property');

        return view('bookings.success', compact('booking'));
    }

    /**
     * Display a listing of bookings (admin).
     */
    public function index(Request $request): View
    {
        $query = Booking::with(['unit.property', 'property']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhereHas('unit', fn ($u) => $u->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('property', fn ($p) => $p->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
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
        $booking->load(['unit.property', 'property']);

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
    public function export(Request $request): \Illuminate\Http\Response
    {
        $query = Booking::with(['unit.property', 'property']);

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $bookings = $query->orderBy('created_at', 'desc')->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="bookings-' . now()->format('Ymd') . '.csv"',
        ];

        $columns = ['Code', 'Customer', 'Email', 'Phone', 'Unit', 'Property', 'Check-in', 'Check-out', 'Guests', 'Total', 'Status', 'Notes', 'Created'];

        $fp = fopen('php://temp', 'r+');
        fputcsv($fp, $columns);

        foreach ($bookings as $b) {
            fputcsv($fp, [
                $b->code,
                $b->customer_name,
                $b->customer_email,
                $b->customer_phone,
                $b->unit->name ?? '',
                $b->property->name ?? $b->unit->property->name ?? '',
                $b->check_in->format('Y-m-d'),
                $b->check_out->format('Y-m-d'),
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
