@extends('layouts.frontend')

@php
    $primaryColor = \App\Services\SettingsService::get('primary_color', '#3b82f6');
    $secondaryColor = \App\Services\SettingsService::get('secondary_color', '#10b981');
    $whatsapp = \App\Services\SettingsService::get('whatsapp_default', '');
@endphp

@section('content')
    <!-- Page Header -->
    <section class="py-14 md:py-20 text-white"
             style="background: linear-gradient(135deg, {{ $primaryColor }} 0%, {{ $secondaryColor }} 100%);">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <a href="{{ url('/') }}" class="text-sm text-white/80 hover:text-white inline-flex items-center mb-3">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                {{ __('nav.home') }}
            </a>
            <h1 class="text-3xl md:text-5xl font-extrabold">{{ __('promo.title') }}</h1>
            <p class="mt-3 text-white/90 max-w-2xl">{{ __('promo.subtitle') }}</p>
        </div>
    </section>

    <section class="py-12 bg-gray-50 dark:bg-gray-800/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if ($vouchers->isEmpty())
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-12 text-center">
                    <div class="mx-auto w-16 h-16 rounded-full flex items-center justify-center mb-4 bg-gray-100 dark:bg-gray-700">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5a1.99 1.99 0 011.414.586l7 7a2 2 0 010 2.828l-5 5a2 2 0 01-2.828 0l-7-7A1.99 1.99 0 013 12V7a4 4 0 014-4z"/></svg>
                    </div>
                    <p class="text-lg font-medium text-gray-700 dark:text-gray-300">{{ __('promo.empty') }}</p>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($vouchers as $voucher)
                        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-sm overflow-hidden flex flex-col">
                            <!-- Ribbon / discount badge -->
                            <div class="px-6 pt-6 pb-4 text-white" style="background: linear-gradient(135deg, {{ $primaryColor }} 0%, {{ $secondaryColor }} 100%);">
                                <p class="text-sm font-medium opacity-90">{{ $voucher->name }}</p>
                                <p class="mt-1 text-3xl font-extrabold">
                                    @if ($voucher->discount_type === 'percent')
                                        {{ rtrim(rtrim(number_format($voucher->discount_value, 2, ',', '.'), '0'), ',') }}%
                                    @else
                                        Rp {{ number_format((int) $voucher->discount_value, 0, ',', '.') }}
                                    @endif
                                    <span class="text-base font-semibold">{{ __('promo.off') }}</span>
                                </p>
                            </div>

                            <div class="p-6 flex-1 flex flex-col">
                                <!-- Code -->
                                <div class="flex items-center gap-2 mb-4">
                                    <span class="text-xs uppercase tracking-wider text-gray-400">{{ __('promo.code') }}</span>
                                    <span class="inline-flex items-center px-3 py-1 rounded-lg border border-dashed border-gray-300 dark:border-gray-600 font-mono font-bold text-gray-900 dark:text-white">{{ $voucher->code }}</span>
                                </div>

                                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400 flex-1">
                                    @if ($voucher->min_booking_amount)
                                        <li class="flex items-start gap-2">
                                            <svg class="w-4 h-4 mt-0.5 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <span>{{ __('promo.min_booking', ['amount' => 'Rp ' . number_format($voucher->min_booking_amount, 0, ',', '.')]) }}</span>
                                        </li>
                                    @endif
                                    @if ($voucher->discount_type === 'percent' && $voucher->max_discount_amount)
                                        <li class="flex items-start gap-2">
                                            <svg class="w-4 h-4 mt-0.5 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <span>{{ __('promo.max_discount', ['amount' => 'Rp ' . number_format($voucher->max_discount_amount, 0, ',', '.')]) }}</span>
                                        </li>
                                    @endif
                                    @if ($voucher->valid_until)
                                        <li class="flex items-start gap-2">
                                            <svg class="w-4 h-4 mt-0.5 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            <span>{{ __('promo.valid_until', ['date' => $voucher->valid_until->translatedFormat('d M Y')]) }}</span>
                                        </li>
                                    @endif
                                </ul>

                                @if ($whatsapp)
                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $whatsapp) }}?text={{ urlencode(__('promo.wa_message', ['code' => $voucher->code])) }}"
                                       target="_blank" rel="noopener"
                                       class="mt-5 inline-flex items-center justify-center px-4 py-2.5 rounded-full text-sm font-semibold text-white transition hover:opacity-90"
                                       style="background-color: {{ $primaryColor }}">
                                        {{ __('promo.claim') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection
