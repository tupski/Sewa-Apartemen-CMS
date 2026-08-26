@extends('layouts.frontend')

@section('content')
    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row gap-8">
                <!-- Main Content -->
                <div class="flex-1">
                    <article class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
                        @if($post->featured_image)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($post->featured_image) }}"
                                 alt="{{ $post->title }}"
                                 class="w-full h-64 md:h-96 object-cover">
                        @endif

                        <div class="p-6 md:p-8">
                            <div class="flex items-center gap-3 text-sm text-gray-500 dark:text-gray-400 mb-4">
                                @if($post->category)
                                    <a href="{{ route('blog.category', $post->category->slug) }}"
                                       class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full hover:bg-blue-200 text-xs font-medium">
                                        {{ $post->category->name }}
                                    </a>
                                @endif
                                <span>{{ $post->published_at?->format('M d, Y') }}</span>
                                <span>{{ __('blog.by') }} {{ $post->author->name ?? __('blog.unknown_author') }}</span>
                            </div>

                            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-6">{{ $post->title }}</h1>

                            <div class="prose max-w-none text-gray-700 dark:text-gray-300">
                                {!! $post->content !!}
                            </div>

                            @if($post->tags->count() > 0)
                                <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-sm text-gray-500 dark:text-gray-400 font-medium">{{ __('blog.tags') }}:</span>
                                        @foreach($post->tags as $t)
                                            <a href="{{ route('blog.tag', $t->slug) }}"
                                               class="bg-gray-100 hover:bg-blue-100 hover:text-blue-700 text-gray-600 dark:text-gray-400 text-sm px-3 py-1 rounded-full transition">
                                                {{ $t->name }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- Share Buttons -->
                            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                                <div class="flex items-center gap-4">
                                    <span class="text-sm text-gray-500 dark:text-gray-400 font-medium">{{ __('blog.share') }}:</span>
                                    @php
                                        $shareUrl = urlencode(route('blog.show', $post->slug));
                                        $shareTitle = urlencode($post->title);
                                    @endphp
                                    <a href="https://twitter.com/intent/tweet?url={{ $shareUrl }}&text={{ $shareTitle }}"
                                       target="_blank" rel="noopener"
                                       class="text-blue-400 hover:text-blue-600 transition">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                                    </a>
                                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}"
                                       target="_blank" rel="noopener"
                                       class="text-blue-600 hover:text-blue-800 transition">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                    </a>
                                    <a href="https://wa.me/?text={{ $shareTitle }}%20{{ $shareUrl }}"
                                       target="_blank" rel="noopener"
                                       class="text-green-500 hover:text-green-700 transition">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </article>

                    <!-- Related Posts -->
                    @if($relatedPosts->count() > 0)
                        <div class="mt-8">
                            <h3 class="text-xl font-bold text-gray-800 mb-4">{{ __('blog.related') }}</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                @foreach($relatedPosts as $relatedPost)
                                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden hover:shadow-md transition">
                                        @if($relatedPost->featured_image)
                                            <a href="{{ route('blog.show', $relatedPost->slug) }}">
                                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($relatedPost->featured_image) }}"
                                                     alt="{{ $relatedPost->title }}"
                                                     class="w-full h-40 object-cover">
                                            </a>
                                        @endif
                                        <div class="p-4">
                                            <h4 class="font-semibold text-gray-800 mb-1">
                                                <a href="{{ route('blog.show', $relatedPost->slug) }}" class="hover:text-blue-600">
                                                    {{ $relatedPost->title }}
                                                </a>
                                            </h4>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $relatedPost->published_at?->format('M d, Y') }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Back Link -->
                    <div class="mt-6">
                        <a href="{{ route('blog.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">
                            &larr; {{ __('blog.back') }}
                        </a>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="w-full lg:w-80 shrink-0">
                    @include('blog.sidebar')
                </div>
            </div>
        </div>
    </div>
@endsection
