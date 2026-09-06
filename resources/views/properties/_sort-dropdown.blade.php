@php
    $id = $id ?? 'sort';
    $formClass = $formClass ?? 'flex-1';
    $selectClass = $selectClass ?? 'w-full h-9 px-3 pr-8 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-700 dark:text-gray-200 shadow-sm focus:outline-none focus:ring-2 appearance-none';
    $showLabel = $showLabel ?? false;
    $optionPrefix = $optionPrefix ?? false;
@endphp

<form action="{{ route('properties.public.index') }}" method="GET" class="{{ $formClass }}">
    @foreach(request()->except('sort') as $k => $v)
        @if(is_array($v))
            @foreach($v as $vi)<input type="hidden" name="{{ $k }}[]" value="{{ $vi }}">@endforeach
        @else
            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
        @endif
    @endforeach

    <div class="{{ $showLabel ? 'flex items-center gap-2' : '' }}">
        <label for="{{ $id }}" class="{{ $showLabel ? 'text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap' : 'sr-only' }}">{{ __('prop.sort_label') }}{{ $showLabel ? ':' : '' }}</label>
        <select id="{{ $id }}" name="sort" onchange="this.form.submit()"
                class="{{ $selectClass }}"
                style="background-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280' stroke-width='2'%3E%3Cpath stroke-linecap='round' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E\"); background-repeat: no-repeat; background-position: right 0.5rem center; background-size: 1rem;">
            @foreach($sortOptions as $val => $label)
                <option value="{{ $val }}" @selected($sort === $val)>{{ $optionPrefix ? __('prop.sort_label') . ': ' : '' }}{{ $label }}</option>
            @endforeach
        </select>
    </div>
</form>
