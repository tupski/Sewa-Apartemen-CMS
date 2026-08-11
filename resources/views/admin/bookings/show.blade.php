@extends('layouts.admin')

@section('page-title', 'Booking Details - ' . $booking->code)

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-gray-600 mb-2">
            <a href="{{ route('admin.bookings.index') }}" class="hover:text-gray-900">Bookings</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
            <span class="text-gray-900">{{ $booking->code }}</span>
        </div>
        <h2 class="text-2xl font-bold text-gray-800">Booking Details</h2>
    </div>

    <!-- Status Banner -->
    <div class="mb-6">
        @if($booking->status === 'pending')
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-yellow-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-yellow-800">Pending Action Required</p>
                        <p class="text-sm text-yellow-700">This booking needs confirmation before proceeding.</p>
                    </div>
                </div>
            </div>
        @elseif($booking->status === 'confirmed')
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-green-800">Booking Confirmed</p>
                        <p class="text-sm text-green-700">Customer has been notified and unit is reserved.</p>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Booking Details Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column (Booking Info) -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Unit Information -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Unit Information</h3>
                </div>
                <div class="p-6">
                    <div class="flex gap-4">
                        @if($booking->unit->featuredImage)
                            <img src="{{ $booking->unit->featuredImage->url }}"
                                 alt="{{ $booking->unit->name }}"
                                 class="w-32 h-32 object-cover rounded-lg">
                        @else
                            <div class="w-32 h-32 bg-gray-200 rounded-lg flex items-center justify-center">
                                <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                        @endif
                        <div class="flex-1">
                            <h4 class="text-xl font-semibold text-gray-900">{{ $booking->unit->name }}</h4>
                            <p class="text-gray-600">{{ $booking->unit->property->name }}</p>
                            
                            <div class="mt-4 grid grid-cols-2 gap-2 text-sm">
                                @if($booking->unit->unit_type)
                                    <div><span class="font-medium text-gray-500">Type:</span> {{ $booking->unit->unit_type }}</div>
                                @endif
                                @if($booking->unit->size_sqm)
                                    <div><span class="font-medium text-gray-500">Size:</span> {{ $booking->unit->size_sqm }} m²</div>
                                @endif
                                @if($booking->unit->bedrooms)
                                    <div><span class="font-medium text-gray-500">Bedrooms:</span> {{ $booking->unit->bedrooms }}</div>
                                @endif
                                @if($booking->unit->bathrooms)
                                    <div><span class="font-medium text-gray-500">Bathrooms:</span> {{ $booking->unit->bathrooms }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Information -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Customer Information</h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <span class="block text-sm font-medium text-gray-500">Full Name</span>
                            <span class="block text-lg text-gray-900">{{ $booking->customer_name }}</span>
                        </div>
                        <div>
                            <span class="block text-sm font-medium text-gray-500">Phone</span>
                            <span class="block text-lg text-gray-900">{{ $booking->customer_phone }}</span>
                        </div>
                        @if($booking->customer_email)
                            <div class="md:col-span-2">
                                <span class="block text-sm font-medium text-gray-500">Email</span>
                                <span class="block text-gray-900">{{ $booking->customer_email }}</span>
                            </div>
                        @endif
                        @if($booking->customer_whatsapp)
                            <div class="md:col-span-2">
                                <span class="block text-sm font-medium text-gray-500">WhatsApp</span>
                                <a href="https://wa.me/{{ $booking->customer_whatsapp }}"
                                   target="_blank"
                                   class="inline-flex items-center px-3 py-1 bg-green-600 text-white rounded text-sm hover:bg-green-700">
                                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-1.746 6.392 6.392-1.745zm1.439-3.426l-1.438-.804-1.438.804c-2.305 1.287-3.359 3.87-2.802 6.173.556 2.303 2.552 3.936 4.834 3.942 2.283-.006 4.276-1.639 4.832-3.942.557-2.303-.497-4.886-2.802-6.173z"></path>
                                    </svg>
                                    {{ $booking->customer_whatsapp }}
                                </a>
                            </div>
                        @endif
                        @if($booking->message)
                            <div class="md:col-span-2">
                                <span class="block text-sm font-medium text-gray-500">Message</span>
                                <p class="mt-1 text-gray-900 bg-gray-50 p-3 rounded">{{ $booking->message }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Booking Dates -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Booking Dates</h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-2 gap-6">
                        <div class="p-4 bg-blue-50 rounded-lg">
                            <span class="block text-sm font-medium text-blue-600">Check-in</span>
                            <span class="block text-xl font-semibold text-gray-900">
                                {{ \Carbon\Carbon::parse($booking->check_in)->format('F d, Y') }}
                            </span>
                            <span class="block text-sm text-gray-600">
                                {{ \Carbon\Carbon::parse($booking->check_in)->format('l, h:i A') }}
                            </span>
                        </div>
                        <div class="p-4 bg-purple-50 rounded-lg">
                            <span class="block text-sm font-medium text-purple-600">Check-out</span>
                            <span class="block text-xl font-semibold text-gray-900">
                                {{ \Carbon\Carbon::parse($booking->check_out)->format('F d, Y') }}
                            </span>
                            <span class="block text-sm text-gray-600">
                                {{ \Carbon\Carbon::parse($booking->check_out)->format('l, h:i A') }}
                            </span>
                        </div>
                    </div>
                    <div class="mt-4 text-center">
                        <span class="inline-block px-4 py-2 bg-gray-100 rounded-lg text-gray-700 font-medium">
                            {{ \Carbon\Carbon::parse($booking->check_in)->diffInDays($booking->check_out) }} nights total
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column (Actions & Pricing) -->
        <div class="space-y-6">
            <!-- Status & Actions -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Actions</h3>
                </div>
                <div class="p-6 space-y-3">
                    @if($booking->status === 'pending')
                        <form action="{{ route('admin.bookings.confirm', $booking) }}"
                              method="POST"
                              class="space-y-3">
                            @csrf
                            <button type="submit"
                                    class="w-full py-3 px-4 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition flex items-center justify-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Confirm Booking
                            </button>
                        </form>
                        <form action="{{ route('admin.bookings.destroy', $booking) }}"
                              method="POST"
                              class="space-y-3">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    onclick="return confirm('Are you sure you want to cancel this booking? This cannot be undone.');"
                                    class="w-full py-3 px-4 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 transition flex items-center justify-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                Cancel Booking
                            </button>
                        </form>
                    @elseif($booking->status === 'confirmed')
                        <button disabled
                                class="w-full py-3 px-4 bg-gray-300 text-gray-500 font-semibold rounded-lg cursor-not-allowed flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            Already Confirmed
                        </button>
                    @elseif($booking->status === 'cancelled')
                        <button disabled
                                class="w-full py-3 px-4 bg-red-100 text-red-600 font-semibold rounded-lg cursor-not-allowed flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Cancelled
                        </button>
                    @endif
                    <a href="{{ route('admin.bookings.index') }}"
                       class="w-full py-3 px-4 bg-gray-600 text-white font-semibold rounded-lg hover:bg-gray-700 text-center transition">
                        Back to Bookings
                    </a>
                </div>
            </div>

            <!-- Pricing Summary -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Pricing Summary</h3>
                </div>
                <div class="p-6 space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Nightly Rate</span>
                        <span class="font-medium">Rp{{ number_format($booking->total_price / \Carbon\Carbon::parse($booking->check_in)->diffInDays($booking->check_out)) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Number of Nights</span>
                        <span class="font-medium">{{ \Carbon\Carbon::parse($booking->check_in)->diffInDays($booking->check_out) }}</span>
                    </div>
                    <div class="border-t border-gray-200 pt-2 flex justify-between text-sm">
                        <span class="text-gray-600">Total Price</span>
                        <span class="font-medium">Rp{{ number_format($booking->total_price) }}</span>
                    </div>
                    <div class="border-t border-gray-200 pt-2 flex justify-between text-sm">
                        <span class="text-gray-600">Deposit (30%)</span>
                        <span class="font-medium text-blue-600">Rp{{ number_format($booking->deposit_amount) }}</span>
                    </div>
                </div>
            </div>

            <!-- Booking Code -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Booking Reference</h3>
                </div>
                <div class="p-6">
                    <div class="flex items-center gap-3">
                        <code class="flex-1 px-4 py-3 bg-gray-100 rounded-lg font-mono text-center text-lg text-gray-800">
                            {{ $booking->code }}
                        </code>
                        <button onclick="copyBookingCode()"
                                class="px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
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
</script>
@endpush
@endsection