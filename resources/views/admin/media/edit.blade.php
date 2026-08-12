@extends('layouts.admin')

@section('page-title', 'Edit Media')

@section('content')
<div class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Edit Media</h2>
            <p class="text-sm text-gray-600 mt-1">{{ $media->original_filename }}</p>
        </div>
        <a href="{{ route('admin.media.index') }}"
           class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 font-medium rounded-md hover:bg-gray-300 transition">
            <i class="fa-solid fa-arrow-left mr-2"></i> Back
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow-sm p-6">
            @if($media->type === 'image')
                <img src="{{ $media->url }}" alt="{{ $media->alt ?: $media->original_filename }}" class="w-full h-auto rounded-md border border-gray-200">
            @else
                <div class="flex flex-col items-center justify-center py-16 text-gray-400 border border-dashed border-gray-300 rounded-md">
                    <i class="fa-regular fa-file-pdf text-5xl mb-3"></i>
                    <p class="text-sm">{{ $media->mime_type }}</p>
                </div>
            @endif
        </div>

        <div class="lg:col-span-2 bg-white rounded-lg shadow-sm p-6">
            <form action="{{ route('admin.media.update', $media) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                            <input type="text" name="title" id="title" value="{{ old('title', $media->title) }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            @error('title') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="alt" class="block text-sm font-medium text-gray-700 mb-2">Alt Text</label>
                            <input type="text" name="alt" id="alt" value="{{ old('alt', $media->alt) }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            @error('alt') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="caption" class="block text-sm font-medium text-gray-700 mb-2">Caption</label>
                        <input type="text" name="caption" id="caption" value="{{ old('caption', $media->caption) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        @error('caption') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea name="description" id="description" rows="3"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">{{ old('description', $media->description) }}</textarea>
                        @error('description') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-8 flex items-center gap-3 border-t border-gray-200 pt-6">
                    <button type="submit"
                            class="inline-flex items-center px-6 py-2.5 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
                        <i class="fa-solid fa-check mr-2"></i> Save Changes
                    </button>
                    <a href="{{ route('admin.media.show', $media) }}"
                       class="px-6 py-2.5 bg-gray-200 text-gray-700 font-medium rounded-md hover:bg-gray-300 transition">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
