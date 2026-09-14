@php
    // Persistent Geoapify POI rows for one property, grouped for display.
    // $propertyPlaces — collection of PropertyPlace with `place` eager loaded.
    //
    // Extracted so the sync action can re-render JUST this table and swap it into
    // #poi-table-wrap without a page navigation.
    //
    // The three synchronized groups (shopping / hospital / transportation) are
    // always listed in a stable order; anything else that is persisted for the
    // property (manual rows, POIs from an older category set) is collected under
    // "Others" so nothing that exists in the database is hidden.
    $propertyPlaces = $propertyPlaces ?? collect();

    $grouped = [];
    foreach (\App\Services\GeoapifyService::GROUP_ORDER as $groupLabel) {
        $grouped[$groupLabel] = [];
    }

    $others = [];

    foreach ($propertyPlaces as $propertyPlace) {
        $category = $propertyPlace->place->category ?? null;

        if ($category !== null && array_key_exists($category, $grouped)) {
            $grouped[$category][] = $propertyPlace;
        } else {
            $others[] = $propertyPlace;
        }
    }

    if ($others !== []) {
        $grouped[__('Others')] = $others;
    }
@endphp

<div class="overflow-x-auto">
    @if($propertyPlaces->isEmpty())
        <p class="text-sm text-gray-400 py-4">{{ __('No nearby places have been synchronized yet.') }}</p>
    @else
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead>
                <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <th class="px-4 py-2">{{ __('Name') }}</th>
                    <th class="px-4 py-2">{{ __('Walking time') }}</th>
                    <th class="px-4 py-2">{{ __('Walking distance') }}</th>
                    <th class="px-4 py-2">{{ __('Address') }}</th>
                    <th class="px-4 py-2">{{ __('Source') }}</th>
                </tr>
            </thead>

            @foreach($grouped as $groupLabel => $rows)
                @if($rows !== [])
                    <tbody class="divide-y divide-gray-100">
                        <tr class="bg-gray-50">
                            <th colspan="5" class="px-4 py-2 text-left text-xs font-semibold text-gray-700 uppercase tracking-wide">
                                {{ $groupLabel }}
                                <span class="ml-1 font-normal normal-case text-gray-400">({{ count($rows) }})</span>
                            </th>
                        </tr>

                        @foreach($rows as $propertyPlace)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 text-gray-900">{{ $propertyPlace->place->name ?? '—' }}</td>
                                <td class="px-4 py-2 text-gray-600 tabular-nums whitespace-nowrap">
                                    {{ $propertyPlace->walking_duration_formatted ?? '—' }}
                                </td>
                                <td class="px-4 py-2 text-gray-600 tabular-nums whitespace-nowrap">
                                    {{ $propertyPlace->walking_distance_formatted ?? $propertyPlace->distance_formatted ?? '—' }}
                                </td>
                                <td class="px-4 py-2 text-gray-500">
                                    {{ \Illuminate\Support\Str::limit($propertyPlace->place->address ?? '', 48) }}
                                </td>
                                <td class="px-4 py-2">
                                    @if($propertyPlace->source === 'geoapify')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">geoapify</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">manual</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                @endif
            @endforeach
        </table>
    @endif
</div>
