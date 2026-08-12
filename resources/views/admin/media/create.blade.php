@extends('layouts.admin')

@section('page-title', 'Upload Media')

@section('content')
<div class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Upload Media</h2>
            <p class="text-sm text-gray-600 mt-1">Upload images, videos, or documents</p>
        </div>
        <a href="{{ route('admin.media.index') }}"
           class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 font-medium rounded-md hover:bg-gray-300 transition">
            <i class="fa-solid fa-arrow-left mr-2"></i> Back
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6 max-w-3xl">
        <form action="{{ route('admin.media.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="space-y-6">
                <!-- File -->
                <div>
                    <label for="file" class="block text-sm font-medium text-gray-700 mb-2">
                        File <span class="text-red-500">*</span>
                    </label>
                    <input type="file" name="file" id="file" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Max 10MB. Allowed: jpg, jpeg, png, webp, gif, svg, pdf, doc, docx, mp4, avi, mov</p>
                    @error('file') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Folder -->
                <div>
                    <label for="folder" class="block text-sm font-medium text-gray-700 mb-2">Folder</label>
                    <input type="text" name="folder" id="folder"
                           value="{{ old('folder', 'media/' . date('Y/m')) }}"
                           placeholder="media/2026/08"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Storage sub-directory (default: media/YYYY/MM)</p>
                    @error('folder') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Title -->
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                        <input type="text" name="title" id="title" value="{{ old('title') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        @error('title') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <!-- Alt -->
                    <div>
                        <label for="alt" class="block text-sm font-medium text-gray-700 mb-2">Alt Text</label>
                        <input type="text" name="alt" id="alt" value="{{ old('alt') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        @error('alt') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <!-- Caption -->
                <div>
                    <label for="caption" class="block text-sm font-medium text-gray-700 mb-2">Caption</label>
                    <input type="text" name="caption" id="caption" value="{{ old('caption') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    @error('caption') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="description" id="description" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">{{ old('description') }}</textarea>
                    @error('description') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-8 flex items-center gap-3 border-t border-gray-200 pt-6">
                <button type="submit"
                        class="inline-flex items-center px-6 py-2.5 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
                    <i class="fa-solid fa-upload mr-2"></i> Upload File
                </button>
                <a href="{{ route('admin.media.index') }}"
                   class="px-6 py-2.5 bg-gray-200 text-gray-700 font-medium rounded-md hover:bg-gray-300 transition">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
