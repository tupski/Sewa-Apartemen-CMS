@extends('layouts.admin')

@section('page-title', 'Bookings')

@section('content')
<div class="w-full">
    <!-- Header with Actions -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Bookings</h2>
            <p class="text-sm text-gray-600 mt-1">Manage customer booking requests</p>
        </div>
        <a href="{{ route('admin.bookings.export', request()->query()) }}" class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 transition">
            Export CSV
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <form method="GET" action="{{ route('admin.bookings.index') }}" class="space-y-3">
            <div class="flex flex-col md:flex-row gap-3">
                <div class="flex-1">
                    <input type="text" name="search" placeholder="Cari kode, nama, atau tipe kamar..." value="{{ request('search') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>
                <div class="w-full md:w-44">
                    <select name="booking_type" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm">
                        <option value="">Semua Tipe Sewa</option>
                        <option value="transit" {{ request('booking_type') == 'transit' ? 'selected' : '' }}>Transit Jam</option>
                        <option value="daily" {{ request('booking_type') == 'daily' ? 'selected' : '' }}>Harian</option>
                        <option value="weekly" {{ request('booking_type') == 'weekly' ? 'selected' : '' }}>Mingguan</option>
                        <option value="monthly" {{ request('booking_type') == 'monthly' ? 'selected' : '' }}>Bulanan</option>
                    </select>
                </div>
                <div class="w-full md:w-44">
                    <select name="property_id" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm">
                        <option value="">Semua Properti</option>
                        @foreach(\App\Models\Property::published()->orderBy('name')->get() as $prop)
                            <option value="{{ $prop->id }}" {{ request('property_id') == $prop->id ? 'selected' : '' }}>{{ $prop->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-full md:w-36">
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                           title="Check-in dari" placeholder="Check-in dari"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>
                <div class="w-full md:w-36">
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                           title="Check-in sampai" placeholder="Check-in sampai"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>
            </div>
            <!-- Status pills -->
            <div class="flex flex-wrap gap-2">
                @php
                    $statusFilters = ['' => ['label' => 'Semua', 'color' => 'gray'], 'pending' => ['label' => 'Pending', 'color' => 'yellow'], 'confirmed' => ['label' => 'Confirmed', 'color' => 'green'], 'cancelled' => ['label' => 'Cancelled', 'color' => 'red'], 'completed' => ['label' => 'Completed', 'color' => 'blue']];
                @endphp
                @foreach($statusFilters as $val => $cfg)
                    <a href="{{ route('admin.bookings.index', array_filter(array_merge(request()->query(), ['status' => $val ?: null, 'page' => null]))) }}"
                       class="px-3 py-1 rounded-full text-xs font-semibold transition border
                              {{ request('status', '') === $val
                                  ? 'bg-blue-600 text-white border-blue-600'
                                  : 'bg-white text-gray-600 border-gray-300 hover:border-gray-400' }}">
                        {{ $cfg['label'] }}
                    </a>
                @endforeach
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 transition">Filter</button>
                @if(request()->hasAny(['search','booking_type','status','property_id','date_from','date_to']))
                    <a href="{{ route('admin.bookings.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-200 transition">Reset</a>
                @endif
            </div>
        </form>
    </div>

    <!-- Bookings Table -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        @if($bookings->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Booking Code</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Room / Property</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dates</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($bookings as $booking)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="{{ route('admin.bookings.show', $booking) }}" class="text-sm font-mono font-medium text-blue-600 hover:text-blue-900">{{ $booking->code }}</a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ $booking->customer_name }}</div>
                                    <div class="text-sm text-gray-500">{{ $booking->customer_phone }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ $booking->property?->typeLabel($booking->unit_type) ?? '-' }}</div>
                                    <div class="text-sm text-gray-500">{{ $booking->property->name ?? '-' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($booking->booking_type === 'transit')
                                        <div class="text-sm text-gray-900">{{ \Carbon\Carbon::parse($booking->check_in)->format('M d, Y H:i') }} · {{ $booking->duration_hours }}h</div>
                                    @else
                                        <div class="text-sm text-gray-900">{{ \Carbon\Carbon::parse($booking->check_in)->format('M d') }} - {{ \Carbon\Carbon::parse($booking->check_out)->format('M d, Y') }}</div>
                                        <div class="text-xs text-gray-500">{{ ucfirst($booking->booking_type) }} · {{ \Carbon\Carbon::parse($booking->check_in)->diffInDays($booking->check_out) }} nights</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">Rp{{ number_format($booking->total_price) }}</div>
                                    <div class="text-xs text-gray-500">Deposit: Rp{{ number_format($booking->deposit_amount) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($booking->status === 'pending')
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>
                                    @elseif($booking->status === 'confirmed')
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Confirmed</span>
                                    @elseif($booking->status === 'cancelled')
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Cancelled</span>
                                    @elseif($booking->status === 'completed')
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Completed</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.bookings.show', $booking) }}" class="text-blue-600 hover:text-blue-900" title="View">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                        </a>
                                        @if($booking->status === 'pending')
                                            <form action="{{ route('admin.bookings.confirm', $booking) }}" method="POST" class="inline" onsubmit="return confirm('Confirm this booking?');">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="text-green-600 hover:text-green-900" title="Confirm">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                </button>
                                            </form>
                                        @endif
                                        @if($booking->status === 'confirmed')
                                            <form action="{{ route('admin.bookings.complete', $booking) }}" method="POST" class="inline" onsubmit="return confirm('Mark as completed?');">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="text-green-600 hover:text-green-900" title="Complete">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                </button>
                                            </form>
                                        @endif
                                        @if($booking->status !== 'cancelled' && $booking->status !== 'completed')
                                            <form action="{{ route('admin.bookings.cancel', $booking) }}" method="POST" class="inline" onsubmit="return confirm('Cancel this booking?');">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="text-red-600 hover:text-red-900" title="Cancel">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $bookings->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No bookings found</h3>
                <p class="mt-1 text-sm text-gray-500">Customer booking requests will appear here.</p>
            </div>
        @endif
    </div>
</div>
@endsection
