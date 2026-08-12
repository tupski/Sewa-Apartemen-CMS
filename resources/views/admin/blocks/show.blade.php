@extends('layouts.admin')

@section('page-title', 'Block Details')

@section('content')
<div class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Block Details</h2>
            <p class="text-sm text-gray-600 mt-1">{{ $block->name }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.blocks.edit', $block) }}"
               class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 transition">
                <i class="fa-solid fa-pen mr-2"></i> Edit
            </a>
            <a href="{{ route('admin.blocks.index') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 font-medium rounded-md hover:bg-gray-300 transition">
                <i class="fa-solid fa-arrow-left mr-2"></i> Back
            </a>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
            <div>
                <dt class="text-gray-500">Status</dt>
                <dd>
                    @if($block->status === 'active')
                        <span class="px-2 py-0.5 inline-flex text-xs font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                    @else
                        <span class="px-2 py-0.5 inline-flex text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Inactive</span>
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-gray-500">Display Pages</dt>
                <dd class="font-medium text-gray-800">{{ empty($block->pages) ? 'All Pages' : implode(', ', (array) $block->pages) }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Created</dt>
                <dd class="font-medium text-gray-800">{{ $block->created_at?->format('d M Y H:i') }}</dd>
            </div>
        </dl>

        <!-- Content -->
        <div class="mt-6 border-t border-gray-200 pt-6">
            <h3 class="text-sm font-semibold text-gray-700 mb-2">Content</h3>
            @if(is_string($block->content))
                <div class="prose max-w-none text-gray-700 bg-gray-50 rounded-md p-4 max-h-96 overflow-y-auto">
                    {!! $block->content !!}
                </div>
            @else
                <pre class="text-xs text-gray-700 bg-gray-50 rounded-md p-4 overflow-x-auto whitespace-pre-wrap">{{ json_encode($block->content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            @endif
        </div>

        <div class="mt-6 pt-6 border-t border-gray-200 flex items-center justify-end">
            <form action="{{ route('admin.blocks.destroy', $block) }}" method="POST"
                  onsubmit="return confirm('Are you sure you want to delete this block?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 text-white font-medium rounded-md hover:bg-red-700 transition">
                    <i class="fa-solid fa-trash mr-2"></i> Delete Block
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
