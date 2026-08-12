@extends('layouts.admin')

@section('page-title', 'Page Details')

@section('content')
<div class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">{{ $page->title }}</h2>
            <p class="text-sm text-gray-600 mt-1">/{{ $page->slug }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.pages.edit', $page) }}"
               class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 transition">
                <i class="fa-solid fa-pen mr-2"></i> Edit
            </a>
            <a href="{{ route('admin.pages.index') }}"
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
                    @if($page->status === 'published')
                        <span class="px-2 py-0.5 inline-flex text-xs font-semibold rounded-full bg-green-100 text-green-800">Published</span>
                    @else
                        <span class="px-2 py-0.5 inline-flex text-xs font-semibold rounded-full bg-gray-100 text-gray-800">{{ ucfirst($page->status ?? 'draft') }}</span>
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-gray-500">Layout</dt>
                <dd class="font-medium text-gray-800">{{ ucfirst(str_replace('-', ' ', $page->layout ?? 'default')) }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Homepage</dt>
                <dd class="font-medium text-gray-800">{{ $page->is_homepage ? 'Yes' : 'No' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Author</dt>
                <dd class="font-medium text-gray-800">{{ $page->user?->name ?? 'Unknown' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Created</dt>
                <dd class="font-medium text-gray-800">{{ $page->created_at?->format('d M Y H:i') }}</dd>
            </div>
        </dl>

        <div class="mt-6 border-t border-gray-200 pt-6">
            <h3 class="text-sm font-semibold text-gray-700 mb-2">Content</h3>
            <div class="prose max-w-none text-gray-700 bg-gray-50 rounded-md p-4 max-h-[32rem] overflow-y-auto">
                {!! $page->content !!}
            </div>
        </div>

        @if($page->seo && ($page->seo->meta_title || $page->seo->meta_description))
            <div class="mt-6 border-t border-gray-200 pt-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-2">SEO</h3>
                <dl class="grid grid-cols-1 gap-y-3 text-sm">
                    @if($page->seo->meta_title)
                        <div>
                            <dt class="text-gray-500">Meta Title</dt>
                            <dd class="font-medium text-gray-800">{{ $page->seo->meta_title }}</dd>
                        </div>
                    @endif
                    @if($page->seo->meta_description)
                        <div>
                            <dt class="text-gray-500">Meta Description</dt>
                            <dd class="font-medium text-gray-800">{{ $page->seo->meta_description }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        @endif

        <div class="mt-6 pt-6 border-t border-gray-200 flex items-center justify-end">
            <form action="{{ route('admin.pages.destroy', $page) }}" method="POST"
                  onsubmit="return confirm('Are you sure you want to delete this page?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 text-white font-medium rounded-md hover:bg-red-700 transition">
                    <i class="fa-solid fa-trash mr-2"></i> Delete Page
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
