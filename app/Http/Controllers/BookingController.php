<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookingRequest;
use App\Models\Booking;
use App\Models\Property;
use App\Models\Unit;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
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

            // Create booking
            $booking = BookingService::create($data);

            return redirect()
                ->route('bookings.success', $booking)
                ->with('success', 'Booking request submitted successfully! We will contact you shortly.');
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
    public function index(): View
    {
        $bookings = Booking::with(['unit.property', 'property'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.bookings.index', compact('bookings'));
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
