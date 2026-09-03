@extends('layouts.admin')

@section('page-title', 'Dashboard')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    {{-- ── TODAY STRIP ──────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @php
        $todayCards = [
            ['label'=>'Booking Hari Ini','value'=>$todayBookings,'color'=>'indigo','icon'=>'fa-calendar-day','url'=>route('admin.bookings.index',['date_from'=>now()->format('Y-m-d'),'date_to'=>now()->format('Y-m-d')])],
            ['label'=>'Pending Konfirmasi','value'=>$pendingCount,'color'=>'yellow','icon'=>'fa-triangle-exclamation','url'=>route('admin.bookings.index',['status'=>'pending']),'alert'=>$pendingCount>0],
            ['label'=>'Check-in Hari Ini','value'=>$checkinToday,'color'=>'green','icon'=>'fa-arrow-right-to-bracket','url'=>route('admin.bookings.index',['check_in'=>now()->format('Y-m-d')])],
            ['label'=>'Check-out Hari Ini','value'=>$checkoutToday,'color'=>'red','icon'=>'fa-arrow-right-from-bracket','url'=>route('admin.bookings.index',['check_out'=>now()->format('Y-m-d')])],
        ];
        $colors = ['indigo'=>'border-indigo-500 text-indigo-600','yellow'=>'border-yellow-500 text-yellow-600','green'=>'border-green-500 text-green-600','red'=>'border-red-500 text-red-600'];
        @endphp
        @foreach($todayCards as $card)
        <a href="{{ $card['url'] }}" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 hover:shadow-md transition border-l-4 {{ $colors[$card['color']] }}">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ $card['label'] }}</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-white mt-1">{{ $card['value'] }}</p>
                    @if(!empty($card['alert']))
                    <p class="text-xs text-yellow-600 font-medium mt-1">Butuh tindakan!</p>
                    @endif
                </div>
                <i class="fa-solid {{ $card['icon'] }} text-2xl opacity-20 mt-1"></i>
            </div>
        </a>
        @endforeach
    </div>

    {{-- ── KPI CARDS ────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @php
        $kpi = [
            ['label'=>'Total Properties','value'=>$totalProperties,'icon'=>'fa-building','color'=>'text-blue-600','url'=>route('admin.properties.index')],
            ['label'=>'Total Bookings','value'=>$totalBookings,'icon'=>'fa-book','color'=>'text-purple-600','url'=>route('admin.bookings.index')],
            ['label'=>'Total Users','value'=>$totalUsers,'icon'=>'fa-users','color'=>'text-teal-600','url'=>route('admin.users.index')],
            ['label'=>'Occupancy Rate','value'=>$occupancyRate.'%','icon'=>'fa-percent','color'=>'text-orange-600','url'=>null],
        ];
        @endphp
        @foreach($kpi as $k)
        @if($k['url'])
        <a href="{{ $k['url'] }}" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 hover:shadow-md transition">
        @else
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5">
        @endif
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center shrink-0">
                    <i class="fa-solid {{ $k['icon'] }} text-xl {{ $k['color'] }}"></i>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ $k['label'] }}</p>
                    <p class="text-2xl font-bold text-gray-800 dark:text-white">{{ $k['value'] }}</p>
                </div>
            </div>
        @if($k['url'])</a>@else</div>@endif
        @endforeach
    </div>

    {{-- ── REVENUE KPI ──────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5">
            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Total Revenue</p>
            <p class="text-2xl font-bold text-gray-800 dark:text-white">Rp {{ number_format($totalRevenue ?? 0, 0, ',', '.') }}</p>
            <p class="text-xs text-gray-400 mt-1">Confirmed + Completed</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5">
            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Revenue Bulan Ini</p>
            <p class="text-2xl font-bold text-green-600">Rp {{ number_format($monthRevenue ?? 0, 0, ',', '.') }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ now()->format('F Y') }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5">
            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Booking Aktif</p>
            <p class="text-2xl font-bold text-indigo-600">{{ $activeBookings }}</p>
            <p class="text-xs text-gray-400 mt-1">Status: confirmed</p>
        </div>
    </div>

    {{-- ── CHARTS ROW ───────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Booking trend line chart --}}
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Booking & Revenue (6 Bulan)</h3>
            <canvas id="trendChart" height="120"></canvas>
        </div>

        {{-- Booking status doughnut --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 flex flex-col">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Status Booking</h3>
            <div class="flex-1 flex items-center justify-center">
                <canvas id="statusChart" style="max-height:200px"></canvas>
            </div>
            <div class="mt-3 space-y-1" id="statusLegend"></div>
        </div>
    </div>

    {{-- ── TOP PROPERTIES + RECENT BOOKINGS ────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Top properties --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <h3 class="font-semibold text-gray-800 dark:text-white text-sm">Top Properties</h3>
                <a href="{{ route('admin.properties.index') }}" class="text-xs text-blue-600 hover:underline">Lihat semua</a>
            </div>
            @if(isset($topProperties) && $topProperties->count())
            <div class="divide-y divide-gray-50 dark:divide-gray-700">
                @foreach($topProperties as $i => $prop)
                <div class="px-6 py-3 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="text-xs font-bold text-gray-400 w-4">{{ $i+1 }}</span>
                        <div>
                            <a href="{{ route('admin.properties.show', $prop) }}" class="text-sm font-medium text-gray-800 dark:text-white hover:text-blue-600">{{ $prop->name }}</a>
                            <p class="text-xs text-gray-400">{{ $prop->city ?? '—' }}</p>
                        </div>
                    </div>
                    <span class="text-xs font-semibold bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 px-2 py-0.5 rounded-full">{{ $prop->bookings_count }} booking</span>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-sm text-gray-400 text-center py-8">Belum ada data.</p>
            @endif
        </div>

        {{-- Recent bookings --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <h3 class="font-semibold text-gray-800 dark:text-white text-sm">Booking Terbaru</h3>
                <a href="{{ route('admin.bookings.index') }}" class="text-xs text-blue-600 hover:underline">Lihat semua</a>
            </div>
            @if($recentBookings->count())
            <div class="divide-y divide-gray-50 dark:divide-gray-700">
                @foreach($recentBookings as $bk)
                @php
                $sc = ['pending'=>'bg-yellow-100 text-yellow-700','confirmed'=>'bg-green-100 text-green-700','cancelled'=>'bg-red-100 text-red-700','completed'=>'bg-blue-100 text-blue-700'];
                @endphp
                <div class="px-6 py-3 flex items-center justify-between">
                    <div>
                        <a href="{{ route('admin.bookings.show', $bk) }}" class="text-sm font-medium text-gray-800 dark:text-white hover:text-blue-600">{{ $bk->guest_name ?? '#'.$bk->id }}</a>
                        <p class="text-xs text-gray-400">{{ $bk->property->name ?? '—' }}</p>
                    </div>
                    <div class="text-right">
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $sc[$bk->status] ?? 'bg-gray-100 text-gray-600' }}">{{ ucfirst($bk->status) }}</span>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $bk->created_at->diffForHumans() }}</p>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-sm text-gray-400 text-center py-8">Belum ada booking.</p>
            @endif
        </div>
    </div>

    {{-- ── RECENT POSTS + PROPERTIES ───────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <h3 class="font-semibold text-gray-800 dark:text-white text-sm">Post Terbaru</h3>
                <a href="{{ route('admin.posts.index') }}" class="text-xs text-blue-600 hover:underline">Lihat semua</a>
            </div>
            @if($recentPosts->count())
            <div class="divide-y divide-gray-50 dark:divide-gray-700">
                @foreach($recentPosts as $post)
                <div class="px-6 py-3 flex items-center justify-between">
                    <div>
                        <a href="{{ route('admin.posts.edit', $post) }}" class="text-sm font-medium text-gray-800 dark:text-white hover:text-blue-600">{{ Str::limit($post->title, 40) }}</a>
                        <p class="text-xs text-gray-400">{{ $post->category->name ?? 'Uncategorized' }}</p>
                    </div>
                    <span class="text-xs text-gray-400">{{ $post->created_at->format('M d') }}</span>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-sm text-gray-400 text-center py-8">Belum ada post.</p>
            @endif
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <h3 class="font-semibold text-gray-800 dark:text-white text-sm">Properti Terbaru</h3>
                <a href="{{ route('admin.properties.index') }}" class="text-xs text-blue-600 hover:underline">Lihat semua</a>
            </div>
            @if($recentProperties->count())
            <div class="divide-y divide-gray-50 dark:divide-gray-700">
                @foreach($recentProperties as $prop)
                <div class="px-6 py-3 flex items-center justify-between">
                    <div>
                        <a href="{{ route('admin.properties.show', $prop) }}" class="text-sm font-medium text-gray-800 dark:text-white hover:text-blue-600">{{ $prop->name }}</a>
                        <p class="text-xs text-gray-400">{{ $prop->city ?? '' }}{{ $prop->city && $prop->province ? ', '.$prop->province : ($prop->province ?? '') }}</p>
                    </div>
                    <span class="text-xs text-gray-400">{{ $prop->created_at->format('M d') }}</span>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-sm text-gray-400 text-center py-8">Belum ada properti.</p>
            @endif
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
(function () {
    var CHART_SRC = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js';

    function renderCharts() {
    const isDark = document.documentElement.classList.contains('dark');
    const gridColor   = isDark ? 'rgba(255,255,255,0.07)' : 'rgba(0,0,0,0.06)';
    const textColor   = isDark ? '#9ca3af' : '#6b7280';
    const labelMonths = @json($labels ?? []);
    const bookingData = @json($bookingMonthly ?? []);
    const revenueData = @json($revenueMonthly ?? []);

    // ── Trend chart (line + bar combo) ──────────────────────────────
    const trendCtx = document.getElementById('trendChart');
    if (trendCtx) {
        new Chart(trendCtx, {
            data: {
                labels: labelMonths,
                datasets: [
                    {
                        type: 'bar',
                        label: 'Booking',
                        data: bookingData,
                        backgroundColor: 'rgba(99,102,241,0.25)',
                        borderColor: 'rgba(99,102,241,0.8)',
                        borderWidth: 1.5,
                        borderRadius: 4,
                        yAxisID: 'yBooking',
                    },
                    {
                        type: 'line',
                        label: 'Revenue (Rp)',
                        data: revenueData,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16,185,129,0.08)',
                        borderWidth: 2,
                        pointBackgroundColor: '#10b981',
                        pointRadius: 4,
                        fill: true,
                        tension: 0.35,
                        yAxisID: 'yRevenue',
                    },
                ],
            },
            options: {
                responsive: true,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { labels: { color: textColor, font: { size: 11 } } },
                    tooltip: {
                        callbacks: {
                            label: ctx => {
                                if (ctx.datasetIndex === 1) return ' Rp ' + Number(ctx.raw).toLocaleString('id-ID');
                                return ' ' + ctx.raw + ' booking';
                            }
                        }
                    }
                },
                scales: {
                    x: { ticks: { color: textColor }, grid: { color: gridColor } },
                    yBooking: { position: 'left', ticks: { color: textColor }, grid: { color: gridColor }, beginAtZero: true },
                    yRevenue: { position: 'right', ticks: { color: textColor, callback: v => 'Rp '+Number(v).toLocaleString('id-ID') }, grid: { display: false }, beginAtZero: true },
                }
            }
        });
    }

    // ── Status doughnut ─────────────────────────────────────────────
    const statusRaw = @json($statusBreakdown ?? []);
    const statusColors = {
        pending:   '#f59e0b',
        confirmed: '#10b981',
        cancelled: '#ef4444',
        completed: '#3b82f6',
    };
    const statusLabels = Object.keys(statusRaw);
    const statusValues = Object.values(statusRaw);
    const statusColorsArr = statusLabels.map(l => statusColors[l] ?? '#9ca3af');

    const statusCtx = document.getElementById('statusChart');
    if (statusCtx && statusLabels.length) {
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: statusLabels.map(l => l.charAt(0).toUpperCase() + l.slice(1)),
                datasets: [{ data: statusValues, backgroundColor: statusColorsArr, borderWidth: 2, borderColor: isDark ? '#1f2937' : '#fff' }]
            },
            options: {
                responsive: true,
                cutout: '68%',
                plugins: { legend: { display: false } }
            }
        });

        // custom legend
        const leg = document.getElementById('statusLegend');
        statusLabels.forEach((l, i) => {
            const total = statusValues.reduce((a, b) => a + b, 0);
            const pct   = total ? Math.round(statusValues[i] / total * 100) : 0;
            leg.insertAdjacentHTML('beforeend',
                `<div class="flex items-center justify-between text-xs">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background:${statusColorsArr[i]}"></span>
                        <span class="text-gray-600 dark:text-gray-300 capitalize">${l}</span>
                    </div>
                    <span class="font-semibold text-gray-700 dark:text-gray-200">${statusValues[i]} <span class="text-gray-400">(${pct}%)</span></span>
                </div>`
            );
        });
    }
    }

    // Load Chart.js on demand, then render. loadScript is defined in app.js.
    if (typeof window.loadScript === 'function') {
        window.loadScript(CHART_SRC).then(renderCharts).catch(function () {
            /* Chart.js failed to load — charts are non-critical, fail silently */
        });
    } else if (typeof Chart !== 'undefined') {
        renderCharts();
    }
})();
</script>
@endpush
