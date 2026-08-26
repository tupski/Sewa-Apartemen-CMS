@php
    /**
     * _pricing-table.blade.php
     *
     * Variables:
     *   $property       — App\Models\Property
     *   $whatsappNumber — string (e.g. "6281234567890")
     *   $primaryColor   — string (hex color, passed from parent or defaulted)
     *
     * Displays ONE dynamic price per row (Durasi Sewa | Harga | Booking button).
     * The price shown automatically reflects weekday/weekend based on the current
     * server date in Asia/Jakarta and the property's weekend-day configuration.
     * Each row has a WhatsApp "Booking" button with a duration-specific message.
     */
    $primaryColor = $primaryColor ?? \App\Services\SettingsService::get('primary_color', '#3b82f6');

    $waNumber  = preg_replace('/[^0-9]/', '', $whatsappNumber ?? '');

    // Collect all unit types that have at least one price set
    $unitTypes = $property->unit_types ?? [];

    // -------------------------------------------------------------------
    // Determine weekday/weekend for TODAY in Asia/Jakarta (GMT+7)
    // -------------------------------------------------------------------
    $todayJakarta   = \Carbon\Carbon::now()->setTimezone('Asia/Jakarta');
    $isWeekendToday = in_array($todayJakarta->dayOfWeek, $property->weekendDays(), true); // 0=Sun..6=Sat
    $suffix         = $isWeekendToday ? '_we' : '_wd';

    // Pick the cheapest applicable price across unit types for a given base key.
    // Falls back to the opposite weekday/weekend value if the applicable one is empty.
    $pickMin = function (string $base) use ($property, $unitTypes, $suffix) {
        $otherSuffix = $suffix === '_we' ? '_wd' : '_we';
        $min = null;
        foreach ($unitTypes as $type) {
            $v = (float) ($property->prices[$type][$base . $suffix] ?? 0);
            if ($v <= 0) {
                $v = (float) ($property->prices[$type][$base . $otherSuffix] ?? 0);
            }
            if ($v > 0) {
                $min = $min === null ? $v : min($min, $v);
            }
        }
        return $min;
    };

    // Pick the cheapest flat price (no weekday/weekend split) for a given key.
    $pickMinFlat = function (string $key) use ($property, $unitTypes) {
        $min = null;
        foreach ($unitTypes as $type) {
            $v = (float) ($property->prices[$type][$key] ?? 0);
            if ($v > 0) {
                $min = $min === null ? $v : min($min, $v);
            }
        }
        return $min;
    };

    // -------------------------------------------------------------------
    // Build the display rows: label + single dynamic price
    // -------------------------------------------------------------------
    $rows = [];

    // Transit hour slots
    foreach ([3, 6, 9, 12, 24] as $hours) {
        $price = $pickMin('t' . $hours);
        if ($price !== null) {
            $rows[] = [
                'label' => __('prop.duration_hours', ['count' => $hours]),
                'price' => $price,
            ];
        }
    }

    // Daily (per night)
    if (($daily = $pickMin('night')) !== null) {
        $rows[] = ['label' => __('prop.duration_daily'), 'price' => $daily];
    }

    // Weekly (flat)
    if (($weekly = $pickMinFlat('weekly')) !== null) {
        $rows[] = ['label' => __('prop.duration_weekly'), 'price' => $weekly];
    }

    // Monthly (flat)
    if (($monthly = $pickMinFlat('monthly')) !== null) {
        $rows[] = ['label' => __('prop.duration_monthly'), 'price' => $monthly];
    }

    $hasAnyPrice = count($rows) > 0;
@endphp

@if ($hasAnyPrice)
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-6 md:p-8">
    <!-- Section header -->
    <div class="flex items-center gap-3 mb-5">
        <div class="w-9 h-9 rounded-full flex items-center justify-center shrink-0" style="background-color: {{ $primaryColor }}1a">
            <svg class="w-5 h-5" style="color: {{ $primaryColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white leading-tight">{{ __('prop.price_list') }}</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                {{ __('prop.price_today_note', ['daytype' => $isWeekendToday ? __('prop.weekend') : __('prop.weekday')]) }}
            </p>
        </div>
    </div>

    <!-- Pricing table -->
    <div class="overflow-x-auto -mx-2">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 dark:border-gray-700">
                    <th class="text-left py-2 px-2 font-semibold text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wide">{{ __('prop.rental_duration') }}</th>
                    <th class="text-right py-2 px-2 font-semibold text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wide">{{ __('prop.price') }}</th>
                    <th class="text-right py-2 px-2 font-semibold text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wide"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                @foreach ($rows as $row)
                    @php
                        $waMessage = __('prop.wa_booking_message', [
                            'property' => $property->name,
                            'duration' => $row['label'],
                        ]);
                        $waLink = $waNumber ? 'https://wa.me/' . $waNumber . '?text=' . urlencode($waMessage) : null;
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                        <td class="py-3 px-2">
                            <span class="font-medium text-gray-800 dark:text-gray-200">{{ $row['label'] }}</span>
                        </td>
                        <td class="py-3 px-2 text-right font-semibold text-gray-900 dark:text-white whitespace-nowrap">
                            Rp {{ number_format($row['price'], 0, ',', '.') }}
                        </td>
                        <td class="py-3 px-2 text-right">
                            @if ($waLink)
                                <a href="{{ $waLink }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg font-semibold text-white text-xs hover:opacity-90 active:scale-[0.98] transition-all duration-150 shadow-sm whitespace-nowrap"
                                   style="background-color: #25d366"
                                   aria-label="{{ __('prop.booking') }} — {{ $row['label'] }}">
                                    <i class="fa-brands fa-whatsapp text-sm" aria-hidden="true"></i>
                                    {{ __('prop.booking') }}
                                </a>
                            @else
                                <span class="text-gray-400 dark:text-gray-600">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if (count($unitTypes) > 1)
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-3 italic">
            {{ __('prop.price_from_note') }}
        </p>
    @endif
</div>
@endif
