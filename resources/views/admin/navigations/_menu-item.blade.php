<div class="flex items-center justify-between p-4 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition">
    <div class="flex items-center space-x-3">
        <!-- Drag Handle (for future drag-and-drop) -->
        <div class="text-gray-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
            </svg>
        </div>

        <!-- Menu Item Info -->
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.navigations.edit', $item) }}"
                   class="text-sm font-medium text-blue-600 hover:text-blue-900">
                    {{ $item->title }}
                </a>
                @if($item->status === 'inactive')
                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                        Inactive
                    </span>
                @endif
            </div>
            <p class="text-xs text-gray-500 mt-1">
                @if($item->type === 'internal')
                    Internal: {{ $item->url }}
                @elseif($item->type === 'external')
                    External: {{ $item->url }}
                @elseif($item->type === 'page')
                    Page: {{ $item->page->title ?? 'N/A' }}
                @endif
                | Order: {{ $item->order }}
            </p>
        </div>
    </div>

    <!-- Actions -->
    <div class="flex items-center gap-2">
        <a href="{{ route('admin.navigations.edit', $item) }}"
           class="text-blue-600 hover:text-blue-900"
           title="Edit">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
            </svg>
        </a>
        <form action="{{ route('admin.navigations.destroy', $item) }}"
              method="POST"
              class="inline"
              onsubmit="return confirm('Are you sure you want to delete this menu item?');">
            @csrf
            @method('DELETE')
            <button type="submit"
                    class="text-red-600 hover:text-red-900"
                    title="Delete">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
            </button>
        </form>
    </div>
</div>

<!-- Child Items (Nested) -->
@if($children->count() > 0)
    <div class="ml-8 mt-2 space-y-2">
        @foreach($children as $child)
            @include('admin.navigations._menu-item', ['item' => $child, 'children' => collect()])
        @endforeach
    </div>
@endif
