@extends('layouts.admin')

@section('page-title', __('media.library'))

@section('content')
@php
    $mediaJson = $media->getCollection()->map(function ($m) {
        return [
            'id'                => $m->id,
            'url'               => $m->url,
            'thumbnail_url'     => $m->thumbnail_url,
            'filename'          => $m->filename,
            'original_filename' => $m->original_filename,
            'mime_type'         => $m->mime_type,
            'type'              => $m->type,
            'size'              => $m->size,
            'width'             => $m->width,
            'height'            => $m->height,
            'directory'         => $m->directory,
            'alt'               => $m->alt,
            'title'             => $m->title,
            'caption'           => $m->caption,
            'description'       => $m->description,
            'uploaded_by'       => $m->user?->name,
            'created_at'        => optional($m->created_at)->toDateTimeString(),
            'update_url'        => route('admin.media.update', $m),
            'destroy_url'       => route('admin.media.destroy', $m),
        ];
    })->values();
@endphp

<div class="w-full"
     x-data="mediaLibrary({
        items: {{ Illuminate\Support\Js::from($mediaJson) }},
        uploadUrl: '{{ route('admin.media.upload') }}',
        fromUrlUrl: '{{ route('admin.media.from-url') }}',
        indexUrl: '{{ route('admin.media.index') }}',
        csrf: '{{ csrf_token() }}',
     })">

    <!-- Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ __('media.library') }}</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ __('media.subtitle') }}</p>
        </div>
        <button type="button" @click="openAdd()"
                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
            <i class="fa-solid fa-plus mr-2"></i>
            {{ __('media.add') }}
        </button>
    </div>

    <!-- Search / filter -->
    <form method="GET" action="{{ route('admin.media.index') }}" class="mb-4 flex flex-wrap items-center gap-3">
        <div class="relative flex-1 min-w-[200px]">
            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="{{ __('media.search_placeholder') }}"
                   class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 rounded-md focus:ring-blue-500 focus:border-blue-500">
        </div>
        <select name="type"
                class="px-3 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 rounded-md focus:ring-blue-500 focus:border-blue-500">
            <option value="">{{ __('media.all_types') }}</option>
            <option value="image" @selected(request('type')==='image')>{{ __('media.type_image') }}</option>
            <option value="document" @selected(request('type')==='document')>{{ __('media.type_document') }}</option>
            <option value="video" @selected(request('type')==='video')>{{ __('media.type_video') }}</option>
        </select>
        <button type="submit"
                class="px-4 py-2 bg-gray-200 dark:bg-gray-700 dark:text-gray-100 text-gray-700 font-medium rounded-md hover:bg-gray-300 dark:hover:bg-gray-600 transition">
            {{ __('media.filter') }}
        </button>
    </form>

    <!-- Media Grid -->
    <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm overflow-hidden">
        @if($media->count() > 0)
            <div class="p-6">
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
                    <template x-for="item in items" :key="item.id">
                        <button type="button" @click="openDetails(item)"
                                class="group relative aspect-square rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 hover:shadow-lg hover:ring-2 hover:ring-blue-500 transition text-left">
                            <template x-if="item.type === 'image'">
                                <img :src="item.thumbnail_url || item.url" :alt="item.alt || item.filename"
                                     class="w-full h-full object-cover" loading="lazy">
                            </template>
                            <template x-if="item.type !== 'image'">
                                <span class="w-full h-full flex flex-col items-center justify-center bg-gray-100 dark:bg-gray-800 text-gray-400">
                                    <i class="text-4xl" :class="fileIcon(item)"></i>
                                    <span class="mt-2 text-[10px] uppercase font-semibold" x-text="item.mime_type.split('/').pop()"></span>
                                </span>
                            </template>
                            <span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent px-2 py-1.5 opacity-0 group-hover:opacity-100 transition">
                                <span class="block text-[11px] text-white truncate" x-text="item.title || item.original_filename"></span>
                            </span>
                        </button>
                    </template>
                </div>
            </div>

            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-800">
                {{ $media->links() }}
            </div>
        @else
            <div class="text-center py-16">
                <i class="fa-regular fa-images text-5xl text-gray-300 dark:text-gray-600"></i>
                <h3 class="mt-3 text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('media.empty_title') }}</h3>
                <p class="mt-1 text-sm text-gray-500">{{ __('media.empty_subtitle') }}</p>
                <button type="button" @click="openAdd()"
                        class="mt-6 inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 transition">
                    <i class="fa-solid fa-plus mr-2"></i>
                    {{ __('media.add') }}
                </button>
            </div>
        @endif
    </div>

    {{-- ─────────────────────────  ADD MEDIA MODAL  ───────────────────────── --}}
    <div x-show="addOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         @keydown.escape.window="addOpen = false">
        <div class="absolute inset-0 bg-black/50" @click="addOpen = false"></div>
        <div class="relative bg-white dark:bg-gray-900 rounded-xl shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-800">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">{{ __('media.add') }}</h3>
                <button type="button" @click="addOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>

            <!-- Tabs -->
            <div class="flex border-b border-gray-200 dark:border-gray-800 px-6">
                <button type="button" @click="tab = 'upload'"
                        :class="tab === 'upload' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                        class="px-4 py-3 -mb-px border-b-2 font-medium text-sm transition">
                    <i class="fa-solid fa-upload mr-2"></i>{{ __('media.upload_files') }}
                </button>
                <button type="button" @click="tab = 'library'; loadLibrary()"
                        :class="tab === 'library' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                        class="px-4 py-3 -mb-px border-b-2 font-medium text-sm transition">
                    <i class="fa-regular fa-images mr-2"></i>{{ __('media.library') }}
                </button>
                <button type="button" @click="tab = 'url'"
                        :class="tab === 'url' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                        class="px-4 py-3 -mb-px border-b-2 font-medium text-sm transition">
                    <i class="fa-solid fa-link mr-2"></i>{{ __('media.from_url') }}
                </button>
            </div>

            <div class="p-6 overflow-y-auto">
                {{-- Tab 1: Upload from computer --}}
                <div x-show="tab === 'upload'">
                    <div @dragover.prevent="dragging = true"
                         @dragleave.prevent="dragging = false"
                         @drop.prevent="handleDrop($event)"
                         :class="dragging ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-300 dark:border-gray-700'"
                         class="border-2 border-dashed rounded-lg p-10 text-center transition">
                        <i class="fa-solid fa-cloud-arrow-up text-4xl text-gray-400 mb-3"></i>
                        <p class="text-gray-600 dark:text-gray-300 font-medium" x-text="dragging ? '{{ __('media.drop_here') }}' : '{{ __('media.drag_or_click') }}'"></p>
                        <p class="text-xs text-gray-400 mt-1">{{ __('media.supported_files') }}</p>
                        <input type="file" x-ref="fileInput" multiple class="hidden"
                               accept="image/jpeg,image/png,image/webp,image/gif,image/svg+xml,application/pdf"
                               @change="handleFiles($event.target.files)">
                        <button type="button" @click="$refs.fileInput.click()"
                                class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 transition">
                            {{ __('media.select_files') }}
                        </button>
                    </div>

                    <!-- Per-file progress -->
                    <div class="mt-4 space-y-2" x-show="queue.length">
                        <template x-for="(f, i) in queue" :key="i">
                            <div class="flex items-center gap-3 text-sm">
                                <i class="fa-regular fa-file text-gray-400"></i>
                                <span class="flex-1 truncate text-gray-700 dark:text-gray-300" x-text="f.name"></span>
                                <div class="w-32 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                    <div class="h-full transition-all"
                                         :class="f.status === 'error' ? 'bg-red-500' : 'bg-blue-600'"
                                         :style="`width: ${f.progress}%`"></div>
                                </div>
                                <span class="w-16 text-right text-xs"
                                      :class="{
                                        'text-green-600': f.status === 'done',
                                        'text-red-600': f.status === 'error',
                                        'text-gray-500': f.status === 'uploading',
                                      }"
                                      x-text="f.status === 'done' ? '{{ __('media.done') }}' : (f.status === 'error' ? '{{ __('media.failed') }}' : f.progress + '%')"></span>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Tab 2: Media Library (browse existing) --}}
                <div x-show="tab === 'library'">
                    <div x-show="libraryLoading" class="py-10 text-center text-gray-400">
                        <i class="fa-solid fa-spinner fa-spin text-2xl"></i>
                    </div>
                    <div x-show="!libraryLoading" class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3">
                        <template x-for="item in libraryItems" :key="item.id">
                            <button type="button" @click="addOpen = false; openDetails(item)"
                                    class="group relative aspect-square rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 hover:ring-2 hover:ring-blue-500 transition">
                                <template x-if="item.type === 'image'">
                                    <img :src="item.thumbnail_url || item.url" :alt="item.alt || item.filename" class="w-full h-full object-cover" loading="lazy">
                                </template>
                                <template x-if="item.type !== 'image'">
                                    <span class="w-full h-full flex items-center justify-center bg-gray-100 dark:bg-gray-800 text-gray-400">
                                        <i class="text-3xl" :class="fileIcon(item)"></i>
                                    </span>
                                </template>
                            </button>
                        </template>
                    </div>
                    <p x-show="!libraryLoading && !libraryItems.length" class="py-10 text-center text-gray-400 text-sm">
                        {{ __('media.empty_title') }}
                    </p>
                </div>

                {{-- Tab 3: Upload from URL --}}
                <div x-show="tab === 'url'">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('media.file_url') }}</label>
                    <div class="flex gap-2">
                        <input type="url" x-model="urlValue"
                               placeholder="{{ __('media.url_placeholder') }}"
                               class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        <button type="button" @click="importFromUrl()" :disabled="urlLoading || !urlValue"
                                class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 disabled:opacity-50 transition">
                            <span x-show="!urlLoading">{{ __('media.import') }}</span>
                            <span x-show="urlLoading"><i class="fa-solid fa-spinner fa-spin mr-1"></i>{{ __('media.importing') }}</span>
                        </button>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">{{ __('media.url_help') }}</p>
                    <p x-show="urlError" x-text="urlError" class="text-sm text-red-600 mt-2"></p>
                </div>
            </div>
        </div>
    </div>

    {{-- ─────────────────────────  DETAILS MODAL  ───────────────────────── --}}
    <div x-show="detailsOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         @keydown.escape.window="detailsOpen = false">
        <div class="absolute inset-0 bg-black/50" @click="detailsOpen = false"></div>
        <div class="relative bg-white dark:bg-gray-900 rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-800">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">{{ __('media.details') }}</h3>
                <button type="button" @click="detailsOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 p-6 overflow-y-auto" x-show="current">
                <!-- Preview -->
                <div class="lg:col-span-2">
                    <div class="rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex items-center justify-center min-h-[200px]">
                        <template x-if="current && current.type === 'image'">
                            <img :src="current.url" :alt="current.alt || current.filename" class="w-full h-auto object-contain max-h-[50vh]">
                        </template>
                        <template x-if="current && current.type === 'video'">
                            <video :src="current.url" controls class="w-full"></video>
                        </template>
                        <template x-if="current && current.type !== 'image' && current.type !== 'video'">
                            <div class="flex flex-col items-center py-16 text-gray-400">
                                <i class="text-5xl" :class="fileIcon(current)"></i>
                                <span class="mt-3 text-sm" x-text="current.mime_type"></span>
                            </div>
                        </template>
                    </div>

                    <!-- Metadata -->
                    <dl class="mt-4 text-xs space-y-1.5 text-gray-600 dark:text-gray-400" x-show="current">
                        <div class="flex justify-between gap-2"><dt>{{ __('media.filename') }}</dt><dd class="font-medium text-gray-800 dark:text-gray-200 truncate" x-text="current?.original_filename"></dd></div>
                        <div class="flex justify-between gap-2"><dt>{{ __('media.mime') }}</dt><dd class="font-medium text-gray-800 dark:text-gray-200" x-text="current?.mime_type"></dd></div>
                        <div class="flex justify-between gap-2"><dt>{{ __('media.size') }}</dt><dd class="font-medium text-gray-800 dark:text-gray-200" x-text="humanSize(current?.size)"></dd></div>
                        <div class="flex justify-between gap-2" x-show="current?.width"><dt>{{ __('media.dimensions') }}</dt><dd class="font-medium text-gray-800 dark:text-gray-200" x-text="current?.width + ' × ' + current?.height + ' px'"></dd></div>
                        <div class="flex justify-between gap-2"><dt>{{ __('media.uploaded_by') }}</dt><dd class="font-medium text-gray-800 dark:text-gray-200" x-text="current?.uploaded_by || '-'"></dd></div>
                    </dl>

                    <!-- URL + copy -->
                    <div class="mt-3" x-show="current">
                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('media.url') }}</label>
                        <div class="flex gap-2">
                            <input type="text" readonly :value="current?.url"
                                   class="flex-1 px-2 py-1.5 text-xs border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 rounded-md font-mono">
                            <button type="button" @click="copyUrl(current?.url)"
                                    class="px-3 py-1.5 bg-gray-200 dark:bg-gray-700 dark:text-gray-100 text-gray-700 text-xs rounded-md hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                                <i class="fa-regular fa-copy mr-1"></i><span x-text="copied ? '{{ __('media.copied') }}' : '{{ __('media.copy_url') }}'"></span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Edit form -->
                <div class="lg:col-span-3">
                    <form @submit.prevent="saveDetails()" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('media.title') }}</label>
                            <input type="text" x-model="form.title"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('media.alt') }}</label>
                            <input type="text" x-model="form.alt"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('media.caption') }}</label>
                            <input type="text" x-model="form.caption"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('media.description') }}</label>
                            <textarea x-model="form.description" rows="4"
                                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 rounded-md focus:ring-blue-500 focus:border-blue-500"></textarea>
                        </div>

                        <div class="flex items-center justify-between gap-3 border-t border-gray-200 dark:border-gray-800 pt-4">
                            <button type="button" @click="deleteCurrent()"
                                    class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700 transition">
                                <i class="fa-solid fa-trash mr-2"></i>{{ __('media.delete') }}
                            </button>
                            <div class="flex items-center gap-3">
                                <span x-show="saved" class="text-sm text-green-600"><i class="fa-solid fa-check mr-1"></i>{{ __('media.saved') }}</span>
                                <button type="submit" :disabled="saving"
                                        class="inline-flex items-center px-5 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 disabled:opacity-50 transition">
                                    <i class="fa-solid fa-check mr-2" x-show="!saving"></i>
                                    <i class="fa-solid fa-spinner fa-spin mr-2" x-show="saving"></i>
                                    {{ __('media.save') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
