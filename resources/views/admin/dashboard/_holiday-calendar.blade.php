{{--
    National-holiday calendar (admin dashboard).

    Reads ONLY the cached `national_holidays` table via
    NationalHolidayService::forMonth() — no API call happens during render.
    Month navigation is a plain server-rendered link (`?holiday_month=YYYY-MM`),
    so it works under Turbo Drive without any JS.

    Expects:
      $holidayMonth      Carbon  first day of the displayed month
      $holidays          Collection<string, NationalHoliday> keyed by Y-m-d
      $upcomingHolidays  Collection<int, NationalHoliday>
--}}
@php
    $firstDay   = $holidayMonth->copy()->startOfMonth();
    $daysInMonth = $firstDay->daysInMonth;
    // Monday-first grid: Carbon's dayOfWeek is 0=Sunday, so shift by one.
    $leadingBlanks = ($firstDay->dayOfWeek + 6) % 7;
    $todayString   = now()->toDateString();
    $prevMonth     = $firstDay->copy()->subMonth()->format('Y-m');
    $nextMonth     = $firstDay->copy()->addMonth()->format('Y-m');
    $weekdayLabels = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
@endphp

<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm" data-testid="holiday-calendar">
    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between gap-3">
        <h3 class="font-semibold text-gray-800 dark:text-white text-sm">{{ __('holiday.title') }}</h3>
        <div class="flex items-center gap-1">
            <a href="{{ route('dashboard', ['holiday_month' => $prevMonth]) }}"
               class="p-1.5 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-700 transition"
               aria-label="{{ __('holiday.prev_month') }}" title="{{ __('holiday.prev_month') }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <span class="text-xs font-semibold text-gray-600 dark:text-gray-300 min-w-[7.5rem] text-center">
                {{ $firstDay->translatedFormat('F Y') }}
            </span>
            <a href="{{ route('dashboard', ['holiday_month' => $nextMonth]) }}"
               class="p-1.5 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-700 transition"
               aria-label="{{ __('holiday.next_month') }}" title="{{ __('holiday.next_month') }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
    </div>

    <div class="p-4 sm:p-6">
        {{-- Grid --}}
        <div class="grid grid-cols-7 gap-1 text-center">
            @foreach($weekdayLabels as $label)
                <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 py-1">{{ $label }}</div>
            @endforeach

            @for($i = 0; $i < $leadingBlanks; $i++)
                <div aria-hidden="true"></div>
            @endfor

            @for($day = 1; $day <= $daysInMonth; $day++)
                @php
                    $cellDate   = $firstDay->copy()->day($day);
                    $key        = $cellDate->toDateString();
                    $holiday    = $holidays[$key] ?? null;
                    $isToday    = $key === $todayString;
                    $isSunday   = $cellDate->dayOfWeek === 0;
                @endphp
                <div class="relative aspect-square flex items-center justify-center rounded-lg text-sm
                            @if($holiday && $holiday->isPublicHoliday()) bg-red-100 text-red-700 font-semibold dark:bg-red-900/40 dark:text-red-300
                            @elseif($holiday) bg-amber-100 text-amber-700 font-semibold dark:bg-amber-900/40 dark:text-amber-300
                            @elseif($isSunday) text-red-500 dark:text-red-400
                            @else text-gray-700 dark:text-gray-300 @endif
                            @if($isToday) ring-2 ring-blue-500 @endif"
                     @if($holiday) title="{{ $holiday->name }} — {{ $holiday->typeLabel() }}" @endif>
                    <span>{{ $day }}</span>
                    @if($holiday)
                        <span class="sr-only">{{ $holiday->name }} ({{ $holiday->typeLabel() }})</span>
                    @endif
                    @if($isToday)
                        <span class="sr-only">{{ __('holiday.today') }}</span>
                    @endif
                </div>
            @endfor
        </div>

        {{-- Legend --}}
        <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-gray-500 dark:text-gray-400">
            <span class="inline-flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-full bg-red-400" aria-hidden="true"></span>{{ __('holiday.type_holiday') }}
            </span>
            <span class="inline-flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-full bg-amber-400" aria-hidden="true"></span>{{ __('holiday.type_leave') }}
            </span>
        </div>

        {{-- This month's entries --}}
        @if($holidays->isNotEmpty())
            <ul class="mt-4 space-y-2 border-t border-gray-100 dark:border-gray-700 pt-4">
                @foreach($holidays as $holiday)
                    <li class="flex items-start justify-between gap-3 text-xs">
                        <span class="text-gray-700 dark:text-gray-300">{{ $holiday->name }}</span>
                        <span class="shrink-0 font-medium {{ $holiday->isPublicHoliday() ? 'text-red-600 dark:text-red-400' : 'text-amber-600 dark:text-amber-400' }}">
                            {{ $holiday->date->translatedFormat('d M') }}
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif

        {{-- Upcoming (across months) --}}
        @if($upcomingHolidays->isNotEmpty())
            <div class="mt-4 border-t border-gray-100 dark:border-gray-700 pt-4">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 mb-2">{{ __('holiday.upcoming') }}</p>
                <ul class="space-y-2">
                    @foreach($upcomingHolidays as $holiday)
                        <li class="flex items-start justify-between gap-3 text-xs">
                            <span class="text-gray-700 dark:text-gray-300">{{ $holiday->name }}</span>
                            <span class="shrink-0 text-gray-400">{{ $holiday->date->translatedFormat('d M Y') }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($holidays->isEmpty() && $upcomingHolidays->isEmpty())
            <p class="mt-4 text-xs text-gray-400 text-center border-t border-gray-100 dark:border-gray-700 pt-4">
                {{ __('holiday.none') }}
            </p>
        @endif
    </div>
</div>
