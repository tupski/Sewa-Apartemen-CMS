@extends('layouts.app')

@section('title', 'Book ' . $unit->name)

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <!-- Header -->
    <div class="text-center mb-8">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2">Book {{ $unit->name }}</h1>
        <p class="text-gray-600">Complete your booking request for {{ $unit->property->name }}</p>
    </div>

    <!-- Unit Info Card -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <div class="flex flex-col md:flex-row gap-6">
            <!-- Unit Image -->
            <div class="w-full md:w-48 flex-shrink-0">
                @if($unit->featuredImage)
                    <img src="{{ $unit->featuredImage->url }}" 
                         alt="{{ $unit->name }}"
                         class="w-full h-48 object-cover rounded-lg">
                @else
                    <div class="w-full h-48 bg-gray-200 rounded-lg flex items-center justify-center">
                        <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                @endif
            </div>

            <!-- Unit Details -->
            <div class="flex-1">
                <h2 class="text-xl font-semibold text-gray-900 mb-2">{{ $unit->name }}</h2>
                <div class="grid grid-cols-2 gap-2 text-sm text-gray-600">
                    @if($unit->unit_type)
                        <div><span class="font-medium">Type:</span> {{ $unit->unit_type }}</div>
                    @endif
                    @if($unit->size_sqm)
                        <div><span class="font-medium">Size:</span> {{ $unit->size_sqm }} m²</div>
                    @endif
                    @if($unit->bedrooms)
                        <div><span class="font-medium">Bedrooms:</span> {{ $unit->bedrooms }}</div>
                    @endif
                    @if($unit->bathrooms)
                        <div><span class="font-medium">Bathrooms:</span> {{ $unit->bathrooms }}</div>
                    @endif
                </div>

                <!-- Price Info -->
                <div class="mt-4 p-4 bg-blue-50 rounded-lg">
                    <p class="text-sm text-gray-600 mb-1">Nightly Rate</p>
                    <p class="text-2xl font-bold text-blue-600">Rp{{ number_format($unit->price_per_night) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Form -->
    <div class="bg-white rounded-lg shadow-sm">
        <form method="POST" action="{{ route('bookings.store') }}" class="p-6">
            @csrf
            
            <!-- Hidden Fields -->
            <input type="hidden" name="unit_id" value="{{ $unit->id }}">
            <input type="hidden" name="property_id" value="{{ $unit->property_id }}">

            <div class="space-y-6">
                <!-- Customer Information -->
                <div class="border-b border-gray-200 pb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Customer Information</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="customer_name" class="block text-sm font-medium text-gray-700 mb-2">
                                Full Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   name="customer_name"
                                   id="customer_name"
                                   value="{{ old('customer_name') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 @error('customer_name') border-red-500 @enderror"
                                   required>
                            @error('customer_name')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="customer_email" class="block text-sm font-medium text-gray-700 mb-2">
                                Email Address
                            </label>
                            <input type="email"
                                   name="customer_email"
                                   id="customer_email"
                                   value="{{ old('customer_email') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 @error('customer_email') border-red-500 @enderror">
                            @error('customer_email')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="customer_phone" class="block text-sm font-medium text-gray-700 mb-2">
                                Phone Number <span class="text-red-500">*</span>
                            </label>
                            <input type="tel"
                                   name="customer_phone"
                                   id="customer_phone"
                                   value="{{ old('customer_phone') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 @error('customer_phone') border-red-500 @enderror"
                                   placeholder="+6281234567890"
                                   required>
                            @error('customer_phone')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="customer_whatsapp" class="block text-sm font-medium text-gray-700 mb-2">
                                WhatsApp Number
                            </label>
                            <input type="tel"
                                   name="customer_whatsapp"
                                   id="customer_whatsapp"
                                   value="{{ old('customer_whatsapp') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 @error('customer_whatsapp') border-red-500 @enderror"
                                   placeholder="+6281234567890">
                            @error('customer_whatsapp')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Booking Dates -->
                <div class="border-b border-gray-200 pb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Booking Dates</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="check_in" class="block text-sm font-medium text-gray-700 mb-2">
                                Check-in Date <span class="text-red-500">*</span>
                            </label>
                            <input type="date"
                                   name="check_in"
                                   id="check_in"
                                   value="{{ old('check_in') }}"
                                   min="{{ now()->format('Y-m-d') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 @error('check_in') border-red-500 @enderror"
                                   required>
                            @error('check_in')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="check_out" class="block text-sm font-medium text-gray-700 mb-2">
                                Check-out Date <span class="text-red-500">*</span>
                            </label>
                            <input type="date"
                                   name="check_out"
                                   id="check_out"
                                   value="{{ old('check_out') }}"
                                   min="{{ now()->format('Y-m-d') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 @error('check_out') border-red-500 @enderror"
                                   required>
                            @error('check_out')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Price Summary -->
                    <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <h4 class="font-semibold text-gray-800 mb-3">Price Summary</h4>
                        <div class="flex justify-between mb-2">
                            <span class="text-gray-600">Nightly Rate:</span>
                            <span class="font-medium">Rp{{ number_format($unit->price_per_night) }}</span>
                        </div>
                        <div class="flex justify-between mb-2">
                            <span class="text-gray-600">Number of Nights:</span>
                            <span class="font-medium" id="num-nights">0</span>
                        </div>
                        <div class="flex justify-between mb-2 border-t border-gray-200 pt-2">
                            <span class="text-gray-800 font-medium">Total:</span>
                            <span class="text-lg font-bold text-gray-900" id="total-price">Rp0</span>
                        </div>
                    </div>
                </div>

                <!-- Number of Guests -->
                <div class="border-b border-gray-200 pb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Guests</h3>
                    
                    <div>
                        <label for="guests" class="block text-sm font-medium text-gray-700 mb-2">
                            Number of Guests <span class="text-red-500">*</span>
                        </label>
                        <input type="number"
                               name="guests"
                               id="guests"
                               value="{{ old('guests', 1) }}"
                               min="1"
                               max="20"
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 @error('guests') border-red-500 @enderror"
                               required>
                        @error('guests')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-gray-500 mt-1">Maximum 20 guests per booking</p>
                    </div>
                </div>

                <!-- Additional Message -->
                <div class="pb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Additional Information</h3>
                    
                    <div>
                        <label for="message" class="block text-sm font-medium text-gray-700 mb-2">
                            Special Requests or Messages
                        </label>
                        <textarea name="message"
                                  id="message"
                                  rows="4"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 @error('message') border-red-500 @enderror"
                                  placeholder="Any special requests, arrival time preferences, etc.">{{ old('message') }}</textarea>
                        @error('message')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Terms and Conditions -->
            <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <h4 class="font-semibold text-yellow-800 mb-2">Terms and Conditions</h4>
                <ul class="text-sm text-yellow-700 space-y-1">
                    <li>• A 30% deposit is required to confirm your booking</li>
                    <li>• Full payment is due upon arrival</li>
                    <li>• Check-in time: 2:00 PM, Check-out time: 12:00 PM</li>
                    <li>• Cancellation policy: Free cancellation up to 3 days before check-in</li>
                </ul>
            </div>

            <!-- Submit Button -->
            <button type="submit"
                    class="w-full py-3 px-6 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
                Submit Booking Request
            </button>
        </form>
    </div>
</div>

@push('scripts')
<script>
    // Price calculation
    const pricePerNight = {{ $unit->price_per_night }};
    
    function calculatePrice() {
        const checkIn = document.getElementById('check_in').value;
        const checkOut = document.getElementById('check_out').value;
        
        if (checkIn && checkOut) {
            const startDate = new Date(checkIn);
            const endDate = new Date(checkOut);
            const diffTime = endDate - startDate;
            const diffNights = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            
            if (diffNights > 0) {
                document.getElementById('num-nights').textContent = diffNights;
                const total = pricePerNight * diffNights;
                document.getElementById('total-price').textContent = 'Rp' + total.toLocaleString('id-ID');
            } else {
                document.getElementById('num-nights').textContent = '0';
                document.getElementById('total-price').textContent = 'Rp0';
            }
        }
    }
    
    // Update price on date changes
    document.getElementById('check_in').addEventListener('change', calculatePrice);
    document.getElementById('check_out').addEventListener('change', calculatePrice);
</script>
@endpush
@endsection