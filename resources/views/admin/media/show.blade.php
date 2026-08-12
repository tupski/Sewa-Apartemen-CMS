@extends('layouts.admin')

@section('page-title', 'Media Details')

@section('content')
<div class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Media Details</h2>
            <p class="text-sm text-gray-600 mt-1">{{ $media->original_filename }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.media.edit', $media) }}"
               class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 transition">
                <i class="fa-solid fa-pen mr-2"></i> Edit
            </a>
            <a href="{{ route('admin.media.index') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 font-medium rounded-md hover:bg-gray-300 transition">
                <i class="fa-solid fa-arrow-left mr-2"></i> Back
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Preview -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            @if($media->type === 'image')
                <img src="{{ $media->url }}" alt="{{ $media->alt ?: $media->original_filename }}"
                     class="w-full h-auto rounded-md border border-gray-200">
            @elseif($media->type === 'video')
                <video src="{{ $media->url }}" controls class="w-full rounded-md border border-gray-200"></video>
            @else
                <div class="flex flex-col items-center justify-center py-16 text-gray-400 border border-dashed border-gray-300 rounded-md">
                    <i class="fa-regular fa-file-pdf text-5xl mb-3"></i>
                    <p class="text-sm">{{ $media->mime_type }}</p>
                </div>
            @endif
            <a href="{{ $media->url }}" target="_blank" rel="noopener"
               class="mt-4 w-full inline-flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 font-medium rounded-md hover:bg-gray-200 transition">
                <i class="fa-solid fa-download mr-2"></i> Open Original
            </a>
        </div>

        <!-- Details -->
        <div class="lg:col-span-2 bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">File Information</h3>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                <div>
                    <dt class="text-gray-500">Original Name</dt>
                    <dd class="font-medium text-gray-800 break-all">{{ $media->original_filename }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Stored Name</dt>
                    <dd class="font-medium text-gray-800 break-all">{{ $media->filename }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Type</dt>
                    <dd><span class="px-2 py-0.5 inline-flex text-xs font-semibold rounded-full bg-blue-100 text-blue-800">{{ ucfirst($media->type) }}</span></dd>
                </div>
                <div>
                    <dt class="text-gray-500">MIME Type</dt>
                    <dd class="font-medium text-gray-800">{{ $media->mime_type }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Size</dt>
                    <dd class="font-medium text-gray-800">{{ number_format($media->size / 1024, 1) }} KB</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Dimensions</dt>
                    <dd class="font-medium text-gray-800">{{ $media->width && $media->height ? $media->width . ' × ' . $media->height . ' px' : '-' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Folder</dt>
                    <dd class="font-medium text-gray-800 font-mono text-xs">{{ $media->directory }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Uploaded By</dt>
                    <dd class="font-medium text-gray-800">{{ $media->user?->name ?? 'Unknown' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-gray-500">Alt Text</dt>
                    <dd class="font-medium text-gray-800">{{ $media->alt ?: '-' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-gray-500">Title</dt>
                    <dd class="font-medium text-gray-800">{{ $media->title ?: '-' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-gray-500">Caption</dt>
                    <dd class="font-medium text-gray-800">{{ $media->caption ?: '-' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-gray-500">Description</dt>
                    <dd class="font-medium text-gray-800">{{ $media->description ?: '-' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-gray-500">URL</dt>
                    <dd class="font-medium text-gray-800 break-all">
                        <code class="text-xs bg-gray-100 px-2 py-1 rounded">{{ $media->url }}</code>
                    </dd>
                </div>
            </dl>

            <div class="mt-6 pt-6 border-t border-gray-200 flex items-center justify-end">
                <form action="{{ route('admin.media.destroy', $media) }}" method="POST"
                      onsubmit="return confirm('Are you sure you want to delete this file?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 text-white font-medium rounded-md hover:bg-red-700 transition">
                        <i class="fa-solid fa-trash mr-2"></i> Delete File
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
