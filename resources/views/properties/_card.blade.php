{{--
    Shared property card partial.

    Used by:
      - resources/views/properties/index.blade.php (listing grid)
      - resources/views/properties/show.blade.php (nearby accommodations section)

    Variables:
      - $property (required) : App\Models\Property instance
      - $primaryColor (optional) : brand color; falls back to settings
      - $distance (optional) : great-circle distance in KM (float). When set and
                               numeric, a location-pin badge is rendered. The
                               listing page does not pass this, so no badge shows.
--}}
@php
    $primaryColor = $primaryColor ?? \App\Services\SettingsService::get('primary_color', '#3b82f6');

    $lowestPx = $property->lowestPrice();
    $priceLabel = $lowestPx ? 'Rp ' . number_format($lowestPx, 0, ',', '.') : null;
    $topUnitType = $property->unit_types[0] ?? null;
    $unitTypeLabel = $topUnitType ? (\App\Models\Property::UNIT_TYPES[$topUnitType] ?? $topUnitType) : null;
    $amenityBadges = $property->amenities->take(3);

    // Distance badge: only render when a valid, non-null numeric distance is provided.
    $distanceValue = isset($distance) && $distance !== null && is_numeric($distance) ? (float) $distance : null;
@endphp
<a href="{{ route('properties.public.show', $property->slug) }}"
   class="group bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 flex flex-col">

    {{-- Card image --}}
    <div class="relative aspect-[16/10] bg-gray-200 dark:bg-gray-700 overflow-hidden shrink-0">
        @if ($property->featuredImage)
            <img src="{{ $property->featuredImage?->url }}"
                 alt="{{ $property->name }}"
                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                 loading="lazy">
        @elseif ($property->photos->isNotEmpty() && $property->photos->first()->media)
            <img src="{{ $property->photos->first()->media->url }}"
                 alt="{{ $property->name }}"
                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                 loading="lazy">
        @else
            <div class="w-full h-full flex items-center justify-center text-gray-400 dark:text-gray-500">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21"/>
                </svg>
            </div>
        @endif

        {{-- Badges overlay --}}
        <div class="absolute top-3 left-3 flex flex-wrap gap-1.5">
            @if ($property->is_featured)
                <span class="inline-flex items-center gap-0.5 px-2 py-1 rounded-lg text-xs font-bold text-yellow-900 bg-yellow-400/95 shadow-sm">
                    ★ {{ __('home.featured_badge') !== '★ Unggulan' ? 'Unggulan' : 'Unggulan' }}
                </span>
            @endif
            @if($unitTypeLabel)
                <span class="px-2 py-1 rounded-lg text-xs font-medium text-white/90 bg-black/40 backdrop-blur-sm">
                    {{ $unitTypeLabel }}
                </span>
            @endif
        </div>

        {{-- Share button — opens the global share modal for THIS property.
             stop/prevent so clicking it never triggers the card's link navigation. --}}
        <button type="button"
                x-data
                x-on:click.prevent.stop="$dispatch('open-share-modal', { url: @js(route('properties.public.show', $property->slug)), title: @js($property->name) })"
                class="absolute top-3 right-3 w-8 h-8 flex items-center justify-center rounded-full bg-white/90 dark:bg-gray-800/90 shadow hover:bg-white dark:hover:bg-gray-800 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                aria-label="{{ __('share.button') }}">
            <i class="fa-solid fa-share-nodes text-sm text-gray-500 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400 transition-colors" aria-hidden="true"></i>
        </button>

        {{-- Distance badge (only for nearby section; hidden gracefully when coords missing) --}}
        @if($distanceValue !== null)
            <span class="absolute bottom-3 left-3 inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-semibold text-white bg-black/55 backdrop-blur-sm shadow-sm">
                <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                {{ number_format($distanceValue, 1, '.', '') }} km
            </span>
        @endif
    </div>

    {{-- Card body --}}
    <div class="flex-1 flex flex-col p-4 gap-2.5">

        {{-- Name --}}
        <h3 class="font-semibold text-gray-900 dark:text-gray-100 text-base leading-snug group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors line-clamp-1">
            {{ $property->name }}
        </h3>

        {{-- Location --}}
        <p class="flex items-start gap-1 text-sm text-gray-500 dark:text-gray-400 leading-snug">
            <svg class="w-3.5 h-3.5 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                <path stroke-linecap="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/>
            </svg>
            <span class="line-clamp-1">
                {{ implode(', ', array_filter([$property->city, $property->province])) ?: $property->address }}
            </span>
        </p>

        {{-- Amenity chips --}}
        @if($amenityBadges->isNotEmpty())
            <div class="flex flex-wrap gap-1.5">
                @foreach($amenityBadges as $amenity)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-700/60">
                        @if($amenity->icon)<span aria-hidden="true">{{ $amenity->icon }}</span>@endif
                        {{ $amenity->name }}
                    </span>
                @endforeach
            </div>
        @endif

        {{-- Spacer --}}
        <div class="flex-1"></div>

        {{-- Price + CTA --}}
        <div class="flex items-end justify-between gap-2 pt-2 border-t border-gray-100 dark:border-gray-700/50 mt-auto">
            <div>
                @if($priceLabel)
                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ __('prop.from') }}</p>
                    <p class="text-base font-bold text-gray-900 dark:text-gray-100 leading-tight">
                        {{ $priceLabel }}
                    </p>
                @else
                    <p class="text-sm text-gray-400 dark:text-gray-500 italic">{{ __('home.contact_for_price') }}</p>
                @endif
            </div>
            <span class="inline-flex items-center gap-1 shrink-0 px-3 py-1.5 rounded-lg text-xs font-semibold text-white transition hover:opacity-90 focus:outline-none"
                  style="background-color: {{ $primaryColor }}"
                  aria-label="{{ __('prop.see_detail') }} {{ $property->name }}">
                {{ __('prop.see_detail') }}
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
            </span>
        </div>
    </div>
</a>
