@extends('layouts.frontend')

@section('content')
    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    @if(isset($category))
                        {{ __('blog.category_label', ['name' => $category->name]) }}
                    @elseif(isset($tag))
                        {{ __('blog.tag_label', ['name' => $tag->name]) }}
                    @else
                        {{ __('blog.title') }}
                    @endif
                </h1>
            </div>

            <div class="flex flex-col lg:flex-row gap-8">
                <!-- Main Content -->
                <div class="flex-1">
                    @if($posts->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            @foreach($posts as $post)
                                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden hover:shadow-md transition">
                                    @if($post->featured_image)
                                        <a href="{{ route('blog.show', $post->slug) }}">
                                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($post->featured_image) }}"
                                                 alt="{{ $post->title }}"
                                                 class="w-full h-48 object-cover">
                                        </a>
                                    @endif
                                    <div class="p-6">
                                        <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 mb-2">
                                            @if($post->category)
                                                <a href="{{ route('blog.category', $post->category->slug) }}"
                                                   class="bg-blue-100 text-blue-700 px-2 py-1 rounded-full hover:bg-blue-200">
                                                    {{ $post->category->name }}
                                                </a>
                                            @endif
                                            <span>{{ $post->published_at?->format('M d, Y') }}</span>
                                        </div>
                                        <h2 class="text-xl font-semibold text-gray-800 mb-2">
                                            <a href="{{ route('blog.show', $post->slug) }}" class="hover:text-blue-600">
                                                {{ $post->title }}
                                            </a>
                                        </h2>
                                        @if($post->excerpt)
                                            <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">{{ \Illuminate\Support\Str::limit(strip_tags($post->excerpt), 150) }}</p>
                                        @endif
                                        <a href="{{ route('blog.show', $post->slug) }}"
                                           class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                            {{ __('blog.read_more') }} &rarr;
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-8">
                            {{ $posts->links() }}
                        </div>
                    @else
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-12 text-center">
                            <p class="text-gray-500 dark:text-gray-400">{{ __('blog.no_posts') }}</p>
                            <a href="{{ route('blog.index') }}" class="text-blue-600 hover:text-blue-800 mt-2 inline-block">{{ __('blog.view_all') }}</a>
                        </div>
                    @endif
                </div>

                <!-- Sidebar -->
                <div class="w-full lg:w-80 shrink-0">
                    @include('blog.sidebar')
                </div>
            </div>
        </div>
    </div>
@endsection
