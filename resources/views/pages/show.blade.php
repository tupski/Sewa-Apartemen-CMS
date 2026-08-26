@extends('layouts.frontend')

@section('content')
    <!-- Page Header -->
    <section class="py-14 md:py-20 text-white"
             style="background: linear-gradient(135deg, {{ \App\Services\SettingsService::get('primary_color', '#3b82f6') }} 0%, {{ \App\Services\SettingsService::get('secondary_color', '#10b981') }} 100%);">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <a href="{{ url('/') }}" class="text-sm text-white/80 hover:text-white inline-flex items-center mb-3">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                {{ __('nav.home') }}
            </a>
            <h1 class="text-3xl md:text-5xl font-extrabold">{{ $page->title }}</h1>
        </div>
    </section>

    <section class="py-12 bg-gray-50 dark:bg-gray-800/50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <article class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-8 md:p-12">
                <div class="prose prose-gray max-w-none text-gray-700 dark:text-gray-300 leading-relaxed">
                    {!! $page->content !!}
                </div>
            </article>

            {{-- Content-area blocks --}}
            @foreach (($blocks['content'] ?? collect()) as $block)
                <div class="mt-8 bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-8">
                    @if (is_string($block->content))
                        <div class="prose prose-gray max-w-none text-gray-700 dark:text-gray-300 leading-relaxed">
                            {!! $block->content !!}
                        </div>
                    @else
                        <pre class="text-xs text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ json_encode($block->content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    @endif
                </div>
            @endforeach
        </div>
    </section>
@endsection
