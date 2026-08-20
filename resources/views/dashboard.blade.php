@extends('layouts.admin')

@section('page-title', 'Dashboard')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Today's Operational Metrics -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <a href="{{ route('admin.bookings.index', ['date_from' => now()->format('Y-m-d'), 'date_to' => now()->format('Y-m-d')]) }}"
           class="bg-white rounded-lg shadow-sm p-5 hover:shadow-md transition border-l-4 border-indigo-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Booking Hari Ini</p>
                    <p class="text-3xl font-bold text-gray-800 mt-1">{{ $todayBookings }}</p>
                </div>
                <svg class="w-9 h-9 text-indigo-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
            </div>
        </a>

        <a href="{{ route('admin.bookings.index', ['status' => 'pending']) }}"
           class="bg-white rounded-lg shadow-sm p-5 hover:shadow-md transition border-l-4 border-yellow-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Pending Konfirmasi</p>
                    <p class="text-3xl font-bold text-gray-800 mt-1">{{ $pendingCount }}</p>
                </div>
                <svg class="w-9 h-9 text-yellow-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
            @if($pendingCount > 0)
                <p class="text-xs text-yellow-600 font-medium mt-2">Butuh tindakan!</p>
            @endif
        </a>

        <a href="{{ route('admin.bookings.index', ['date_from' => now()->format('Y-m-d'), 'date_to' => now()->format('Y-m-d')]) }}"
           class="bg-white rounded-lg shadow-sm p-5 hover:shadow-md transition border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Check-in Hari Ini</p>
                    <p class="text-3xl font-bold text-gray-800 mt-1">{{ $checkinToday }}</p>
                </div>
                <svg class="w-9 h-9 text-green-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                </svg>
            </div>
        </a>

        <a href="{{ route('admin.bookings.index') }}"
           class="bg-white rounded-lg shadow-sm p-5 hover:shadow-md transition border-l-4 border-orange-400">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Check-out Hari Ini</p>
                    <p class="text-3xl font-bold text-gray-800 mt-1">{{ $checkoutToday }}</p>
                </div>
                <svg class="w-9 h-9 text-orange-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
            </div>
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4 mb-8">
        <a href="{{ route('admin.properties.index') }}" class="bg-white rounded-lg shadow-sm p-5 hover:shadow-md transition border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 font-medium">Properties</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $totalProperties }}</p>
                </div>
                <svg class="w-10 h-10 text-blue-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
            </div>
        </a>

        <a href="{{ route('admin.properties.index') }}" class="bg-white rounded-lg shadow-sm p-5 hover:shadow-md transition border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 font-medium">Room Types</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $totalUnits }}</p>
                </div>
                <svg class="w-10 h-10 text-green-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
            </div>
        </a>

        <a href="{{ route('admin.bookings.index') }}" class="bg-white rounded-lg shadow-sm p-5 hover:shadow-md transition border-l-4 border-yellow-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 font-medium">Bookings</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $totalBookings }}</p>
                </div>
                <svg class="w-10 h-10 text-yellow-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
            </div>
        </a>

        <a href="{{ route('admin.users.index') }}" class="bg-white rounded-lg shadow-sm p-5 hover:shadow-md transition border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 font-medium">Users</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $totalUsers }}</p>
                </div>
                <svg class="w-10 h-10 text-purple-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
            </div>
        </a>

        <a href="{{ route('admin.posts.index') }}" class="bg-white rounded-lg shadow-sm p-5 hover:shadow-md transition border-l-4 border-red-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 font-medium">Posts</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $totalPosts }}</p>
                </div>
                <svg class="w-10 h-10 text-red-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path>
                </svg>
            </div>
        </a>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-lg shadow-sm p-5 mb-8">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Quick Actions</h3>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.properties.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Create Property
            </a>
            <a href="{{ route('admin.bookings.index') }}" class="inline-flex items-center px-4 py-2 bg-yellow-600 text-white text-sm font-medium rounded-md hover:bg-yellow-700 transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                View Bookings
            </a>
            <a href="{{ route('admin.posts.create') }}" class="inline-flex items-center px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-md hover:bg-purple-700 transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Create Post
            </a>
            <a href="{{ route('admin.users.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-md hover:bg-gray-700 transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Create User
            </a>
        </div>
    </div>

    <!-- Occupancy Rate + Chart Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Occupancy Rate Card -->
        <div class="bg-white rounded-lg shadow-sm p-5">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Occupancy Rate</h3>
            <div class="text-center">
                <p class="text-4xl font-bold text-gray-800">{{ $occupancyRate }}%</p>
                <p class="text-sm text-gray-500 mt-1">Confirmed / Room Types</p>
                <div class="w-full bg-gray-200 rounded-full h-3 mt-4">
                    <div class="bg-green-500 h-3 rounded-full" style="width: {{ $occupancyRate }}%"></div>
                </div>
            </div>
        </div>

        <!-- Monthly Bookings Chart -->
        <div class="lg:col-span-2 bg-white rounded-lg shadow-sm p-5">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Monthly Bookings (Last 6 Months)</h3>
            <div class="space-y-3">
                @foreach($bookingChartLabels as $i => $label)
                    @php $count = $bookingChartValues[$i]; $pct = $maxValue > 0 ? ($count / $maxValue) * 100 : 0; @endphp
                    <div class="flex items-center gap-3">
                        <span class="w-10 text-xs text-gray-500 font-medium">{{ $label }}</span>
                        <div class="flex-1 bg-gray-100 rounded-full h-6 overflow-hidden">
                            <div class="bg-blue-500 h-full rounded-full flex items-center justify-end pr-2 transition-all" style="width: {{ max($pct, $count > 0 ? 5 : 0) }}%">
                                @if($count > 0)
                                    <span class="text-xs text-white font-bold">{{ $count }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Recent Bookings + Recent Properties -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Recent Bookings Table -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="font-semibold text-gray-800">Recent Bookings</h3>
                <a href="{{ route('admin.bookings.index') }}" class="text-sm text-blue-600 hover:text-blue-800">View All</a>
            </div>
            @if($recentBookings->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Room</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Guest</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($recentBookings as $booking)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        <a href="{{ route('admin.bookings.show', $booking) }}" class="text-sm font-mono text-blue-600">{{ $booking->code }}</a>
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700">{{ $booking->property?->typeLabel($booking->unit_type) ?? '-' }} · {{ $booking->property?->name }}</td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700">{{ $booking->customer_name }}</td>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        @if($booking->status === 'pending')
                                            <span class="px-2 py-0.5 text-xs rounded-full bg-yellow-100 text-yellow-800">Pending</span>
                                        @elseif($booking->status === 'confirmed')
                                            <span class="px-2 py-0.5 text-xs rounded-full bg-blue-100 text-blue-800">Confirmed</span>
                                        @elseif($booking->status === 'cancelled')
                                            <span class="px-2 py-0.5 text-xs rounded-full bg-red-100 text-red-800">Cancelled</span>
                                        @elseif($booking->status === 'completed')
                                            <span class="px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-800">Completed</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-sm text-gray-500 text-center py-6">No recent bookings.</p>
            @endif
        </div>

        <!-- Recent Properties -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="font-semibold text-gray-800">Recent Properties</h3>
                <a href="{{ route('admin.properties.index') }}" class="text-sm text-blue-600 hover:text-blue-800">View All</a>
            </div>
            @if($recentProperties->count() > 0)
                <div class="divide-y divide-gray-200">
                    @foreach($recentProperties as $property)
                        <div class="px-5 py-3 hover:bg-gray-50 flex items-center justify-between">
                            <div>
                                <a href="{{ route('admin.properties.show', $property) }}" class="text-sm font-medium text-gray-800 hover:text-blue-600">{{ $property->name }}</a>
                                <p class="text-xs text-gray-500">{{ $property->city ?? '' }}{{ $property->city && $property->province ? ', ' : '' }}{{ $property->province ?? '' }}</p>
                            </div>
                            <span class="text-xs text-gray-400">{{ $property->created_at->format('M d') }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-500 text-center py-6">No properties yet.</p>
            @endif
        </div>
    </div>
</div>
@endsection
