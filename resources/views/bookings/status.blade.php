@extends('layouts.frontend')

@php
    $primaryColor = \App\Services\SettingsService::get('primary_color', '#3b82f6');
    $statusConfig = [
        'pending'   => ['label' => 'Menunggu Konfirmasi', 'color' => 'yellow',  'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'],
        'confirmed' => ['label' => 'Dikonfirmasi',        'color' => 'green',   'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
        'cancelled' => ['label' => 'Dibatalkan',          'color' => 'red',     'icon' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z'],
        'completed' => ['label' => 'Selesai',             'color' => 'blue',    'icon' => 'M5 13l4 4L19 7'],
    ];
    $cfg = $statusConfig[$booking->status] ?? $statusConfig['pending'];
    $colorMap = [
        'yellow' => ['bg' => 'bg-yellow-100 dark:bg-yellow-900/30', 'text' => 'text-yellow-800 dark:text-yellow-300', 'icon' => 'text-yellow-600 dark:text-yellow-400', 'border' => 'border-yellow-200 dark:border-yellow-700'],
        'green'  => ['bg' => 'bg-green-100 dark:bg-green-900/30',   'text' => 'text-green-800 dark:text-green-300',   'icon' => 'text-green-600 dark:text-green-400',   'border' => 'border-green-200 dark:border-green-700'],
        'red'    => ['bg' => 'bg-red-100 dark:bg-red-900/30',       'text' => 'text-red-800 dark:text-red-300',       'icon' => 'text-red-600 dark:text-red-400',       'border' => 'border-red-200 dark:border-red-700'],
        'blue'   => ['bg' => 'bg-blue-100 dark:bg-blue-900/30',     'text' => 'text-blue-800 dark:text-blue-300',     'icon' => 'text-blue-600 dark:text-blue-400',     'border' => 'border-blue-200 dark:border-blue-700'],
    ];
    $colors = $colorMap[$cfg['color']];
    $typeLabels = ['transit' => 'Transit Jam', 'daily' => 'Harian', 'weekly' => 'Mingguan', 'monthly' => 'Bulanan'];
    $typeLabel  = $typeLabels[$booking->booking_type] ?? ucfirst($booking->booking_type ?? '-');
@endphp

@section('title', 'Status Booking ' . $booking->code)

@section('content')
<div class="max-w-2xl mx-auto px-4 py-12">

    <!-- Header -->
    <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-1">Status Booking</h1>
        <p class="text-gray-500 dark:text-gray-400 text-sm">Kode booking kamu:</p>
        <code class="inline-block mt-2 px-4 py-2 bg-gray-100 dark:bg-gray-800 rounded-lg font-mono text-lg font-bold text-gray-800 dark:text-gray-100">
            {{ $booking->code }}
        </code>
    </div>

    <!-- Status Banner -->
    <div class="{{ $colors['bg'] }} {{ $colors['border'] }} border rounded-2xl p-5 flex items-center gap-4 mb-6">
        <div class="shrink-0 w-12 h-12 rounded-full flex items-center justify-center {{ $colors['bg'] }}">
            <svg class="w-7 h-7 {{ $colors['icon'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $cfg['icon'] }}"/>
            </svg>
        </div>
        <div>
            <p class="text-xs font-medium {{ $colors['text'] }} uppercase tracking-wide">Status</p>
            <p class="text-xl font-bold {{ $colors['text'] }}">{{ $cfg['label'] }}</p>
        </div>
    </div>

    <!-- Booking Details Card -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm overflow-hidden mb-6">
        <div class="bg-gray-50 dark:bg-gray-800/50 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">Detail Pemesanan</h2>
        </div>
        <div class="p-6 space-y-4">

            <!-- Property -->
            <div class="flex justify-between gap-4">
                <span class="text-sm text-gray-500 dark:text-gray-400">Properti</span>
                <span class="text-sm font-medium text-gray-900 dark:text-white text-right">
                    {{ $booking->property?->name ?? '-' }}
                </span>
            </div>

            <!-- Room type -->
            <div class="flex justify-between gap-4">
                <span class="text-sm text-gray-500 dark:text-gray-400">Tipe Kamar</span>
                <span class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ $booking->property?->typeLabel($booking->unit_type) ?? strtoupper($booking->unit_type ?? '-') }}
                </span>
            </div>

            <!-- Booking type -->
            <div class="flex justify-between gap-4">
                <span class="text-sm text-gray-500 dark:text-gray-400">Tipe Sewa</span>
                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $typeLabel }}</span>
            </div>

            <!-- Check-in -->
            <div class="flex justify-between gap-4">
                <span class="text-sm text-gray-500 dark:text-gray-400">Check-in</span>
                <span class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ \Carbon\Carbon::parse($booking->check_in)->locale('id')->isoFormat('dddd, D MMMM YYYY HH:mm') }}
                </span>
            </div>

            <!-- Check-out / Duration -->
            @if($booking->booking_type === 'transit')
                <div class="flex justify-between gap-4">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Durasi</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $booking->duration_hours }} jam</span>
                </div>
            @else
                <div class="flex justify-between gap-4">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Check-out</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ \Carbon\Carbon::parse($booking->check_out)->locale('id')->isoFormat('dddd, D MMMM YYYY HH:mm') }}
                    </span>
                </div>
            @endif

            <!-- Guests -->
            <div class="flex justify-between gap-4">
                <span class="text-sm text-gray-500 dark:text-gray-400">Jumlah Tamu</span>
                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $booking->guests }} orang</span>
            </div>

            <!-- Price -->
            <div class="flex justify-between gap-4 pt-3 border-t border-gray-100 dark:border-gray-700">
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Total Harga</span>
                <span class="text-lg font-bold" style="color: {{ $primaryColor }}">
                    Rp {{ number_format((float)$booking->total_price, 0, ',', '.') }}
                </span>
            </div>
        </div>
    </div>

    <!-- Customer Card -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm overflow-hidden mb-6">
        <div class="bg-gray-50 dark:bg-gray-800/50 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">Data Pemesan</h2>
        </div>
        <div class="p-6 space-y-3">
            <div class="flex justify-between gap-4">
                <span class="text-sm text-gray-500 dark:text-gray-400">Nama</span>
                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $booking->customer_name }}</span>
            </div>
            <div class="flex justify-between gap-4">
                <span class="text-sm text-gray-500 dark:text-gray-400">No. HP</span>
                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $booking->customer_phone }}</span>
            </div>
            @if($booking->customer_email)
                <div class="flex justify-between gap-4">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Email</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $booking->customer_email }}</span>
                </div>
            @endif
            <div class="flex justify-between gap-4">
                <span class="text-sm text-gray-500 dark:text-gray-400">Dibuat</span>
                <span class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ $booking->created_at->locale('id')->isoFormat('D MMM YYYY, HH:mm') }}
                </span>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="flex flex-col sm:flex-row gap-3">
        <a href="{{ route('properties.public.index') }}"
           class="flex-1 py-3 px-6 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 font-semibold rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 text-center transition text-sm">
            Lihat Apartemen Lain
        </a>
        @if($booking->property)
            <a href="{{ route('properties.public.show', $booking->property->slug) }}"
               class="flex-1 py-3 px-6 text-white font-semibold rounded-xl hover:opacity-90 text-center transition text-sm"
               style="background-color: {{ $primaryColor }}">
                Kembali ke Properti
            </a>
        @endif
    </div>

    <!-- Lookup another booking -->
    <div class="mt-8 bg-gray-50 dark:bg-gray-800/50 rounded-2xl p-5">
        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Cek booking lain</p>
        <form action="" method="GET" class="flex gap-2"
              onsubmit="event.preventDefault(); window.location='{{ route('bookings.status', '__TOKEN__') }}'.replace('__TOKEN__', this.code.value.trim())">
            <input type="text" name="code" placeholder="Masukkan token akses booking..."
                   class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 text-sm focus:outline-none focus:ring-2"
                   style="--tw-ring-color: {{ $primaryColor }}">
            <button type="submit"
                    class="px-5 py-2.5 text-white text-sm font-semibold rounded-xl hover:opacity-90 transition"
                    style="background-color: {{ $primaryColor }}">
                Cek
            </button>
        </form>
    </div>

</div>
@endsection
