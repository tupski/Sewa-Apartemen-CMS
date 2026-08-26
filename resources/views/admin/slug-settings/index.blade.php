@extends('layouts.admin')
@section('page-title', __('slug_settings.title'))

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div>
        <h1 class="text-xl font-bold text-gray-800 dark:text-white">{{ __('slug_settings.heading') }}</h1>
        <p class="text-sm text-gray-500 mt-1">{{ __('slug_settings.subtitle') }}</p>
    </div>

    @if(session('success'))
        <div class="px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
        <form method="POST" action="{{ route('admin.slug-settings.update') }}" data-warn-unsaved>
            @csrf
            <div class="space-y-5">
                @foreach($slugs as $key => $meta)
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ $meta['label'] }}
                        @if($key === 'admin_prefix')
                            <span class="ml-1 text-xs text-red-500">⚠ {{ __('slug_settings.admin_prefix_warning') }}</span>
                        @endif
                    </label>
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-400 shrink-0">/</span>
                        <input type="text"
                               name="{{ $key }}"
                               value="{{ old($key, $meta['value']) }}"
                               placeholder="{{ $meta['default'] }}"
                               class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm
                                      focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white
                                      {{ $key === 'admin_prefix' ? 'border-yellow-400' : '' }}"
                               pattern="[a-z0-9\-\/]+"
                               title="{{ __('slug_settings.input_title') }}">
                    </div>
                    <p class="text-xs text-gray-400 mt-1">
                        {{ __('slug_settings.example') }}: <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">{{ $meta['example'] }}</code>
                        &nbsp;|&nbsp; {{ __('slug_settings.default') }}: <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">{{ $meta['default'] }}</code>
                    </p>
                    @error($key)<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                @endforeach
            </div>

            <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between gap-4">
                <div class="text-xs text-yellow-600 bg-yellow-50 border border-yellow-200 rounded-lg px-3 py-2">
                    <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                    {!! __('slug_settings.route_cache_hint') !!}
                </div>
                <button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition shrink-0">
                    {{ __('slug_settings.save') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
