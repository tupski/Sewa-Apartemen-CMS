<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Post;
use App\Models\Property;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = Cache::remember('dashboard_stats', now()->addMinutes(5), function () {
            $totalProperties = Property::count();
            $totalUnits = Property::get()->sum(fn ($p) => count($p->unit_types ?? []));
            $totalBookings = Booking::count();
            $totalUsers = User::count();
            $totalPosts = Post::where('status', 'published')->count();
            $activeBookings = Booking::where('status', 'confirmed')->count();
            $occupancyRate = $totalUnits > 0 ? round(($activeBookings / $totalUnits) * 100, 1) : 0;

            $monthlyData = collect();
            for ($i = 5; $i >= 0; $i--) {
                $start = now()->subMonths($i)->startOfMonth();
                $end = now()->subMonths($i)->endOfMonth();
                $count = Booking::whereBetween('created_at', [$start, $end])->count();
                $monthlyData[(int) $start->format('m')] = $count;
            }

            $bookingChartLabels = [];
            $bookingChartValues = [];
            $maxValue = $monthlyData->max() ?: 1;
            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $monthKey = (int) $date->format('m');
                $bookingChartLabels[] = $date->format('M');
                $bookingChartValues[] = $monthlyData[$monthKey] ?? 0;
            }

            return compact(
                'totalProperties', 'totalUnits', 'totalBookings', 'totalUsers',
                'totalPosts', 'activeBookings', 'occupancyRate',
                'bookingChartLabels', 'bookingChartValues', 'maxValue',
                'monthlyData'
            );
        });

        $recentBookings = Booking::with('property')->latest()->take(5)->get();
        $recentPosts = Post::with('category')->latest()->take(5)->get();
        $recentProperties = Property::latest()->take(5)->get();

        return view('dashboard', array_merge($stats, compact(
            'recentBookings',
            'recentPosts',
            'recentProperties'
        )));
    }
}
