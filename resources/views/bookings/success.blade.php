@extends('layouts.app')

@section('title', 'Booking Confirmed')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-8">
    <!-- Success Icon -->
    <div class="text-center mb-8">
        <div class="mx-auto w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mb-4">
            <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Booking Request Submitted!</h1>
        <p class="text-gray-600">Your booking request has been received. We will contact you shortly.</p>
    </div>

    <!-- Booking Details Card -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">Booking Details</h2>
        </div>

        <div class="p-6">
            <!-- Booking Code -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Booking Code</label>
                <div class="flex items-center gap-3">
                    <code class="px-3 py-2 bg-gray-100 rounded font-mono text-lg text-gray-800">
                        {{ $booking->code }}
                    </code>
                    <button onclick="copyBookingCode()"
                            class="px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Unit Information -->
            <div class="border-t border-gray-200 pt-6 mb-6">
                <h3 class="font-semibold text-gray-800 mb-3">Unit Information</h3>
                <div class="flex gap-4">
                    @if($booking->unit->featuredImage)
                        <img src="{{ $booking->unit->featuredImage->url }}"
                             alt="{{ $booking->unit->name }}"
                             class="w-24 h-24 object-cover rounded-lg">
                    @else
                        <div class="w-24 h-24 bg-gray-200 rounded-lg flex items-center justify-center">
                            <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                    @endif
                    <div>
                        <h4 class="text-lg font-medium text-gray-900">{{ $booking->unit->name }}</h4>
                        <p class="text-gray-600">{{ $booking->unit->property->name }}</p>
                        <div class="mt-2 text-sm text-gray-500">
                            @if($booking->unit->unit_type)<span class="mr-2">{{ $booking->unit->unit_type }}</span>@endif
                            @if($booking->unit->size_sqm)<span class="mr-2">{{ $booking->unit->size_sqm }} m²</span>@endif
                            @if($booking->unit->bedrooms)<span class="mr-2">{{ $booking->unit->bedrooms }} BR</span>@endif
                            @if($booking->unit->bathrooms)<span>{{ $booking->unit->bathrooms }} BA</span>@endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Booking Dates -->
            <div class="border-t border-gray-200 pt-6 mb-6">
                <h3 class="font-semibold text-gray-800 mb-3">Booking Dates</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <span class="block text-sm text-gray-500">Check-in</span>
                        <span class="font-medium text-gray-900">{{ \Carbon\Carbon::parse($booking->check_in)->format('F d, Y') }}</span>
                    </div>
                    <div>
                        <span class="block text-sm text-gray-500">Check-out</span>
                        <span class="font-medium text-gray-900">{{ \Carbon\Carbon::parse($booking->check_out)->format('F d, Y') }}</span>
                    </div>
                </div>
                <div class="mt-2 text-sm text-gray-600">
                    Total: {{ \Carbon\Carbon::parse($booking->check_in)->diffInDays($booking->check_out) }} nights
                </div>
            </div>

            <!-- Guest Information -->
            <div class="border-t border-gray-200 pt-6 mb-6">
                <h3 class="font-semibold text-gray-800 mb-3">Guest Information</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <span class="block text-sm text-gray-500">Name</span>
                        <span class="font-medium text-gray-900">{{ $booking->customer_name }}</span>
                    </div>
                    <div>
                        <span class="block text-sm text-gray-500">Guests</span>
                        <span class="font-medium text-gray-900">{{ $booking->guests }} guests</span>
                    </div>
                    @if($booking->customer_email)
                        <div class="col-span-2">
                            <span class="block text-sm text-gray-500">Email</span>
                            <span class="font-medium text-gray-900">{{ $booking->customer_email }}</span>
                        </div>
                    @endif
                    @if($booking->customer_phone)
                        <div class="col-span-2">
                            <span class="block text-sm text-gray-500">Phone</span>
                            <span class="font-medium text-gray-900">{{ $booking->customer_phone }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Pricing -->
            <div class="border-t border-gray-200 pt-6 mb-6">
                <h3 class="font-semibold text-gray-800 mb-3">Pricing</h3>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Nightly Rate</span>
                        <span class="font-medium">Rp{{ number_format($booking->total_price / \Carbon\Carbon::parse($booking->check_in)->diffInDays($booking->check_out)) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Number of Nights</span>
                        <span class="font-medium">{{ \Carbon\Carbon::parse($booking->check_in)->diffInDays($booking->check_out) }}</span>
                    </div>
                    <div class="flex justify-between border-t border-gray-200 pt-2">
                        <span class="font-medium text-gray-800">Total Price</span>
                        <span class="font-bold text-lg text-gray-900">Rp{{ number_format($booking->total_price) }}</span>
                    </div>
                    <div class="flex justify-between border-t border-gray-200 pt-2">
                        <span class="text-gray-600">Deposit (30%)</span>
                        <span class="font-medium text-blue-600">Rp{{ number_format($booking->deposit_amount) }}</span>
                    </div>
                </div>
            </div>

            <!-- WhatsApp Action -->
            <div class="border-t border-gray-200 pt-6">
                <h3 class="font-semibold text-gray-800 mb-3">Next Steps</h3>
                <p class="text-sm text-gray-600 mb-4">We will contact you via WhatsApp to confirm your booking.</p>
                <a href="https://wa.me/{{ $booking->customer_whatsapp ?? str_replace(['+', '-', ' ', '(', ')'], '', $booking->customer_phone) }}"
                   target="_blank"
                   class="inline-flex items-center px-6 py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-1.746 6.392 6.392-1.745zm1.439-3.426l-1.438-.804-1.438.804c-2.305 1.287-3.359 3.87-2.802 6.173.556 2.303 2.552 3.936 4.834 3.942 2.283-.006 4.276-1.639 4.832-3.942.557-2.303-.497-4.886-2.802-6.173z"></path>
                    </svg>
                    Contact via WhatsApp
                </a>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="flex flex-col sm:flex-row gap-4">
        <a href="{{ url('/') }}"
           class="flex-1 py-3 px-6 bg-gray-600 text-white font-semibold rounded-lg hover:bg-gray-700 text-center transition">
            Back to Home
        </a>
        <a href="/"
           class="flex-1 py-3 px-6 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 text-center transition">
            Home
        </a>
    </div>
</div>

@push('scripts')
<script>
function copyBookingCode() {
    const code = '{{ $booking->code }}';
    navigator.clipboard.writeText(code).then(() => {
        alert('Booking code copied to clipboard!');
    });
}

@if(session('analytics_event'))
// GA4 / GTM dataLayer push for conversion
window.dataLayer = window.dataLayer || [];
window.dataLayer.push({
    event: '{{ session('analytics_event')['event'] }}',
    booking_id: '{{ session('analytics_event')['booking_id'] }}',
    booking_code: '{{ session('analytics_event')['booking_code'] }}',
    unit_name: '{{ session('analytics_event')['unit_name'] }}',
    property_name: '{{ session('analytics_event')['property_name'] }}',
    value: {{ session('analytics_event')['value'] }},
    currency: '{{ session('analytics_event')['currency'] }}'
});

// Meta Pixel conversion
if (typeof fbq !== 'undefined') {
    fbq('track', 'Purchase', {
        value: {{ session('analytics_event')['value'] }},
        currency: '{{ session('analytics_event')['currency'] }}'
    });
}
@endif
</script>
@endpush
@endsection
