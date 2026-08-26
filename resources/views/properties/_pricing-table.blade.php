@php
    /**
     * _pricing-table.blade.php
     *
     * Variables:
     *   $property       — App\Models\Property
     *   $whatsappNumber — string (e.g. "6281234567890")
     *   $primaryColor   — string (hex color, passed from parent or defaulted)
     */
    $primaryColor = $primaryColor ?? \App\Services\SettingsService::get('primary_color', '#3b82f6');

    $waNumber  = preg_replace('/[^0-9]/', '', $whatsappNumber ?? '');
    $waText    = urlencode('Halo, saya tertarik dengan ' . $property->name);
    $waLink    = $waNumber ? 'https://wa.me/' . $waNumber . '?text=' . $waText : null;

    // Collect all unit types that have at least one price set
    $unitTypes = $property->unit_types ?? [];

    // -------------------------------------------------------------------
    // Build pricing rows per booking category
    // -------------------------------------------------------------------

    // Transit rows: each slot shown separately if any unit has a WD or WE price
    $transitSlots = [
        't3'  => 'Transit 3 Jam',
        't6'  => 'Transit 6 Jam',
        't12' => 'Transit 12 Jam',
        't24' => 'Transit 24 Jam',
    ];

    $transitRows = [];
    foreach ($transitSlots as $slot => $label) {
        $wdKey = $slot . '_wd';
        $weKey = $slot . '_we';
        $wdMin = null; $weMin = null;

        foreach ($unitTypes as $type) {
            $wd = (float)($property->prices[$type][$wdKey] ?? 0);
            $we = (float)($property->prices[$type][$weKey] ?? 0);
            if ($wd > 0) $wdMin = $wdMin === null ? $wd : min($wdMin, $wd);
            if ($we > 0) $weMin = $weMin === null ? $we : min($weMin, $we);
        }

        if ($wdMin !== null) {
            $transitRows[] = [
                'label' => $label,
                'wd'    => $wdMin,
                'we'    => $weMin,
            ];
        }
    }

    // Daily row (night_wd / night_we)
    $dailyWd = null; $dailyWe = null;
    foreach ($unitTypes as $type) {
        $wd = (float)($property->prices[$type]['night_wd'] ?? 0);
        $we = (float)($property->prices[$type]['night_we'] ?? 0);
        if ($wd > 0) $dailyWd = $dailyWd === null ? $wd : min($dailyWd, $wd);
        if ($we > 0) $dailyWe = $dailyWe === null ? $we : min($dailyWe, $we);
    }

    // Weekly row
    $weeklyMin = null;
    foreach ($unitTypes as $type) {
        $v = (float)($property->prices[$type]['weekly'] ?? 0);
        if ($v > 0) $weeklyMin = $weeklyMin === null ? $v : min($weeklyMin, $v);
    }

    // Monthly row
    $monthlyMin = null;
    foreach ($unitTypes as $type) {
        $v = (float)($property->prices[$type]['monthly'] ?? 0);
        if ($v > 0) $monthlyMin = $monthlyMin === null ? $v : min($monthlyMin, $v);
    }

    // Check if any weekend prices differ from weekday prices (to decide whether to show WE column)
    $hasWeekendPrices = false;
    foreach ($transitRows as $row) {
        if ($row['we'] !== null && $row['we'] !== $row['wd']) {
            $hasWeekendPrices = true;
            break;
        }
    }
    if (!$hasWeekendPrices && $dailyWe !== null && $dailyWe !== $dailyWd) {
        $hasWeekendPrices = true;
    }

    // Determine if we have anything to show
    $hasAnyPrice = !empty($transitRows) || $dailyWd !== null || $weeklyMin !== null || $monthlyMin !== null;
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
            <h2 class="text-xl font-bold text-gray-900 dark:text-white leading-tight">Daftar Harga</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Harga mulai dari (belum termasuk biaya tambahan)</p>
        </div>
    </div>

    <!-- Pricing table -->
    <div class="overflow-x-auto -mx-2">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 dark:border-gray-700">
                    <th class="text-left py-2 px-2 font-semibold text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wide">Tipe Sewa</th>
                    @if ($hasWeekendPrices)
                        <th class="text-right py-2 px-2 font-semibold text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wide">Weekday</th>
                        <th class="text-right py-2 px-2 font-semibold text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wide">Weekend</th>
                    @else
                        <th class="text-right py-2 px-2 font-semibold text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wide">Harga</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">

                {{-- ===== TRANSIT ROWS ===== --}}
                @foreach ($transitRows as $row)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                        <td class="py-3 px-2">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-md bg-blue-50 dark:bg-blue-900/30 shrink-0">
                                    <svg class="w-3.5 h-3.5 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </span>
                                <span class="font-medium text-gray-800 dark:text-gray-200">{{ $row['label'] }}</span>
                            </div>
                        </td>
                        @if ($hasWeekendPrices)
                            <td class="py-3 px-2 text-right font-semibold text-gray-900 dark:text-white">
                                Rp {{ number_format($row['wd'], 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-2 text-right">
                                @if ($row['we'] !== null && $row['we'] !== $row['wd'])
                                    <span class="font-semibold text-gray-900 dark:text-white">Rp {{ number_format($row['we'], 0, ',', '.') }}</span>
                                @elseif ($row['we'] !== null)
                                    <span class="text-gray-500 dark:text-gray-400 text-xs">Sama</span>
                                @else
                                    <span class="text-gray-400 dark:text-gray-600">—</span>
                                @endif
                            </td>
                        @else
                            <td class="py-3 px-2 text-right font-semibold text-gray-900 dark:text-white">
                                Rp {{ number_format($row['wd'], 0, ',', '.') }}
                            </td>
                        @endif
                    </tr>
                @endforeach

                {{-- ===== DAILY ROW ===== --}}
                @if ($dailyWd !== null)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                        <td class="py-3 px-2">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-md bg-purple-50 dark:bg-purple-900/30 shrink-0">
                                    <svg class="w-3.5 h-3.5 text-purple-500 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                                    </svg>
                                </span>
                                <span class="font-medium text-gray-800 dark:text-gray-200">Per Malam / Harian</span>
                            </div>
                        </td>
                        @if ($hasWeekendPrices)
                            <td class="py-3 px-2 text-right font-semibold text-gray-900 dark:text-white">
                                Rp {{ number_format($dailyWd, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-2 text-right">
                                @if ($dailyWe !== null && $dailyWe !== $dailyWd)
                                    <span class="font-semibold text-gray-900 dark:text-white">Rp {{ number_format($dailyWe, 0, ',', '.') }}</span>
                                @elseif ($dailyWe !== null)
                                    <span class="text-gray-500 dark:text-gray-400 text-xs">Sama</span>
                                @else
                                    <span class="text-gray-400 dark:text-gray-600">—</span>
                                @endif
                            </td>
                        @else
                            <td class="py-3 px-2 text-right font-semibold text-gray-900 dark:text-white">
                                Rp {{ number_format($dailyWd, 0, ',', '.') }}
                            </td>
                        @endif
                    </tr>
                @endif

                {{-- ===== WEEKLY ROW ===== --}}
                @if ($weeklyMin !== null)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                        <td class="py-3 px-2">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-md bg-green-50 dark:bg-green-900/30 shrink-0">
                                    <svg class="w-3.5 h-3.5 text-green-500 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </span>
                                <span class="font-medium text-gray-800 dark:text-gray-200">Per Minggu</span>
                            </div>
                        </td>
                        @if ($hasWeekendPrices)
                            <td class="py-3 px-2 text-right font-semibold text-gray-900 dark:text-white">
                                Rp {{ number_format($weeklyMin, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-2 text-right">
                                <span class="text-gray-400 dark:text-gray-600">—</span>
                            </td>
                        @else
                            <td class="py-3 px-2 text-right font-semibold text-gray-900 dark:text-white">
                                Rp {{ number_format($weeklyMin, 0, ',', '.') }}
                            </td>
                        @endif
                    </tr>
                @endif

                {{-- ===== MONTHLY ROW ===== --}}
                @if ($monthlyMin !== null)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                        <td class="py-3 px-2">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-md bg-orange-50 dark:bg-orange-900/30 shrink-0">
                                    <svg class="w-3.5 h-3.5 text-orange-500 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                </span>
                                <span class="font-medium text-gray-800 dark:text-gray-200">Per Bulan</span>
                            </div>
                        </td>
                        @if ($hasWeekendPrices)
                            <td class="py-3 px-2 text-right font-semibold text-gray-900 dark:text-white">
                                Rp {{ number_format($monthlyMin, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-2 text-right">
                                <span class="text-gray-400 dark:text-gray-600">—</span>
                            </td>
                        @else
                            <td class="py-3 px-2 text-right font-semibold text-gray-900 dark:text-white">
                                Rp {{ number_format($monthlyMin, 0, ',', '.') }}
                            </td>
                        @endif
                    </tr>
                @endif

            </tbody>
        </table>
    </div>

    @if (count($unitTypes) > 1)
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-3 italic">
            * Harga mulai dari — tergantung tipe kamar yang dipilih.
        </p>
    @endif

    {{-- WhatsApp CTA Button --}}
    @if ($waLink)
        <div class="mt-5 pt-5 border-t border-gray-100 dark:border-gray-700">
            <a href="{{ $waLink }}"
               target="_blank"
               rel="noopener noreferrer"
               class="w-full inline-flex items-center justify-center gap-2.5 px-5 py-3.5 rounded-xl font-semibold text-white text-sm hover:opacity-90 active:scale-[0.98] transition-all duration-150 shadow-sm"
               style="background-color: #25d366">
                {{-- WhatsApp SVG icon --}}
                <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                </svg>
                Hubungi via WhatsApp
            </a>
        </div>
    @endif
</div>
@endif
