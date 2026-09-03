@php
    // Persistent Geoapify POI rows for one property, nearest-first.
    // $propertyPlaces — collection of PropertyPlace with `place` eager loaded.
    //
    // Extracted from _nearby.blade.php so the resync action can re-render JUST
    // the table and swap it into #poi-table-wrap without a page navigation.
    $propertyPlaces = $propertyPlaces ?? collect();
@endphp

<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead>
            <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                <th class="px-4 py-2">Nama</th>
                <th class="px-4 py-2">{{ __('Category') }}</th>
                <th class="px-4 py-2">{{ __('Distance') }}</th>
                <th class="px-4 py-2">Alamat</th>
                <th class="px-4 py-2">Sumber</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($propertyPlaces as $propertyPlace)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 text-gray-900">{{ $propertyPlace->place->name ?? '—' }}</td>
                    <td class="px-4 py-2 text-gray-600">{{ $propertyPlace->place->category ?? '—' }}</td>
                    <td class="px-4 py-2 text-gray-600 tabular-nums whitespace-nowrap">{{ $propertyPlace->distance_formatted }}</td>
                    <td class="px-4 py-2 text-gray-500">{{ \Illuminate\Support\Str::limit($propertyPlace->place->address ?? '', 48) }}</td>
                    <td class="px-4 py-2">
                        @if($propertyPlace->source === 'geoapify')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">geoapify</span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">manual</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-6 text-center text-gray-400">Belum ada data</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
