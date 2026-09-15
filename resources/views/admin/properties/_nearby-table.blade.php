@php
    // Persistent Geoapify POI rows for one property, grouped for display.
    // $propertyPlaces — collection of PropertyPlace with `place` eager loaded.
    //
    // Extracted so the sync action can re-render JUST this table and swap it into
    // #poi-table-wrap without a page navigation.
    //
    // Groups come from the DB-managed place_categories catalogue (localized
    // labels, display order = sort_order). A place's raw category slug matches
    // its row exact-or-child-prefix (`public_transport.train.station` lands under
    // `public_transport`); anything unmatched (manual rows, POIs from an older
    // category set) is collected under "Others" so nothing in the DB is hidden.
    $propertyPlaces = $propertyPlaces ?? collect();

    $orderedCategories = \App\Models\PlaceCategory::query()
        ->orderBy('sort_order')->orderBy('id')->get();

    $grouped = [];   // slug => rows, in catalogue order
    foreach ($orderedCategories as $category) {
        $grouped[$category->slug] = [];
    }
    $others = [];

    foreach ($propertyPlaces as $propertyPlace) {
        $raw = $propertyPlace->place->category ?? null;
        $slug = null;

        if (is_string($raw) && $raw !== '') {
            foreach ($orderedCategories as $category) {
                if ($raw === $category->slug || str_starts_with($raw, $category->slug.'.')) {
                    $slug = $category->slug;
                    break;
                }
            }

            if ($slug === null) {
                $top = strtok($raw, '.');
                foreach ($orderedCategories as $category) {
                    if ($category->slug === $top) {
                        $slug = $top;
                        break;
                    }
                }
            }
        }

        if ($slug !== null) {
            $grouped[$slug][] = $propertyPlace;
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

            @foreach($grouped as $groupSlug => $rows)
                @if($rows !== [])
                    <tbody class="divide-y divide-gray-100">
                        <tr class="bg-gray-50">
                            <th colspan="5" class="px-4 py-2 text-left text-xs font-semibold text-gray-700 uppercase tracking-wide">
                                {{ \App\Models\PlaceCategory::labelForSlug($groupSlug) }}
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
