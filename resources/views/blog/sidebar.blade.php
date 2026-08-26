<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 space-y-6">
    <!-- Recent Posts -->
    <div>
        <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">{{ __('blog.recent') }}</h3>
        <ul class="space-y-3">
            @foreach($recentPosts as $recentPost)
                <li>
                    <a href="{{ route('blog.show', $recentPost->slug) }}"
                       class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                        {{ $recentPost->title }}
                    </a>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $recentPost->published_at?->format('M d, Y') }}</p>
                </li>
            @endforeach
        </ul>
    </div>

    <!-- Categories -->
    <div>
        <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">{{ __('blog.categories') }}</h3>
        <ul class="space-y-2">
            @foreach($categories as $cat)
                <li>
                    <a href="{{ route('blog.category', $cat->slug) }}"
                       class="flex items-center justify-between text-gray-600 dark:text-gray-400 hover:text-blue-600 text-sm">
                        <span>{{ $cat->name }}</span>
                        <span class="bg-gray-100 text-gray-600 dark:text-gray-400 text-xs px-2 py-1 rounded-full">{{ $cat->posts_count }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>

    <!-- Tags Cloud -->
    <div>
        <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">{{ __('blog.tags') }}</h3>
        <div class="flex flex-wrap gap-2">
            @foreach($tags as $t)
                <a href="{{ route('blog.tag', $t->slug) }}"
                   class="inline-block bg-gray-100 hover:bg-blue-100 hover:text-blue-700 text-gray-600 dark:text-gray-400 text-sm px-3 py-1 rounded-full transition">
                    {{ $t->name }}
                </a>
            @endforeach
        </div>
    </div>
</div>
