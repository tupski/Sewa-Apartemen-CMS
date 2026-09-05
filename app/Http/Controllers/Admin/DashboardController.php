<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Post;
use App\Models\Property;
use App\Models\User;
use App\Services\NationalHolidayService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $stats = Cache::remember('dashboard_stats', now()->addMinutes(5), function () {
            $totalProperties = Property::count();
            $totalUnits = Property::get()->sum(fn ($p) => count($p->unit_types ?? []));
            $totalBookings = Booking::count();
            $totalUsers = User::count();
            $totalPosts = Post::where('status', 'published')->count();
            $activeBookings = Booking::where('status', 'confirmed')->count();
            $occupancyRate = $totalUnits > 0 ? round(($activeBookings / $totalUnits) * 100, 1) : 0;

            // Revenue: 6 bulan terakhir
            $revenueMonthly = [];
            $bookingMonthly = [];
            $labels = [];
            for ($i = 5; $i >= 0; $i--) {
                $start = now()->subMonths($i)->startOfMonth();
                $end = now()->subMonths($i)->endOfMonth();
                $month = $start->format('M Y');
                $labels[] = $start->format('M');

                $bookingsInMonth = Booking::whereBetween('created_at', [$start, $end])->get();
                $bookingMonthly[] = $bookingsInMonth->count();
                $revenueMonthly[] = (float) $bookingsInMonth
                    ->whereIn('status', ['confirmed', 'completed'])
                    ->sum('total_price');
            }

            // Booking status breakdown
            $statusBreakdown = Booking::selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status')
                ->toArray();

            // Top 5 properties by booking count
            $topProperties = Property::withCount('bookings')
                ->orderByDesc('bookings_count')
                ->take(5)
                ->get(['id', 'name', 'city']);

            // Revenue total
            $totalRevenue = Booking::whereIn('status', ['confirmed', 'completed'])->sum('total_price');
            $monthRevenue = Booking::whereIn('status', ['confirmed', 'completed'])
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('total_price');

            $maxValue = max(array_merge($bookingMonthly, [1]));

            return compact(
                'totalProperties', 'totalUnits', 'totalBookings', 'totalUsers',
                'totalPosts', 'activeBookings', 'occupancyRate',
                'labels', 'bookingMonthly', 'revenueMonthly',
                'statusBreakdown', 'topProperties',
                'totalRevenue', 'monthRevenue', 'maxValue'
            );
        });

        // Ensure legacy aliases exist (avoid undefined variable in old blade snippets)
        $stats['bookingChartLabels'] = $stats['bookingChartLabels'] ?? $stats['labels'];
        $stats['bookingChartValues'] = $stats['bookingChartValues'] ?? $stats['bookingMonthly'];

        // Today — always fresh
        $todayBookings = Booking::whereDate('created_at', today())->count();
        $pendingCount = Booking::where('status', 'pending')->count();
        $checkinToday = Booking::whereDate('check_in', today())->whereIn('status', ['confirmed', 'pending'])->count();
        $checkoutToday = Booking::whereDate('check_out', today())->whereIn('status', ['confirmed', 'completed'])->count();

        $recentBookings = Booking::with('property')->latest()->take(8)->get();
        $recentPosts = Post::with('category')->latest()->take(5)->get();
        $recentProperties = Property::latest()->take(5)->get();

        // National-holiday calendar. Reads the cached `national_holidays` table
        // only — the Tanggal Merah API is called exclusively by `holidays:fetch`.
        $holidayMonth = $this->resolveHolidayMonth($request);
        $holidays = NationalHolidayService::forMonth($holidayMonth->year, $holidayMonth->month);
        $upcomingHolidays = NationalHolidayService::upcoming();

        return view('dashboard', array_merge($stats, compact(
            'recentBookings', 'recentPosts', 'recentProperties',
            'todayBookings', 'pendingCount', 'checkinToday', 'checkoutToday',
            'holidayMonth', 'holidays', 'upcomingHolidays'
        )));
    }

    /**
     * Return the holiday calendar partial for AJAX modal loading.
     */
    public function calendar(Request $request): View
    {
        $month = $this->resolveCalendarMonth($request);
        $holidays = NationalHolidayService::forMonth($month->year, $month->month);
        $upcomingHolidays = NationalHolidayService::upcoming();

        return view('admin.dashboard._holiday-calendar', compact('holidayMonth', 'holidays', 'upcomingHolidays'));
    }

    /**
     * Resolve the month shown by the holiday calendar from `?holiday_month=YYYY-MM`,
     * falling back to the current month when the value is missing or malformed.
     */
    protected function resolveHolidayMonth(Request $request): Carbon
    {
        $raw = (string) $request->query('holiday_month', '');

        if (preg_match('/^\d{4}-\d{2}$/', $raw) === 1) {
            try {
                return Carbon::createFromFormat('Y-m-d', $raw.'-01')->startOfMonth();
            } catch (\Throwable $e) {
                // Fall through to the current month.
            }
        }

        return now()->startOfMonth();
    }

    /**
     * Resolve the month for the AJAX calendar endpoint from `?month=YYYY-MM`.
     */
    protected function resolveCalendarMonth(Request $request): Carbon
    {
        $raw = (string) $request->query('month', '');

        if (preg_match('/^\d{4}-\d{2}$/', $raw) === 1) {
            try {
                return Carbon::createFromFormat('Y-m-d', $raw.'-01')->startOfMonth();
            } catch (\Throwable $e) {
                // Fall through to the current month.
            }
        }

        return now()->startOfMonth();
    }
}
